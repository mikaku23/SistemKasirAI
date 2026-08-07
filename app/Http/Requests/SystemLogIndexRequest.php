<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SystemLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $period = trim((string) $this->input('period', 'day'));

        $this->merge([
            'q' => trim((string) $this->input('q')),
            'period' => in_array($period, ['all', 'day', 'week', 'month', 'year', 'custom'], true) ? $period : 'day',
            'date_from' => $this->filled('date_from') ? $this->input('date_from') : null,
            'date_to' => $this->filled('date_to') ? $this->input('date_to') : null,
            'level' => trim((string) $this->input('level')),
            'channel' => trim((string) $this->input('channel')),
        ]);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'period' => ['required', Rule::in(['all', 'day', 'week', 'month', 'year', 'custom'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'level' => ['nullable', Rule::in(['debug', 'info', 'notice', 'warning', 'error', 'critical'])],
            'channel' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_to.after_or_equal' => 'Tanggal akhir tidak boleh lebih kecil dari tanggal awal.',
        ];
    }
}
