<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LogTcIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $period = trim((string) $this->input('period', 'all'));

        $this->merge([
            'q' => trim((string) $this->input('q')),
            'period' => in_array($period, ['all', 'day', 'week', 'month', 'year', 'custom'], true) ? $period : 'all',
            'date_from' => $this->filled('date_from') ? $this->input('date_from') : null,
            'date_to' => $this->filled('date_to') ? $this->input('date_to') : null,
            'location_id' => $this->filled('location_id') ? (int) $this->input('location_id') : null,
            'cashier_id' => $this->filled('cashier_id') ? (int) $this->input('cashier_id') : null,
            'status' => trim((string) $this->input('status')),
        ]);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'period' => ['required', Rule::in(['all', 'day', 'week', 'month', 'year', 'custom'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'cashier_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::in(['paid', 'draft', 'cancelled', 'refunded'])],
        ];
    }

    public function messages(): array
    {
        return [
            'date_to.after_or_equal' => 'Tanggal akhir tidak boleh lebih kecil dari tanggal awal.',
        ];
    }
}
