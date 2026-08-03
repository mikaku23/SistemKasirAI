<?php

namespace App\Services;

use App\Models\Categories;
use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProductService
{
    public function index(): Collection
    {
        return Product::with(['category', 'unit', 'supplier', 'location'])
            ->latest()
            ->get();
    }

    public function recycle(): Collection
    {
        return Product::with(['category', 'unit', 'supplier', 'location'])
            ->onlyTrashed()
            ->latest()
            ->get();
    }

    public function formOptions(): array
    {
        return [
            'categories' => Categories::where('is_active', true)->orderBy('name')->get(),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
        ];
    }

    public function store(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $payload = $this->preparePayload($data);

            return Product::create($payload);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $payload = $this->preparePayload($data, $product);

            if (!empty($payload['image']) && $product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $product->update($payload);

            return $product->refresh();
        });
    }

    public function destroy(Product $product): void
    {
        $product->delete();
    }

    public function restore(int $id): Product
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return $product->refresh();
    }

    public function forceDelete(int $id): void
    {
        $product = Product::withTrashed()->findOrFail($id);

        try {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $product->forceDelete();
        } catch (Throwable $throwable) {
            throw new \RuntimeException('Produk tidak bisa dihapus permanen karena masih memiliki relasi data atau gagal diproses.');
        }
    }

    private function preparePayload(array $data, ?Product $product = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));

        $payload = [
            'category_id' => $this->nullableInt($data['category_id'] ?? null),
            'unit_id' => $this->nullableInt($data['unit_id'] ?? null),
            'supplier_id' => $this->nullableInt($data['supplier_id'] ?? null),
            'location_id' => $this->nullableInt($data['location_id'] ?? null),
            'name' => $name,
            'slug' => $this->resolveSlug($data['slug'] ?? null, $name, $product?->id),
            'barcode' => $this->nullableString($data['barcode'] ?? null),
            'sku' => $this->nullableString($data['sku'] ?? null),
            'description' => $this->nullableString($data['description'] ?? null),
            'short_description' => $this->nullableString($data['short_description'] ?? null),
            'search_keywords' => $this->normalizeKeywords($data['search_keywords'] ?? null),
            'purchase_price' => $this->decimalValue($data['purchase_price'] ?? 0),
            'sale_price' => $this->decimalValue($data['sale_price'] ?? 0),
            'min_stock' => $this->decimalValue($data['min_stock'] ?? 0),
            'stock_on_hand' => $this->decimalValue($data['stock_on_hand'] ?? 0),
            'tracks_expiry' => (bool) ($data['tracks_expiry'] ?? false),
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'is_available_online' => (bool) ($data['is_available_online'] ?? false),
            'popularity_score' => $this->decimalValue($data['popularity_score'] ?? 0),
            'last_sold_at' => $this->nullableDateTime($data['last_sold_at'] ?? null),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];

        if (!empty($data['image']) && is_object($data['image'])) {
            $payload['image'] = $data['image']->store('products', 'public');
        }

        return $payload;
    }

    private function resolveSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name);
        $candidate = $base ?: 'produk';

        $index = 1;
        while (
            Product::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $base . '-' . $index;
            $index++;
        }

        return $candidate;
    }

    private function normalizeKeywords(mixed $keywords): ?array
    {
        if ($keywords === null || $keywords === '') {
            return null;
        }

        if (is_array($keywords)) {
            return array_values(array_filter(array_map(static fn ($item) => trim((string) $item), $keywords)));
        }

        $items = preg_split('/[\r\n,;]+/', (string) $keywords) ?: [];

        return array_values(array_filter(array_map(static fn ($item) => trim($item), $items)));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === '' || $value === null ? null : (string) $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === '0') {
            return $value === '0' ? 0 : null;
        }

        return (int) $value;
    }

    private function decimalValue(mixed $value): string
    {
        $normalized = str_replace(',', '.', (string) $value);

        if ($normalized === '') {
            $normalized = '0';
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private function nullableDateTime(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === '' || $value === null ? null : (string) $value;
    }
}
