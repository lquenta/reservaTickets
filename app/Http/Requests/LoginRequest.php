<?php

namespace App\Http\Requests;

use App\Services\RecaptchaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
            'g-recaptcha-response' => [config('services.recaptcha.secret_key') ? 'required' : 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
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

    public function authenticate(): void
    {
        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => [__('Las credenciales proporcionadas no son correctas.')],
            ]);
        }
    }
}
