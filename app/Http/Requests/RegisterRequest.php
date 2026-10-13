<?php

namespace App\Http\Requests;

use App\Services\RecaptchaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $ci = preg_replace('/[\s.]+/', '', (string) $this->input('ci', ''));

        $this->merge([
            'email' => strtolower(trim((string) $this->input('email', ''))),
            'ci' => $ci,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^[\pL\pM\s\-\.\']+$/u'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'ci' => ['required', 'string', 'max:15', 'regex:/^[0-9\-]+$/'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9\s+\-().]+$/'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'g-recaptcha-response' => [config('services.recaptcha.secret_key') ? 'required' : 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'El nombre solo puede contener letras, espacios, puntos, apóstrofos y guiones.',
            'ci.regex' => 'El CI solo puede contener números. Puedes usar espacios, puntos y guiones.',
            'ci.required' => 'El CI es obligatorio.',
            'phone.regex' => 'El teléfono solo puede contener números, espacios, paréntesis, puntos, + y -.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'email.unique' => 'Este correo ya tiene una cuenta. Recupera la contraseña para entrar.',
            'g-recaptcha-response.required' => 'Debe completar la verificación de seguridad.',
        ];
    }

    protected function passedValidation(): void
    {
        if (! config('services.recaptcha.secret_key')) {
            return;
        }
        $recaptcha = app(RecaptchaService::class);
        if (! $recaptcha->verify($this->input('g-recaptcha-response', ''))) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => [__('La verificación de seguridad ha fallado. Intente de nuevo.')],
            ]);
        }
    }
}
