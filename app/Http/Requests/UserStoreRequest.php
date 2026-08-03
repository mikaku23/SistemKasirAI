<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'role_id' => $this->input('role_id'),
            'location_id' => $this->input('location_id'),
            'name' => trim((string) $this->input('name')),
            'username' => trim((string) $this->input('username')),
            'email' => $this->input('email') !== null ? strtolower(trim((string) $this->input('email'))) : null,
            'nim' => trim((string) $this->input('nim')),
            'nip' => trim((string) $this->input('nip')),
            'no_hp' => trim((string) $this->input('no_hp')),
            'security_question' => trim((string) $this->input('security_question')),
            'security_answer' => trim((string) $this->input('security_answer')),
            'qr_code' => trim((string) $this->input('qr_code')),
            'qr_url' => trim((string) $this->input('qr_url')),
            'email_verified_at' => trim((string) $this->input('email_verified_at')),
            'last_login_at' => trim((string) $this->input('last_login_at')),
            'last_password_changed_at' => trim((string) $this->input('last_password_changed_at')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:120', Rule::unique('users', 'username')],
            'email' => ['nullable', 'email', 'max:190', Rule::unique('users', 'email')],
            'nim' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nim')],
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nip')],
            'no_hp' => ['required', 'string', 'max:25'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'security_question' => ['nullable', 'string', 'max:255'],
            'security_answer' => ['nullable', 'string', 'max:255'],
            'qr_code' => ['nullable', 'string', 'max:255'],
            'qr_url' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'email_verified_at' => ['nullable', 'date'],
            'last_login_at' => ['nullable', 'date'],
            'last_password_changed_at' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'role_id.required' => 'Role user wajib dipilih.',
            'role_id.exists' => 'Role user tidak valid.',
            'name.required' => 'Nama user wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'email.unique' => 'Email sudah digunakan.',
            'nim.unique' => 'NIM sudah digunakan.',
            'nip.unique' => 'NIP sudah digunakan.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'avatar.image' => 'Avatar harus berupa gambar.',
            'avatar.mimes' => 'Avatar harus jpg, jpeg, png, atau webp.',
            'is_active.required' => 'Status user wajib dipilih.',
        ];
    }
}
