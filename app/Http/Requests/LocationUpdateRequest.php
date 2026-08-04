<?php

namespace App\Http\Requests;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocationUpdateRequest extends FormRequest
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
            'address' => trim((string) $this->input('address')),
            'phone' => trim((string) $this->input('phone')),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
        ]);
    }

    public function rules(): array
    {
        $location = $this->route('location');
        $locationId = $location instanceof Location ? $location->id : $location;

        return [
            'id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'nullable',
                'string',
                'max:60',
                Rule::unique('locations', 'code')->ignore($locationId),
            ],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama location wajib diisi.',
            'code.unique' => 'Code location sudah digunakan.',
            'is_active.required' => 'Status location wajib dipilih.',
        ];
    }
}
