<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
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
            'last_login_at' => trim((string) $this->input('last_login_at')),
            'last_password_changed_at' => trim((string) $this->input('last_password_changed_at')),
        ]);

        if (Schema::hasColumn('users', 'is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:120', Rule::unique('users', 'username')],
            'email' => ['nullable', 'email', 'max:190', Rule::unique('users', 'email')],
            'no_hp' => ['required', 'string', 'max:25'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'security_question' => ['nullable', 'string', 'max:255'],
            'security_answer' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'last_login_at' => ['nullable', 'date'],
            'last_password_changed_at' => ['nullable', 'date'],
        ];

        if (Schema::hasColumn('users', 'is_active')) {
            $rules['is_active'] = ['required', 'boolean'];
        }

        return $rules;
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
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'avatar.image' => 'Avatar harus berupa gambar.',
            'avatar.mimes' => 'Avatar harus jpg, jpeg, png, atau webp.',
        ];
    }
}
