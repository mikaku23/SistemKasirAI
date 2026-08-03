<?php

namespace App\Http\Services;

use App\Models\Categories;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function indexData(): array
    {
        return [
            'categories' => $this->activeCategories(),
            'trashedCategories' => $this->trashedCategories(),
            'categoriesStats' => $this->stats(),
        ];
    }

    public function activeCategories(): Collection
    {
        return Categories::query()
            ->orderByDesc('updated_at')
            ->get();
    }

    public function trashedCategories(): Collection
    {
        return Categories::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->get();
    }

    public function stats(): array
    {
        return [
            'total' => Categories::query()->count(),
            'active' => Categories::query()->where('is_active', true)->count(),
            'inactive' => Categories::query()->where('is_active', false)->count(),
            'trashed' => Categories::onlyTrashed()->count(),
        ];
    }

    public function store(array $data): Categories
    {
        $payload = $this->normalizePayload($data);

        return DB::transaction(function () use ($payload) {
            $payload['slug'] = $this->uniqueSlug($payload['slug']);
            $payload['sku'] = $this->uniqueSku($payload['sku']);

            return Categories::create($payload);
        });
    }

    public function update(Categories $category, array $data): Categories
    {
        $payload = $this->normalizePayload($data);

        return DB::transaction(function () use ($category, $payload) {
            $payload['slug'] = $this->uniqueSlug($payload['slug'], $category->id);
            $payload['sku'] = $this->uniqueSku($payload['sku'], $category->id);

            $category->fill($payload);
            $category->save();

            return $category->refresh();
        });
    }

    public function trash(Categories $category): void
    {
        if ($category->products()->whereNull('deleted_at')->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Kategori ini masih dipakai oleh produk aktif. Pindahkan produk ke kategori lain sebelum menghapus.',
            ]);
        }

        $category->delete();
    }

    public function restore(int $id): Categories
    {
        $category = Categories::onlyTrashed()->findOrFail($id);
        $category->restore();

        return $category->refresh();
    }

    public function forceDelete(int $id): void
    {
        $category = Categories::onlyTrashed()->findOrFail($id);

        if ($category->products()->whereNull('deleted_at')->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Kategori ini masih dipakai oleh produk aktif. Pindahkan produk ke kategori lain sebelum penghapusan permanen.',
            ]);
        }

        $category->forceDelete();
    }

    public function payload(Categories $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'sku' => $category->sku,
            'description' => $category->description,
            'is_active' => $category->is_active ? 1 : 0,
            'updated_at' => optional($category->updated_at)->format('d M Y H:i'),
            'deleted_at' => optional($category->deleted_at)->format('d M Y H:i'),
        ];
    }

    protected function normalizePayload(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $slugInput = trim((string) ($data['slug'] ?? ''));
        $skuInput = strtoupper(trim((string) ($data['sku'] ?? '')));
        $description = array_key_exists('description', $data) ? trim((string) $data['description']) : null;

        return [
            'name' => $name,
            'slug' => Str::slug($slugInput !== '' ? $slugInput : $name),
            'sku' => preg_replace('/[^A-Z0-9\-]/', '', $skuInput) ?: null,
            'description' => $description !== '' ? $description : null,
            'is_active' => $this->booleanValue($data['is_active'] ?? false),
        ];
    }

    protected function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    protected function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slug) ?: 'kategori';
        $candidate = $baseSlug;
        $counter = 2;

        while (
            Categories::withTrashed()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    protected function uniqueSku(string $sku, ?int $ignoreId = null): string
    {
        $baseSku = strtoupper(preg_replace('/[^A-Z0-9\-]/', '', $sku)) ?: 'CAT';
        $candidate = $baseSku;
        $counter = 2;

        while (
            Categories::withTrashed()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('sku', $candidate)
                ->exists()
        ) {
            $candidate = $baseSku . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }
}
