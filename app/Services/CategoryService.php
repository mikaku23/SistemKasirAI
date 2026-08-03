<?php

namespace App\Services;

use App\Models\Categories;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CategoryService
{
    public function index(): Collection
    {
        return Categories::latest()->get();
    }

    public function recycle(): Collection
    {
        return Categories::onlyTrashed()->latest()->get();
    }

    public function store(array $data): Categories
    {
        $payload = $this->preparePayload($data);

        return Categories::create($payload);
    }

    public function update(Categories $category, array $data): Categories
    {
        $payload = $this->preparePayload($data, $category);

        $category->update($payload);

        return $category->refresh();
    }

    public function destroy(Categories $category): void
    {
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
        $category = Categories::withTrashed()->findOrFail($id);

        if ($category->products()->exists()) {
            throw new DomainException('Kategori tidak bisa dihapus permanen karena masih dipakai oleh produk.');
        }

        $category->forceDelete();
    }

    private function preparePayload(array $data, ?Categories $category = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));

        return [
            'name' => $name,
            'slug' => $this->resolveSlug($data['slug'] ?? null, $name, $category?->id),
            'description' => $this->nullableString($data['description'] ?? null),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }

    private function resolveSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name);
        $candidate = $base ?: 'kategori';

        $index = 1;
        while (
            Categories::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $base . '-' . $index;
            $index++;
        }

        return $candidate;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === '' || $value === null ? null : (string) $value;
    }
}
