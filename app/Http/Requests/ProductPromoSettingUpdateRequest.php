<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ProductPromoSettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $startsAt = trim((string) $this->input('promo_starts_at'));
        $endsAt = trim((string) $this->input('promo_ends_at'));

        $this->merge([
            'promo_discount_amount' => $this->normalizeMoney($this->input('promo_discount_amount')),
            'promo_discount_is_active' => $this->boolean('promo_discount_is_active'),
            'promo_starts_at' => $startsAt !== '' ? Carbon::parse($startsAt)->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
            'promo_ends_at' => $endsAt !== '' ? Carbon::parse($endsAt)->format('Y-m-d H:i:s') : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'promo_discount_amount' => ['required', 'integer', 'min:0'],
            'promo_discount_is_active' => ['required', 'boolean'],
            'promo_starts_at' => ['required', 'date'],
            'promo_ends_at' => ['required', 'date', 'after_or_equal:promo_starts_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'promo_discount_amount.required' => 'Diskon promo wajib diisi.',
            'promo_starts_at.required' => 'Promo mulai wajib diisi.',
            'promo_ends_at.required' => 'Promo berakhir wajib diisi.',
            'promo_ends_at.after_or_equal' => 'Tanggal selesai promo tidak boleh lebih kecil dari tanggal mulai.',
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
