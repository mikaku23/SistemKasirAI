<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'symbol' => strtoupper(trim((string) $this->input('symbol'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('units', 'name')],
            'symbol' => ['required', 'string', 'max:20', Rule::unique('units', 'symbol')],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama unit wajib diisi.',
            'name.unique' => 'Nama unit sudah digunakan.',
            'symbol.required' => 'Symbol unit wajib diisi.',
            'symbol.unique' => 'Symbol unit sudah digunakan.',
            'is_active.required' => 'Status unit wajib dipilih.',
        ];
    }
}
