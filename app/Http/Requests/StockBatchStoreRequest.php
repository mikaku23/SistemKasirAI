<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StockBatchStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $qtyReceived = $this->normalizeInteger($this->input('qty_received'));

        $this->merge([
            'product_id' => $this->filled('product_id') ? (int) $this->input('product_id') : null,
            'supplier_id' => $this->filled('supplier_id') ? (int) $this->input('supplier_id') : null,
            'location_id' => $this->filled('location_id') ? (int) $this->input('location_id') : null,
            'received_by' => $this->filled('received_by') ? (int) $this->input('received_by') : Auth::id(),
            'qty_received' => $qtyReceived,
            'qty_remaining' => $this->normalizeInteger($this->input('qty_remaining')),
            'purchase_price' => $this->normalizeInteger($this->input('purchase_price')),
            'production_date' => $this->normalizeDate($this->input('production_date')),
            'expired_at' => $this->normalizeDate($this->input('expired_at')),
            'received_at' => $this->normalizeDate($this->input('received_at')) ?: now()->toDateString(),
            'expiry_mode' => $this->normalizeExpiryMode($this->input('expiry_mode')),
            'shelf_life_days' => $this->normalizeInteger($this->input('shelf_life_days')),
            'expiry_warning_days' => $this->normalizeInteger($this->input('expiry_warning_days')),
            'expiry_grace_days' => $this->normalizeInteger($this->input('expiry_grace_days')),
            'notes' => trim((string) $this->input('notes')),
        ]);
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'received_by' => ['nullable', 'integer', 'exists:users,id'],
            'qty_received' => ['required', 'integer', 'min:1'],
            'qty_remaining' => ['nullable', 'integer', 'min:0'],
            'purchase_price' => ['nullable', 'integer', 'min:0'],
            'production_date' => ['nullable', 'date', 'required_if:expiry_mode,shelf_life'],
            'expired_at' => ['nullable', 'date', 'required_if:expiry_mode,fixed_date'],
            'received_at' => ['nullable', 'date'],
            'expiry_mode' => ['required', Rule::in(['none', 'fixed_date', 'shelf_life'])],
            'shelf_life_days' => ['nullable', 'integer', 'min:1', 'required_if:expiry_mode,shelf_life'],
            'expiry_warning_days' => ['nullable', 'integer', 'min:0'],
            'expiry_grace_days' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Produk wajib dipilih.',
            'product_id.exists' => 'Produk tidak valid.',
            'qty_received.required' => 'Jumlah diterima wajib diisi.',
            'qty_received.integer' => 'Jumlah diterima harus berupa angka bulat.',
            'qty_remaining.integer' => 'Sisa stok harus berupa angka bulat.',
            'production_date.required_if' => 'Tanggal produksi wajib diisi untuk mode Shelf Life.',
            'expired_at.required_if' => 'Tanggal expired wajib diisi untuk mode Fixed Date.',
            'shelf_life_days.required_if' => 'Shelf life wajib diisi untuk mode Shelf Life.',
            'expiry_mode.required' => 'Mode expiry wajib dipilih.',
            'expiry_mode.in' => 'Mode expiry tidak valid.',
        ];
    }

    protected function normalizeInteger(mixed $value): ?int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^0-9\-]/', '', $value) ?? '';

        return $value === '' ? null : (int) $value;
    }

    protected function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function normalizeExpiryMode(mixed $value): string
    {
        $value = trim((string) $value);

        return in_array($value, ['none', 'fixed_date', 'shelf_life'], true) ? $value : 'none';
    }
}
