<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminSurrogateCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canSellOnBehalf() ?? false;
    }

    public function rules(): array
    {
        $rules = [
            'accept_terms' => ['required', 'accepted'],
            'payment_receipt' => ['required', 'file', 'max:15360'],
        ];

        $reservation = $this->route('reservation');
        $client = $reservation?->user;
        if ($client && ! $client->isGuest() && ! $client->hasVerifiedEmail()) {
            $rules['seller_delivery_responsibility'] = ['required', 'accepted'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'payment_receipt.required' => 'Debe subir el comprobante de pago.',
            'payment_receipt.max' => 'El comprobante no debe superar 15 MB.',
            'seller_delivery_responsibility.accepted' => 'Debe asumir la responsabilidad de entrega de tickets al cliente si el correo no está verificado.',
        ];
    }
}
