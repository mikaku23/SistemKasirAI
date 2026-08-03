<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unitId = $this->route('unit');

        if ($unitId instanceof Unit) {
            $unitId = $unitId->id;
        }

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('units', 'name')->ignore($unitId)],
            'symbol' => ['required', 'string', 'max:25', Rule::unique('units', 'symbol')->ignore($unitId)],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
