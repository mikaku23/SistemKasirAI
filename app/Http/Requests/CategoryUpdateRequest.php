<?php

namespace App\Http\Requests;

use App\Models\Categories;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = trim((string) $this->input('name'));
        $slugInput = trim((string) $this->input('slug'));
        $skuInput = strtoupper(trim((string) $this->input('sku')));

        $this->merge([
            'name' => $name,
            'slug' => Str::slug($slugInput !== '' ? $slugInput : $name),
            'sku' => preg_replace('/[^A-Z0-9\-]/', '', $skuInput) ?: null,
            'description' => trim((string) $this->input('description')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $category = $this->route('category');
        $categoryId = $category instanceof Categories ? $category->id : $category;

        return [
            'id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:120',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'sku' => [
                'required',
                'string',
                'max:30',
                Rule::unique('categories', 'sku')->ignore($categoryId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'slug.required' => 'Slug kategori wajib diisi.',
            'slug.unique' => 'Slug kategori sudah digunakan.',
            'sku.required' => 'SKU kategori wajib diisi.',
            'sku.unique' => 'SKU kategori sudah digunakan.',
            'is_active.required' => 'Status kategori wajib dipilih.',
        ];
    }
}
