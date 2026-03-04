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

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^[\pL\s\-\.\']+$/u'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'ci' => ['required', 'string', 'max:15', 'regex:/^[0-9\-]+$/'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9\s+\-]+$/'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'g-recaptcha-response' => [config('services.recaptcha.secret_key') ? 'required' : 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => __('El nombre solo puede contener letras, espacios y guiones.'),
            'ci.regex' => __('El CI solo puede contener dígitos y guiones.'),
            'phone.regex' => __('El teléfono solo puede contener números, espacios, + y -.'),
            'g-recaptcha-response.required' => __('Debe completar la verificación de seguridad.'),
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
