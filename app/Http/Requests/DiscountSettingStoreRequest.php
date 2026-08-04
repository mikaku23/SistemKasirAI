<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class DiscountSettingStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => trim((string) $this->input('code')) ?: null,
            'discount_type' => in_array((string) $this->input('discount_type'), ['percent', 'fixed'], true)
                ? $this->input('discount_type')
                : 'percent',
            'discount_value' => $this->normalizeInt($this->input('discount_value')),
            'minimum_total_amount' => $this->normalizeInt($this->input('minimum_total_amount')),
            'ends_at' => $this->input('ends_at') ? Carbon::parse($this->input('ends_at'))->format('Y-m-d') : null,
            'priority' => $this->normalizeInt($this->input('priority', 0)),
            'is_default' => $this->boolean('is_default'),
            'is_active' => $this->boolean('is_active', true),
            'description' => trim((string) $this->input('description')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('discount_settings', 'name')],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('discount_settings', 'code')],
            'discount_type' => ['required', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['required', 'integer', 'min:1', $this->input('discount_type') === 'percent' ? 'max:100' : 'max:1000000000'],
            'minimum_total_amount' => ['required', 'integer', 'min:0'],
            'ends_at' => ['required', 'date', 'after_or_equal:today'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_default' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama diskon wajib diisi.',
            'name.unique' => 'Nama diskon sudah digunakan.',
            'code.unique' => 'Kode diskon sudah digunakan.',
            'discount_type.required' => 'Tipe diskon wajib dipilih.',
            'discount_value.required' => 'Nilai diskon wajib diisi.',
            'discount_value.max' => 'Nilai diskon persen maksimal 100.',
            'minimum_total_amount.required' => 'Batas minimum transaksi wajib diisi.',
            'ends_at.required' => 'Tanggal berakhir wajib diisi.',
            'ends_at.after_or_equal' => 'Tanggal berakhir tidak boleh lebih kecil dari hari ini.',
        ];
    }

    protected function normalizeInt(mixed $value): int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }
        $value = preg_replace('/[^0-9\-]/', '', $value) ?: '0';
        return (int) $value;
    }
}
