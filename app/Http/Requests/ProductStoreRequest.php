<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class ProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = trim((string) $this->input('name'));
        $slugInput = trim((string) $this->input('slug'));

        $tracksExpiry = $this->has('tracks_expiry') ? $this->boolean('tracks_expiry') : true;
        $expiryType = strtolower(trim((string) $this->input('expiry_type', 'none')));

        if (! $tracksExpiry) {
            $expiryType = 'none';
        }

        $this->merge([
            'category_id' => $this->filled('category_id') ? (int) $this->input('category_id') : null,
            'unit_id' => $this->filled('unit_id') ? (int) $this->input('unit_id') : null,
            'supplier_id' => $this->filled('supplier_id') ? (int) $this->input('supplier_id') : null,
            'location_id' => $this->filled('location_id') ? (int) $this->input('location_id') : null,
            'name' => $name,
            'slug' => Str::slug($slugInput !== '' ? $slugInput : $name),
            'barcode' => trim((string) $this->input('barcode')),
            'sku' => trim((string) $this->input('sku')),
            'description' => trim((string) $this->input('description')),
            'short_description' => trim((string) $this->input('short_description')),
            'search_keywords' => trim((string) $this->input('search_keywords')),
            'purchase_price' => $this->normalizeNumber($this->input('purchase_price')),
            'sale_price' => $this->normalizeNumber($this->input('sale_price')),
            'min_stock' => $this->normalizeInteger($this->input('min_stock')),
            'stock_on_hand' => $this->normalizeInteger($this->input('stock_on_hand')),
            'tracks_expiry' => $tracksExpiry,
            'expiry_type' => $expiryType,
            'production_date' => $this->normalizeDate($this->input('production_date')),
            'expired_at' => $this->normalizeDate($this->input('expired_at')),
            'shelf_life_days' => $this->normalizeInteger($this->input('shelf_life_days')),
            'expiry_warning_days' => $this->normalizeInteger($this->input('expiry_warning_days'), 30),
            'expiry_grace_days' => $this->normalizeInteger($this->input('expiry_grace_days'), 0),
            'is_featured' => $this->boolean('is_featured'),
            'is_available_online' => $this->boolean('is_available_online'),
            'popularity_score' => $this->normalizeNumber($this->input('popularity_score')),
            'last_sold_at' => $this->filled('last_sold_at')
                ? Carbon::parse(trim((string) $this->input('last_sold_at')))->format('Y-m-d H:i:s')
                : null,
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
        ]);
    }

    public function rules(): array
    {
        $expiryTypeRules = $this->boolean('tracks_expiry')
            ? ['required', Rule::in(['none', 'fixed_date', 'shelf_life'])]
            : ['nullable', Rule::in(['none', 'fixed_date', 'shelf_life'])];

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'string', 'max:180', Rule::unique('products', 'slug')],
            'barcode' => ['nullable', 'string', 'max:32', Rule::unique('products', 'barcode')],
            'sku' => ['nullable', 'string', 'max:64', Rule::unique('products', 'sku')],
            'description' => ['nullable', 'string', 'max:5000'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:4096'],
            'search_keywords' => ['nullable', 'string', 'max:5000'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'stock_on_hand' => ['nullable', 'integer', 'min:0'],
            'tracks_expiry' => ['required', 'boolean'],
            'expiry_type' => $expiryTypeRules,
            'production_date' => ['nullable', 'date', 'required_if:expiry_type,shelf_life'],
            'expired_at' => ['nullable', 'date', 'required_if:expiry_type,fixed_date'],
            'shelf_life_days' => ['nullable', 'integer', 'min:1', 'required_if:expiry_type,shelf_life'],
            'expiry_warning_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'expiry_grace_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'is_featured' => ['required', 'boolean'],
            'is_available_online' => ['required', 'boolean'],
            'popularity_score' => ['nullable', 'numeric', 'min:0'],
            'last_sold_at' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori produk wajib dipilih.',
            'category_id.exists' => 'Kategori produk tidak valid.',
            'unit_id.required' => 'Unit produk wajib dipilih.',
            'unit_id.exists' => 'Unit produk tidak valid.',
            'supplier_id.required' => 'Supplier produk wajib dipilih.',
            'location_id.required' => 'Location produk wajib dipilih.',
            'name.required' => 'Nama produk wajib diisi.',
            'slug.required' => 'Slug produk wajib diisi.',
            'slug.unique' => 'Slug produk sudah digunakan.',
            'barcode.unique' => 'Barcode produk sudah digunakan.',
            'sku.unique' => 'SKU produk sudah digunakan.',
            'image.file' => 'File gambar produk tidak valid.',
            'image.mimes' => 'Gambar produk harus berupa jpg, jpeg, png, webp, gif, atau svg.',
            'image.max' => 'Ukuran gambar produk maksimal 4 MB.',
            'tracks_expiry.required' => 'Status expiry wajib dipilih.',
            'expiry_type.required_if' => 'Tipe expiry wajib dipilih jika tracking expiry aktif.',
            'production_date.required_if' => 'Tanggal produksi wajib diisi untuk expiry type shelf life.',
            'expired_at.required_if' => 'Tanggal expired wajib diisi untuk expiry type fixed date.',
            'shelf_life_days.required_if' => 'Masa simpan wajib diisi untuk expiry type shelf life.',
            'is_featured.required' => 'Status featured wajib dipilih.',
            'is_available_online.required' => 'Status online wajib dipilih.',
            'is_active.required' => 'Status produk wajib dipilih.',
        ];
    }

    protected function normalizeNumber(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^0-9\.\-]/', '', $value) ?? '';

        return $value === '' ? null : $value;
    }

    protected function normalizeInteger(mixed $value, ?int $default = null): ?int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $default;
        }

        return (int) preg_replace('/[^0-9\-]/', '', $value);
    }

    protected function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
