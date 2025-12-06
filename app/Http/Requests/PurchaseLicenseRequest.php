<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'seats_total' => ['required', 'integer', 'min:1', 'max:500'],
            'domain' => ['nullable', 'string', 'max:255'],
            'card_name' => ['required', 'string', 'max:255'],
            'card_number' => ['required', 'string', 'regex:/^[0-9\s-]+$/', 'min:12', 'max:23'],
            'card_exp_month' => ['required', 'integer', 'between:1,12'],
            'card_exp_year' => ['required', 'integer', 'min:'.now()->year, 'max:'.(now()->year + 10)],
            'card_cvc' => ['required', 'string', 'regex:/^[0-9]{3,4}$/'],
        ];
    }
}
