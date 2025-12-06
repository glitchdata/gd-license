<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $licenseId = $this->route('license')->id ?? null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'product_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('licenses', 'product_code')->ignore($licenseId),
            ],
            'seats_total' => ['required', 'integer', 'min:1'],
            'seats_used' => ['nullable', 'integer', 'min:0', 'lte:seats_total'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
