<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockAdjustmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'product_id' => $this->filled('product_id') ? (int) $this->input('product_id') : null,
            'location_id' => $this->filled('location_id') ? (int) $this->input('location_id') : null,
            'physical_qty' => $this->normalizeInteger($this->input('physical_qty')),
            'reason' => trim((string) $this->input('reason')),
        ]);
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'physical_qty' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Produk wajib dipilih.',
            'product_id.exists' => 'Produk tidak valid.',
            'physical_qty.required' => 'Jumlah stok fisik wajib diisi.',
            'physical_qty.integer' => 'Jumlah stok fisik harus berupa angka bulat.',
            'physical_qty.min' => 'Jumlah stok fisik minimal 0.',
        ];
    }

    protected function normalizeInteger(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^0-9\-]/', '', $value) ?? '';

        return $value === '' ? null : (int) $value;
    }
}
