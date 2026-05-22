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
            'payment_receipt' => ['required', 'image', 'max:5120'],
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
            'payment_receipt.required' => 'Debe subir una captura o foto del comprobante de pago.',
            'seller_delivery_responsibility.accepted' => 'Debe asumir la responsabilidad de entrega de tickets al cliente si el correo no está verificado.',
        ];
    }
}
