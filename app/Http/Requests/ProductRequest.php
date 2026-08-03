<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product');

        if ($productId instanceof Product) {
            $productId = $productId->id;
        }

        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('products', 'slug')->ignore($productId)],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($productId)],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'description' => ['nullable', 'string', 'max:5000'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'max:4096'],
            'search_keywords' => ['nullable'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'stock_on_hand' => ['nullable', 'numeric', 'min:0'],
            'tracks_expiry' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
            'is_available_online' => ['required', 'boolean'],
            'popularity_score' => ['nullable', 'numeric', 'min:0'],
            'last_sold_at' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'category_id' => $this->filled('category_id') ? (int) $this->input('category_id') : null,
            'unit_id' => $this->filled('unit_id') ? (int) $this->input('unit_id') : null,
            'supplier_id' => $this->filled('supplier_id') ? (int) $this->input('supplier_id') : null,
            'location_id' => $this->filled('location_id') ? (int) $this->input('location_id') : null,
            'tracks_expiry' => $this->boolean('tracks_expiry'),
            'is_featured' => $this->boolean('is_featured'),
            'is_available_online' => $this->boolean('is_available_online'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
