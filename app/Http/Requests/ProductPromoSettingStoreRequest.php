<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductPromoSettingStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'product_id' => $this->filled('product_id') ? (int) $this->input('product_id') : null,
            'promo_discount_amount' => $this->normalizeMoney($this->input('promo_discount_amount')),
            'promo_discount_is_active' => $this->boolean('promo_discount_is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'promo_discount_amount' => ['required', 'integer', 'min:0'],
            'promo_discount_is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Product wajib dipilih.',
            'promo_discount_amount.required' => 'Diskon promo wajib diisi.',
        ];
    }

    protected function normalizeMoney(mixed $value): int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        $value = preg_replace('/[^0-9\-]/', '', $value) ?? '0';

        return (int) $value;
    }
}
