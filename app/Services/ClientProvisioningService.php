<?php

namespace App\Services;

use App\DTOs\ClientResolution;
use App\Models\ReservationAuditLog;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClientProvisioningService
{
    public function resolveForAdminSale(
        string $name,
        ?string $email,
        ?string $phone,
        User $admin,
        string $provisionedVia,
        bool $updateProfile = false,
        bool $guestMode = false
    ): ClientResolution {
        if ($guestMode) {
            return $this->createGuestUser($name, $admin, $provisionedVia);
        }

        $email = strtolower(trim((string) $email));
        $phone = PhoneNormalizer::normalize((string) $phone) ?? $phone;
        $existing = User::where('email', $email)->first();

        if ($existing) {
            if ($existing->isAdmin() || $existing->isVendedor()) {
                throw ValidationException::withMessages([
                    'client_email' => ['No se puede usar la cuenta de un administrador o vendedor como cliente.'],
                ]);
            }

            if ($updateProfile) {
                $existing->update([
                    'name' => $name,
                    'phone' => $phone,
                ]);
            }

            $action = $provisionedVia === User::PROVISIONED_VIA_SURROGATE
                ? ReservationAuditLog::ACTION_SURROGATE_SALE_EXISTING_USER
                : ReservationAuditLog::ACTION_SURROGATE_SALE_EXISTING_USER;

            app(ReservationAuditService::class)->log(
                $action,
                ReservationAuditLog::RESULT_SUCCESS,
                $admin,
                null,
                null,
                $existing,
                "Cuenta existente reutilizada ({$email})."
            );

            return new ClientResolution($existing->fresh(), false);
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'ci' => null,
            'password' => Str::password(32),
            'role' => 'user',
            'created_by_user_id' => $admin->id,
            'provisioned_via' => $provisionedVia,
            'is_guest' => false,
        ]);

        $user->sendEmailVerificationNotification();

        app(ReservationAuditService::class)->log(
            ReservationAuditLog::ACTION_USER_PROVISIONED_BY_ADMIN,
            ReservationAuditLog::RESULT_SUCCESS,
            $admin,
            null,
            null,
            $user,
            "Usuario creado por admin ({$provisionedVia}): {$email}."
        );

        return new ClientResolution($user, true);
    }

    private function createGuestUser(string $name, User $admin, string $provisionedVia): ClientResolution
    {
        $email = 'guest+'.strtolower((string) Str::ulid()).'@guest.local';

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => null,
            'ci' => null,
            'password' => Str::password(32),
            'role' => 'user',
            'created_by_user_id' => $admin->id,
            'provisioned_via' => $provisionedVia,
            'is_guest' => true,
        ]);

        app(ReservationAuditService::class)->log(
            ReservationAuditLog::ACTION_USER_PROVISIONED_BY_ADMIN,
            ReservationAuditLog::RESULT_SUCCESS,
            $admin,
            null,
            null,
            $user,
            "Invitado temporal creado ({$provisionedVia}): {$name}."
        );

        return new ClientResolution($user, true);
    }

    public function lookupByEmail(string $email): ?User
    {
        $user = User::where('email', strtolower(trim($email)))->first();

        if ($user && ($user->isAdmin() || $user->isGuest())) {
            return null;
        }

        return $user;
    }
}
