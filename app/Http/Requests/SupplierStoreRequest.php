<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => trim((string) $this->input('code')),
            'phone' => trim((string) $this->input('phone')),
            'email' => $this->input('email') !== null ? strtolower(trim((string) $this->input('email'))) : null,
            'address' => trim((string) $this->input('address')),
            'notes' => trim((string) $this->input('notes')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:120', Rule::unique('suppliers', 'code')],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:190', Rule::unique('suppliers', 'email')],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama supplier wajib diisi.',
            'code.unique' => 'Kode supplier sudah digunakan.',
            'email.unique' => 'Email supplier sudah digunakan.',
            'is_active.required' => 'Status supplier wajib dipilih.',
        ];
    }
}
