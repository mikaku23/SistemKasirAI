<?php

namespace App\Http\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductPromoSettingService
{
    public function indexData(): array
    {
        return [
            'promoProducts' => $this->promoProducts(),
            'promoStats' => $this->stats(),
        ];
    }

    public function promoProducts(): Collection
    {
        return Product::query()
            ->with(['category', 'unit'])
            ->orderByDesc('promo_discount_is_active')
            ->orderBy('name')
            ->get();
    }

    public function stats(): array
    {
        return [
            'total' => Product::query()->count(),
            'active' => Product::query()->where('promo_discount_is_active', true)->count(),
            'inactive' => Product::query()->where('promo_discount_is_active', false)->count(),
            'with_discount' => Product::query()->where('promo_discount_amount', '>', 0)->count(),
        ];
    }

    public function store(array $data): Product
    {
        $product = Product::query()->findOrFail((int) $data['product_id']);
        $payload = $this->normalizePayload($data);
        $payload['promo_discount_amount'] = min((int) $product->sale_price, (int) $payload['promo_discount_amount']);

        $product->fill($payload);
        $product->save();

        return $product->refresh()->load(['category', 'unit']);
    }

    public function update(Product $product, array $data): Product
    {
        $payload = $this->normalizePayload($data);
        $payload['promo_discount_amount'] = min((int) $product->sale_price, (int) $payload['promo_discount_amount']);

        $product->fill($payload);
        $product->save();

        return $product->refresh()->load(['category', 'unit']);
    }

    public function payload(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'sale_price' => (int) $product->sale_price,
            'promo_discount_amount' => (int) $product->promo_discount_amount,
            'promo_discount_is_active' => $product->promo_discount_is_active ? 1 : 0,
            'effective_discount_amount' => (int) $product->effective_discount_amount,
            'effective_price' => max(0, (int) $product->sale_price - (int) $product->effective_discount_amount),
        ];
    }

    protected function normalizePayload(array $data): array
    {
        return [
            'promo_discount_amount' => max(0, (int) ($data['promo_discount_amount'] ?? 0)),
            'promo_discount_is_active' => $this->bool($data['promo_discount_is_active'] ?? false),
        ];
    }

    protected function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }
}
