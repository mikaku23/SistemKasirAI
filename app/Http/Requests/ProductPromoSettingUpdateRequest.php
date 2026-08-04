<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class ProductPromoSettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'promo_discount_amount' => $this->normalizeMoney($this->input('promo_discount_amount')),
            'promo_discount_is_active' => $this->boolean('promo_discount_is_active'),
        ]);
    }

    public function rules(): array
    {
        /** @var Product|int|string|null $product */
        $product = $this->route('product');
        $productId = $product instanceof Product ? $product->id : $product;

        return [
            'id' => ['nullable', 'integer'],
            'promo_discount_amount' => ['required', 'integer', 'min:0'],
            'promo_discount_is_active' => ['required', 'boolean'],
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
