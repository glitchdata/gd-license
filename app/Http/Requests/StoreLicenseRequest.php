<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'product_code' => ['required', 'string', 'max:50', 'unique:licenses,product_code'],
            'seats_total' => ['required', 'integer', 'min:1'],
            'seats_used' => ['nullable', 'integer', 'min:0', 'lte:seats_total'],
            'expires_at' => ['nullable', 'date'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'seats_used' => $this->input('seats_used', 0),
        ]);
    }
}
