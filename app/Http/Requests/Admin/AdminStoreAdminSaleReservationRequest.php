<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\StoreReservationRequest;
use Illuminate\Contracts\Validation\Validator;

class AdminStoreAdminSaleReservationRequest extends StoreReservationRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['g-recaptcha-response']);

        return $rules;
    }

    protected function passedValidation(): void
    {
        // Sin reCAPTCHA en ventas admin.
    }

    protected function failedValidation(Validator $validator): void
    {
        parent::failedValidation($validator);
    }
}
