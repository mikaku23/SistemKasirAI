<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class TransactionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'location_id' => $this->filled('location_id') ? (int) $this->input('location_id') : null,
            'tax_setting_id' => $this->filled('tax_setting_id') ? (int) $this->input('tax_setting_id') : null,
            'product_id' => $this->filled('product_id') ? (int) $this->input('product_id') : null,
            'quantity' => $this->filled('quantity') ? (int) $this->input('quantity') : null,
            'cashier_id' => $this->filled('cashier_id') ? (int) $this->input('cashier_id') : null,
            'customer_name' => trim((string) $this->input('customer_name')),
            'customer_phone' => trim((string) $this->input('customer_phone')),
            'shift' => trim((string) $this->input('shift')) ?: 'morning',
            'payment_method' => trim((string) $this->input('payment_method')) ?: 'cash',
            'paid_amount' => $this->normalizeMoney($this->input('paid_amount', 0)),
            'transaction_at' => $this->input('transaction_at')
                ? Carbon::parse($this->input('transaction_at'))->format('Y-m-d H:i:s')
                : now()->format('Y-m-d H:i:s'),
            'notes' => trim((string) $this->input('notes')),
        ]);
    }

    public function rules(): array
    {
        return [
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'tax_setting_id' => [
                'required',
                'integer',
                Rule::exists('tax_settings', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'cashier_id' => ['nullable', 'integer', 'exists:users,id'],
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'shift' => ['required', Rule::in(['morning', 'afternoon', 'night'])],
            'payment_method' => ['required', Rule::in(['cash', 'qris', 'transfer', 'debit', 'ewallet', 'mixed'])],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'transaction_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'location_id.required' => 'Location wajib dipilih.',
            'tax_setting_id.required' => 'Tax setting wajib dipilih.',
            'product_id.required' => 'Product wajib dipilih.',
            'quantity.required' => 'Jumlah barang wajib diisi.',
            'quantity.min' => 'Jumlah barang minimal 1 pcs.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'paid_amount.required' => 'Uang pelanggan wajib diisi.',
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
