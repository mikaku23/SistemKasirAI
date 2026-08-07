<?php

namespace App\Http\Services;


use App\Support\AuditTrail;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductPromoSettingService
{
    use AuditTrail;

    public function indexData(): array
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $this->syncExpiredPromos();

        return [
            'promoProducts' => $this->promoProducts(),
            'promoStats' => $this->stats(),
        ];
    }

    public function referenceData(): array
    {
        $this->syncExpiredPromos();

        return [
            'products' => Product::query()
                ->with(['category', 'unit'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ];
    }

    public function promoProducts(): Collection
    {
        $this->syncExpiredPromos();

        return Product::query()
            ->with(['category', 'unit'])
            ->where(function ($query) {
                $query->where('promo_discount_amount', '>', 0)
                    ->orWhere('promo_discount_is_active', true)
                    ->orWhereNotNull('promo_starts_at')
                    ->orWhereNotNull('promo_ends_at');
            })
            ->get()
            ->sortBy(function (Product $product) {
                return sprintf(
                    '%02d|%s|%s',
                    $this->statusRank($product),
                    strtolower((string) $product->name),
                    str_pad((string) $product->id, 10, '0', STR_PAD_LEFT)
                );
            })
            ->values();
    }

    public function stats(): array
    {
        $products = Product::query()
            ->with(['category', 'unit'])
            ->where(function ($query) {
                $query->where('promo_discount_amount', '>', 0)
                    ->orWhere('promo_discount_is_active', true)
                    ->orWhereNotNull('promo_starts_at')
                    ->orWhereNotNull('promo_ends_at');
            })
            ->get();

        return [
            'total' => $products->count(),
            'active' => $products->where('promo_status', 'active')->count(),
            'scheduled' => $products->where('promo_status', 'scheduled')->count(),
            'expired' => $products->where('promo_status', 'expired')->count(),
            'inactive' => $products->where('promo_status', 'inactive')->count(),
        ];
    }

    public function store(array $data): Product
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $product = Product::query()->findOrFail((int) $data['product_id']);
        $payload = $this->normalizePayload($data);
        $payload['promo_discount_amount'] = min((int) $product->sale_price, (int) $payload['promo_discount_amount']);

        return DB::transaction(function () use ($product, $payload) {
            $product->fill($payload);
            $product->save();

            $this->syncExpiredPromos();

            return $product->refresh()->load(['category', 'unit']);
        });
    }

    public function update(Product $product, array $data): Product
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $payload = $this->normalizePayload($data);
        $payload['promo_discount_amount'] = min((int) $product->sale_price, (int) $payload['promo_discount_amount']);

        return DB::transaction(function () use ($product, $payload) {
            $product->fill($payload);
            $product->save();

            $this->syncExpiredPromos();

            return $product->refresh()->load(['category', 'unit']);
        });
    }

    public function reset(Product $product): Product
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        return DB::transaction(function () use ($product) {
            $product->forceFill([
                'promo_discount_amount' => 0,
                'promo_discount_is_active' => false,
                'promo_starts_at' => null,
                'promo_ends_at' => null,
                'promo_metadata' => null,
            ])->save();

            return $product->refresh()->load(['category', 'unit']);
        });
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
            'promo_starts_at' => optional($product->promo_starts_at)->format('Y-m-d\TH:i'),
            'promo_ends_at' => optional($product->promo_ends_at)->format('Y-m-d\TH:i'),
            'promo_status' => $product->promo_status,
            'promo_status_label' => $product->promo_status_label,
            'promo_status_class' => $product->promo_status_class,
            'promo_period_label' => $product->promo_period_label,
            'promo_remaining_days' => $product->promo_remaining_days,
            'effective_discount_amount' => (int) $product->effective_discount_amount,
            'promo_effective_price' => (int) $product->promo_effective_price,
        ];
    }

    public function syncExpiredPromos(?Carbon $at = null): int
    {
        $at ??= now();

        $expiredProducts = Product::query()
            ->where('promo_discount_is_active', true)
            ->where('promo_discount_amount', '>', 0)
            ->whereNotNull('promo_ends_at')
            ->where('promo_ends_at', '<', $at)
            ->get();

        foreach ($expiredProducts as $product) {
            $product->forceFill([
                'promo_discount_is_active' => false,
                'promo_metadata' => array_merge($product->promo_metadata ?? [], [
                    'auto_inactivated_at' => $at->toDateTimeString(),
                    'auto_inactivated_reason' => 'promo_period_ended',
                ]),
            ])->saveQuietly();
        }

        return $expiredProducts->count();
    }

    protected function normalizePayload(array $data): array
    {
        return [
            'promo_discount_amount' => max(0, (int) ($data['promo_discount_amount'] ?? 0)),
            'promo_discount_is_active' => $this->bool($data['promo_discount_is_active'] ?? false),
            'promo_starts_at' => $this->normalizeDateTime($data['promo_starts_at'] ?? null) ?? now(),
            'promo_ends_at' => $this->normalizeDateTime($data['promo_ends_at'] ?? null),
            'promo_metadata' => [
                'managed_by' => 'promo_setting_module',
                'rule_type' => 'product_promo_window',
            ],
        ];
    }

    protected function normalizeDateTime(mixed $value): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    protected function statusRank(Product $product): int
    {
        return match ($product->promo_status) {
            'active' => 0,
            'scheduled' => 1,
            'expired' => 2,
            default => 3,
        };
    }
}
