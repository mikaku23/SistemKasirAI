<?php

namespace App\Http\Requests;

use App\Models\TaxSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaxSettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = trim((string) $this->input('name'));
        $codeInput = trim((string) $this->input('code'));

        $this->merge([
            'name' => $name,
            'code' => $codeInput !== '' ? Str::upper($codeInput) : '',
            'tax_type' => trim((string) $this->input('tax_type')) ?: 'fixed',
            'tax_value' => $this->normalizeMoney($this->input('tax_value')),
            'is_default' => $this->boolean('is_default'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $setting = $this->route('tax_setting');
        $settingId = $setting instanceof TaxSetting ? $setting->id : $setting;

        return [
            'id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('tax_settings', 'code')->ignore($settingId),
            ],
            'tax_type' => ['required', Rule::in(['fixed', 'percent'])],
            'tax_value' => ['required', 'integer', 'min:0'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama pajak wajib diisi.',
            'code.unique' => 'Kode pajak sudah digunakan.',
            'tax_type.required' => 'Tipe pajak wajib dipilih.',
            'tax_value.required' => 'Nilai pajak wajib diisi.',
        ];
    }

    protected function normalizeMoney(mixed $value): int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        $numeric = preg_replace('/[^0-9]/', '', $value) ?? '0';

        return (int) $numeric;
    }
}
