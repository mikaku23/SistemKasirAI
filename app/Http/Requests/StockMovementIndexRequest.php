<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMovementIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $period = trim((string) $this->input('period', 'all'));
        $mode = trim((string) $this->input('mode', 'grouped'));

        $this->merge([
            'q' => trim((string) $this->input('q')),
            'movement_type' => trim((string) $this->input('movement_type')),
            'product_id' => $this->filled('product_id') ? (int) $this->input('product_id') : null,
            'user_id' => $this->filled('user_id') ? (int) $this->input('user_id') : null,
            'location_id' => $this->filled('location_id') ? (int) $this->input('location_id') : null,
            'period' => in_array($period, ['all', 'day', 'week', 'month', 'year', 'custom'], true) ? $period : 'all',
            'mode' => in_array($mode, ['grouped', 'raw'], true) ? $mode : 'grouped',
            'date_from' => $this->filled('date_from') ? $this->input('date_from') : null,
            'date_to' => $this->filled('date_to') ? $this->input('date_to') : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'movement_type' => ['nullable', Rule::in([
                'in',
                'out',
                'adjustment',
                'transfer_in',
                'transfer_out',
                'return_in',
                'return_out',
                'write_off',
            ])],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'period' => ['required', Rule::in(['all', 'day', 'week', 'month', 'year', 'custom'])],
            'mode' => ['nullable', Rule::in(['grouped', 'raw'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_to.after_or_equal' => 'Tanggal akhir tidak boleh lebih kecil dari tanggal awal.',
        ];
    }
}
