<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminSurrogateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canSellOnBehalf() ?? false;
    }

    public function rules(): array
    {
        return [
            'client_name' => ['required', 'string', 'max:255', 'regex:/^[\pL\s\-\.\']+$/u'],
            'client_email' => ['required', 'string', 'email', 'max:255'],
            'client_email_confirmation' => ['required', 'same:client_email'],
            'client_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9\s+\-]+$/'],
            'update_existing_profile' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_name.regex' => 'El nombre solo puede contener letras, espacios y guiones.',
            'client_email_confirmation.same' => 'La confirmación del correo debe coincidir.',
            'client_phone.regex' => 'El teléfono solo puede contener números, espacios, + y -.',
        ];
    }
}
