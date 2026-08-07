<?php

namespace App\Http\Services;


use App\Support\AuditTrail;
use App\Models\Categories;
use App\Models\Location;
use App\Models\Product;
use App\Models\Product_keyword;
use App\Models\StockBatches;
use App\Models\Supplier;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductService
{
    use AuditTrail;

    public function indexData(): array
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        return [
            'products' => $this->activeProducts(),
            'trashedProducts' => $this->trashedProducts(),
            'productStats' => $this->stats(),
        ];
    }

    public function referenceData(): array
    {
        return [
            'categories' => Categories::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('name')->get(),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'locations' => Location::query()->orderBy('name')->get(),
        ];
    }

    public function activeProducts(): Collection
    {
        return Product::query()
            ->with(['category', 'unit', 'supplier', 'location'])
            ->orderByDesc('updated_at')
            ->get();
    }

    public function trashedProducts(): Collection
    {
        return Product::onlyTrashed()
            ->with(['category', 'unit', 'supplier', 'location'])
            ->orderByDesc('deleted_at')
            ->get();
    }

    public function stats(?Collection $products = null): array
    {
        $products ??= $this->activeProducts();

        return [
            'total' => Product::query()->count(),
            'active' => Product::query()->where('is_active', true)->count(),
            'featured' => Product::query()->where('is_featured', true)->count(),
            'tracked_expiry' => Product::query()->where('tracks_expiry', true)->count(),
            'expiring_soon' => $products->filter(function (Product $product): bool {
                return in_array($product->expiry_status, ['expiring_soon', 'expires_today', 'grace_period'], true);
            })->count(),
            'expired' => $products->filter(fn (Product $product): bool => $product->expiry_status === 'expired')->count(),
            'trashed' => Product::onlyTrashed()->count(),
        ];
    }

    public function store(array $data, ?UploadedFile $imageFile = null): Product
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $payload = $this->normalizePayload($data);
        $storedImagePath = null;

        try {
            if ($imageFile !== null) {
                $storedImagePath = $this->storeImage($imageFile);
                $payload['image'] = $storedImagePath;
            }

            return DB::transaction(function () use ($payload) {
                $keywords = $payload['search_keywords'];
                unset($payload['search_keywords']);

                $payload['slug'] = $this->uniqueSlug($payload['slug']);
                $payload['stock_on_hand'] = null;
                $payload['tracks_expiry'] = true;
                $payload['expiry_type'] = 'none';
                $payload['production_date'] = null;
                $payload['expired_at'] = null;
                $payload['expiry_snapshots'] = null;

                $product = Product::create($payload);
                $this->syncKeywords($product, $keywords);
                $this->refreshFinancialSnapshot($product->id);

                return $product->refresh()->load(['category', 'unit', 'supplier', 'location']);
            });
        } catch (Throwable $throwable) {
            if ($storedImagePath !== null) {
                $this->deleteImage($storedImagePath);
            }

            throw $throwable;
        }
    }

    public function update(Product $product, array $data, ?UploadedFile $imageFile = null): Product
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $payload = $this->normalizePayload($data, $product->id);
        $payload['stock_on_hand'] = $this->refreshStockSnapshot($product->id) ?: null;
        $expirySnapshots = $this->refreshExpirySnapshot($product->id);
        $payload['expiry_snapshots'] = $expirySnapshots ?: null;

        if (! empty($expirySnapshots)) {
            $topSnapshot = $expirySnapshots[0];
            $payload['tracks_expiry'] = true;
            $payload['expired_at'] = $topSnapshot['resolved_expiry_at'] ?? $topSnapshot['expired_at'] ?? null;
            $payload['production_date'] = $topSnapshot['production_date'] ?? null;
            $payload['shelf_life_days'] = $topSnapshot['shelf_life_days'] ?? $payload['shelf_life_days'];
            $payload['expiry_warning_days'] = $topSnapshot['expiry_warning_days'] ?? $payload['expiry_warning_days'];
            $payload['expiry_grace_days'] = $topSnapshot['expiry_grace_days'] ?? $payload['expiry_grace_days'];
        }
        $oldImagePath = $product->image;
        $storedImagePath = null;

        try {
            if ($imageFile !== null) {
                $storedImagePath = $this->storeImage($imageFile);
                $payload['image'] = $storedImagePath;
            } else {
                $payload['image'] = $oldImagePath;
            }

            $updatedProduct = DB::transaction(function () use ($product, $payload) {
                $keywords = $payload['search_keywords'];
                unset($payload['search_keywords']);

                $payload['slug'] = $this->uniqueSlug($payload['slug'], $product->id);

                $product->fill($payload);
                $product->save();

                $this->syncKeywords($product, $keywords);
                $this->refreshFinancialSnapshot($product->id);

                return $product->refresh()->load(['category', 'unit', 'supplier', 'location']);
            });

            if ($storedImagePath !== null && $oldImagePath && $oldImagePath !== $storedImagePath) {
                $this->deleteImage($oldImagePath);
            }

            return $updatedProduct;
        } catch (Throwable $throwable) {
            if ($storedImagePath !== null) {
                $this->deleteImage($storedImagePath);
            }

            throw $throwable;
        }
    }

    public function trash(Product $product): void
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $this->deleteKeywords($product);
        $product->delete();
    }
public function restore(int $id): Product
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        $this->syncKeywords($product, $this->normalizeKeywords($product->search_keywords));
        $this->refreshStockSnapshot($product->id);
        $this->refreshExpirySnapshot($product->id);
        $this->refreshFinancialSnapshot($product->id);

        return $product->refresh()->load(['category', 'unit', 'supplier', 'location']);
    }

    public function forceDelete(int $id): void
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $product = Product::onlyTrashed()->findOrFail($id);

        $this->deleteKeywords($product);
        $this->deleteImage($product->image);

        $product->forceDelete();
    }

    public function payload(Product $product): array
    {
        return [
            'id' => $product->id,
            'category_id' => $product->category_id,
            'unit_id' => $product->unit_id,
            'supplier_id' => $product->supplier_id,
            'location_id' => $product->location_id,
            'name' => $product->name,
            'slug' => $product->slug,
            'barcode' => $product->barcode,
            'sku' => $product->sku,
            'description' => $product->description,
            'short_description' => $product->short_description,
            'image' => $product->image,
            'image_url' => $product->image ? Storage::disk('public')->url($product->image) : null,
            'search_keywords' => $product->search_keywords ?? [],
            'search_keywords_text' => collect($product->search_keywords ?? [])->implode(', '),
            'purchase_price' => $product->purchase_price,
            'sale_price' => $product->sale_price,
            'min_stock' => $product->min_stock,
            'stock_on_hand' => $product->stock_on_hand,
            'tracks_expiry' => $product->tracks_expiry ? 1 : 0,
            'expiry_type' => $product->expiry_type,
            'expiry_type_label' => $product->expiry_type_label,
            'production_date' => optional($product->production_date)->format('Y-m-d'),
            'expired_at' => optional($product->expired_at)->format('Y-m-d'),
            'shelf_life_days' => $product->shelf_life_days,
            'expiry_warning_days' => $product->expiry_warning_days,
            'expiry_grace_days' => $product->expiry_grace_days,
            'resolved_expiry_at' => $product->resolved_expiry_at,
            'expiry_snapshot_count' => $product->expiry_snapshot_count,
            'expiry_snapshot_items' => $product->expiry_snapshot_items,
            'expiry_snapshot_top' => $product->expiry_snapshot_top,
            'expiry_status' => $product->expiry_status,
            'expiry_status_label' => $product->expiry_status_label,
            'expiry_status_class' => $product->expiry_status_class,
            'expiry_summary' => $product->expiry_summary,
            'is_featured' => $product->is_featured ? 1 : 0,
            'is_available_online' => $product->is_available_online ? 1 : 0,
            'popularity_score' => $product->popularity_score,
            'last_sold_at' => optional($product->last_sold_at)->format('Y-m-d\TH:i'),
            'is_active' => $product->is_active ? 1 : 0,
            'updated_at' => optional($product->updated_at)->format('d M Y H:i'),
            'deleted_at' => optional($product->deleted_at)->format('d M Y H:i'),
        ];
    }

    protected function syncKeywords(Product $product, array $keywords): void
    {
        Product_keyword::query()
            ->where('product_id', $product->id)
            ->delete();

        foreach ($keywords as $index => $keyword) {
            Product_keyword::create([
                'product_id' => $product->id,
                'keyword' => $keyword,
                'weight' => max(1, 100 - ($index * 10)),
                'is_auto_generated' => true,
            ]);
        }

        $product->forceFill([
            'search_keywords' => $keywords,
        ])->saveQuietly();
    }

    protected function deleteKeywords(Product $product): void
    {
        Product_keyword::query()
            ->where('product_id', $product->id)
            ->delete();
    }

    protected function storeImage(UploadedFile $file): string
    {
        return $file->store('product', 'public');
    }

    protected function deleteImage(?string $path): void
    {
        if (!$path) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    public function refreshStockSnapshot(int $productId): int
    {
        $stockOnHand = (int) StockBatches::query()
            ->where('product_id', $productId)
            ->whereNull('deleted_at')
            ->sum('qty_remaining');

        Product::query()
            ->whereKey($productId)
            ->update([
                'stock_on_hand' => $stockOnHand > 0 ? $stockOnHand : null,
            ]);

        $this->refreshFinancialSnapshot($productId);

        return $stockOnHand;
    }

    public function refreshFinancialSnapshot(int $productId): array
    {
        $product = Product::query()->find($productId);

        if (! $product) {
            return [];
        }

        $batches = StockBatches::query()
            ->where('product_id', $productId)
            ->whereNull('deleted_at')
            ->get();

        $purchaseTotal = (int) $batches->sum(function (StockBatches $batch): int {
            return (int) round(((float) $batch->purchase_price) * max(0, (int) $batch->qty_received));
        });

        $stockOnHand = (int) $batches->sum('qty_remaining');
        $salePrice = (int) $product->sale_price;
        $expectedRevenueTotal = (int) $batches->sum(function (StockBatches $batch) use ($salePrice): int {
            return (int) round(((float) $salePrice) * max(0, (int) $batch->qty_received));
        });

        $expectedProfitTotal = $expectedRevenueTotal - $purchaseTotal;
        $realizedRevenueTotal = (int) $batches->sum(function (StockBatches $batch): int {
            return (int) data_get($batch->metadata, 'financial_snapshot.sold_revenue_total', 0);
        });
        $realizedCogsTotal = (int) $batches->sum(function (StockBatches $batch): int {
            return (int) data_get($batch->metadata, 'financial_snapshot.sold_cogs_total', 0);
        });
        $realizedProfitTotal = $realizedRevenueTotal - $realizedCogsTotal;

        Product::query()
            ->whereKey($productId)
            ->update([
                'purchase_price' => $purchaseTotal,
                'stock_on_hand' => $stockOnHand > 0 ? $stockOnHand : null,
            ]);

        return [
            'purchase_total' => $purchaseTotal,
            'stock_on_hand' => $stockOnHand,
            'expected_revenue_total' => $expectedRevenueTotal,
            'expected_profit_total' => $expectedProfitTotal,
            'realized_revenue_total' => $realizedRevenueTotal,
            'realized_cogs_total' => $realizedCogsTotal,
            'realized_profit_total' => $realizedProfitTotal,
        ];
    }

public function refreshExpirySnapshot(int $productId): array
{
    $batches = StockBatches::query()
        ->with(['receiver'])
        ->where('product_id', $productId)
        ->whereNull('deleted_at')
        ->where('qty_remaining', '>', 0)
        ->orderByRaw("CASE WHEN expired_at IS NULL THEN 1 ELSE 0 END")
        ->orderBy('expired_at')
        ->orderBy('received_at')
        ->orderBy('id')
        ->get()
        ->filter(function (StockBatches $batch): bool {
            return $this->hasTrackedExpiryData($batch);
        })
        ->values();

    $snapshots = $batches->map(function (StockBatches $batch): array {
        $status = $batch->expiry_status;

        return [
            'batch_id' => $batch->id,
            'batch_code' => $batch->batch_code,
            'lot_number' => $batch->lot_number,
            'qty_received' => (int) $batch->qty_received,
            'qty_remaining' => (int) $batch->qty_remaining,
            'production_date' => optional($batch->production_date)->format('Y-m-d'),
            'expired_at' => optional($batch->expired_at)->format('Y-m-d'),
            'resolved_expiry_at' => $batch->resolved_expiry_at,
            'expiry_warning_days' => (int) data_get($batch->metadata, 'expiry_warning_days', 30),
            'expiry_grace_days' => (int) data_get($batch->metadata, 'expiry_grace_days', 0),
            'shelf_life_days' => data_get($batch->metadata, 'shelf_life_days'),
            'expiry_mode' => data_get($batch->metadata, 'expiry_mode', 'none'),
            'added_at' => optional($batch->received_at)->format('Y-m-d'),
            'added_at_label' => optional($batch->received_at)->format('d M Y'),
            'added_by_id' => $batch->received_by,
            'added_by_name' => optional($batch->receiver)->name,
            'expiry_days_left' => $batch->expiry_days_left,
            'expiry_status' => $status,
            'expiry_status_label' => $batch->expiry_status_label,
            'expiry_status_class' => $batch->expiry_status_class,
            'expiry_summary' => $batch->expiry_summary,
            'priority_rank' => $this->expirySnapshotPriority($status),
            'status' => $batch->status,
            'status_label' => $batch->status_label,
            'notes' => $batch->notes,
            'source_label' => data_get($batch->metadata, 'source_label', 'Penerimaan batch'),
        ];
    })->sort(function (array $left, array $right): int {
        $leftKey = [
            (int) ($left['priority_rank'] ?? 99),
            ($left['expiry_days_left'] ?? null) === null ? PHP_INT_MAX : (int) $left['expiry_days_left'],
            ($left['added_at'] ?? null) !== null ? -(int) strtotime((string) $left['added_at']) : PHP_INT_MAX,
            (int) ($left['batch_id'] ?? 0),
        ];

        $rightKey = [
            (int) ($right['priority_rank'] ?? 99),
            ($right['expiry_days_left'] ?? null) === null ? PHP_INT_MAX : (int) $right['expiry_days_left'],
            ($right['added_at'] ?? null) !== null ? -(int) strtotime((string) $right['added_at']) : PHP_INT_MAX,
            (int) ($right['batch_id'] ?? 0),
        ];

        return $leftKey <=> $rightKey;
    })->values()->all();

    $topSnapshot = $snapshots[0] ?? null;

    Product::query()
        ->whereKey($productId)
        ->update([
            'tracks_expiry' => true,
            'expiry_snapshots' => $snapshots ?: null,
            'expired_at' => $topSnapshot['resolved_expiry_at'] ?? $topSnapshot['expired_at'] ?? null,
            'production_date' => $topSnapshot['production_date'] ?? null,
            'shelf_life_days' => $topSnapshot['shelf_life_days'] ?? null,
            'expiry_warning_days' => $topSnapshot['expiry_warning_days'] ?? 30,
            'expiry_grace_days' => $topSnapshot['expiry_grace_days'] ?? 0,
        ]);

    return $snapshots;
}


protected function hasTrackedExpiryData(StockBatches $batch): bool
{
    if (filled($batch->production_date) || filled($batch->expired_at)) {
        return true;
    }

    return data_get($batch->metadata, 'expiry_mode', 'none') !== 'none';
}

protected function expirySnapshotPriority(string $status): int
{
    return match ($status) {
        'grace_period' => 0,
        'expired' => 1,
        'expires_today' => 2,
        'expiring_soon' => 3,
        'active' => 4,
        'depleted' => 5,
        'no_tracking' => 6,
        default => 7,
    };
}

    protected function normalizePayload(array $data, ?int $ignoreId = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $slugInput = trim((string) ($data['slug'] ?? ''));
        $description = array_key_exists('description', $data) ? trim((string) $data['description']) : null;
        $shortDescription = array_key_exists('short_description', $data) ? trim((string) $data['short_description']) : null;
        $keywords = $this->normalizeKeywords($data['search_keywords'] ?? null);
        $lastSoldAt = $this->normalizeDateTime($data['last_sold_at'] ?? null);

        $category = Categories::query()->find($this->nullableInt($data['category_id'] ?? null));
        if (!$category) {
            throw ValidationException::withMessages([
                'category_id' => 'Kategori produk tidak valid.',
            ]);
        }

        if (blank($category->sku)) {
            throw ValidationException::withMessages([
                'category_id' => 'SKU kategori belum diisi. Lengkapi SKU kategori terlebih dahulu sebelum membuat produk.',
            ]);
        }

        $sku = trim((string) ($data['sku'] ?? ''));
        if ($sku === '') {
            $sku = $this->generateSku($category, $name, $ignoreId);
        } else {
            $sku = $this->uniqueSku($sku, $ignoreId);
        }

        $barcode = trim((string) ($data['barcode'] ?? ''));
        if ($barcode === '') {
            $barcode = $this->generateBarcode($category, $name, $ignoreId);
        } else {
            $barcode = $this->normalizeBarcode($barcode);
            $barcode = $this->uniqueBarcode($barcode, $ignoreId);
        }

        return [
            'category_id' => $this->nullableInt($data['category_id'] ?? null),
            'unit_id' => $this->nullableInt($data['unit_id'] ?? null),
            'supplier_id' => $this->nullableInt($data['supplier_id'] ?? null),
            'location_id' => $this->nullableInt($data['location_id'] ?? null),
            'name' => $name,
            'slug' => Str::slug($slugInput !== '' ? $slugInput : $name),
            'barcode' => $barcode,
            'sku' => $sku,
            'description' => $this->nullableString($description),
            'short_description' => $this->nullableString($shortDescription),
            'search_keywords' => $keywords,
            'purchase_price' => $this->normalizeDecimal($data['purchase_price'] ?? null),
            'sale_price' => $this->normalizeDecimal($data['sale_price'] ?? null),
            'min_stock' => $this->normalizeInteger($data['min_stock'] ?? null),
            'stock_on_hand' => $this->normalizeInteger($data['stock_on_hand'] ?? null),
            'tracks_expiry' => $this->booleanValue($data['tracks_expiry'] ?? true),
            'expiry_type' => $this->normalizeExpiryType($data['expiry_type'] ?? null, $this->booleanValue($data['tracks_expiry'] ?? true)),
            'production_date' => $this->normalizeDate($data['production_date'] ?? null),
            'expired_at' => $this->normalizeDate($data['expired_at'] ?? null),
            'shelf_life_days' => $this->normalizeInteger($data['shelf_life_days'] ?? null),
            'expiry_warning_days' => $this->normalizeInteger($data['expiry_warning_days'] ?? 30, 30),
            'expiry_grace_days' => $this->normalizeInteger($data['expiry_grace_days'] ?? 0, 0),
            'is_featured' => $this->booleanValue($data['is_featured'] ?? false),
            'is_available_online' => $this->booleanValue($data['is_available_online'] ?? false),
            'popularity_score' => $this->normalizeDecimal($data['popularity_score'] ?? null),
            'last_sold_at' => $lastSoldAt,
            'is_active' => $this->booleanValue($data['is_active'] ?? true),
        ];
    }

    protected function generateSku(Categories $category, string $name, ?int $ignoreId = null): string
    {
        $categoryCode = $this->normalizeCodeSegment((string) $category->sku, 'CAT');
        $nameCode = $this->generateProductNameCode($name);
        $randomCode = Str::upper(Str::random(4));

        $base = implode('-', array_values(array_filter([$categoryCode, $nameCode, $randomCode])));
        return $this->uniqueSku($base, $ignoreId);
    }

    protected function generateBarcode(Categories $category, string $name, ?int $ignoreId = null): string
    {
        $seed = implode('|', [
            $this->normalizeCodeSegment((string) $category->sku, 'CAT'),
            $name,
            (string) microtime(true),
            Str::random(8),
        ]);

        $candidate = $this->ean13FromSeed($seed);
        $attempt = 0;

        while ($this->barcodeExists($candidate, $ignoreId)) {
            $candidate = $this->ean13FromSeed($seed . '|' . $attempt . '|' . Str::random(8));
            $attempt++;
        }

        return $candidate;
    }

    protected function uniqueSku(string $sku, ?int $ignoreId = null): string
    {
        $baseSku = strtoupper(preg_replace('/[^A-Z0-9\-]/', '', $sku)) ?: 'SKU';
        $candidate = $baseSku;
        $counter = 2;

        while ($this->skuExists($candidate, $ignoreId)) {
            $candidate = $baseSku . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    protected function uniqueBarcode(string $barcode, ?int $ignoreId = null): string
    {
        $candidate = $this->normalizeBarcode($barcode);
        $counter = 2;

        while ($this->barcodeExists($candidate, $ignoreId)) {
            $candidate = $this->normalizeBarcode($barcode . $counter);
            $counter++;
        }

        return $candidate;
    }

    protected function skuExists(string $sku, ?int $ignoreId = null): bool
    {
        return Product::withTrashed()
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('sku', $sku)
            ->exists();
    }

    protected function barcodeExists(string $barcode, ?int $ignoreId = null): bool
    {
        return Product::withTrashed()
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('barcode', $barcode)
            ->exists();
    }

    protected function normalizeCodeSegment(string $value, string $fallback): string
    {
        $value = strtoupper(preg_replace('/[^A-Z0-9]/', '', $value) ?? '');
        return $value !== '' ? $value : $fallback;
    }

    protected function generateProductNameCode(string $name): string
    {
        $map = [
            'BERAS' => 'BRS',
            'PREMIUM' => 'PREM',
            'POKOK' => 'POK',
            'GULA' => 'GLA',
            'MINYAK' => 'MYK',
            'TEPUNG' => 'TPG',
            'TELUR' => 'TLR',
            'AYAM' => 'AYM',
            'SUSU' => 'SSU',
            'DAPUR' => 'DPR',
            'MIE' => 'MIE',
            'INSTANT' => 'INST',
            'INSTAN' => 'INST',
        ];

        $tokens = preg_split('/[^a-zA-Z0-9]+/', strtoupper($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $segments = [];

        foreach ($tokens as $token) {
            if (isset($map[$token])) {
                $segments[] = $map[$token];
                continue;
            }

            if (preg_match('/^\d+$/', $token)) {
                $segments[] = $token;
                continue;
            }

            $segments[] = $this->abbreviateWord($token);
        }

        $segments = array_values(array_filter(array_unique($segments), static fn ($segment) => $segment !== ''));
        if ($segments === []) {
            return 'PRD';
        }

        return implode('-', array_slice($segments, 0, 3));
    }

    protected function abbreviateWord(string $word): string
    {
        $word = strtoupper(preg_replace('/[^A-Z0-9]/', '', $word) ?? '');
        if ($word === '') {
            return '';
        }

        if (strlen($word) <= 4) {
            return $word;
        }

        $abbr = substr($word, 0, 1) . preg_replace('/[AEIOU]/', '', substr($word, 1));
        $abbr = substr($abbr, 0, 4);

        return strtoupper($abbr);
    }

    protected function ean13FromSeed(string $seed): string
    {
        $hash = sprintf('%u', crc32($seed));
        $base12 = substr(str_pad($hash, 12, '0', STR_PAD_LEFT), 0, 12);
        $checkDigit = $this->ean13CheckDigit($base12);

        return $base12 . $checkDigit;
    }

    protected function ean13CheckDigit(string $base12): int
    {
        $digits = preg_replace('/\D/', '', $base12) ?? '';
        $digits = substr(str_pad($digits, 12, '0', STR_PAD_LEFT), 0, 12);

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $digits[$i];
            $sum += ($i % 2 === 0) ? $digit : ($digit * 3);
        }

        return (10 - ($sum % 10)) % 10;
    }

    protected function normalizeBarcode(string $barcode): string
    {
        $barcode = preg_replace('/\D/', '', $barcode) ?? '';

        if ($barcode === '') {
            $barcode = '200000000000';
        }

        $base12 = substr(str_pad($barcode, 12, '0', STR_PAD_RIGHT), 0, 12);
        return $base12 . $this->ean13CheckDigit($base12);
    }

    protected function normalizeKeywords(mixed $value): array
    {
        if (is_array($value)) {
            $items = $value;
        } else {
            $items = preg_split('/[\r\n,;]+/', (string) $value) ?: [];
        }

        $items = array_map(static fn ($item) => trim((string) $item), $items);
        $items = array_filter($items, static fn ($item) => $item !== '');

        return array_values(array_unique($items));
    }

    protected function normalizeDateTime(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    protected function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    protected function normalizeInteger(mixed $value, ?int $default = null): ?int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $default;
        }

        $value = preg_replace('/[^0-9\-]/', '', $value) ?? '';

        return $value === '' ? $default : (int) $value;
    }

    protected function normalizeExpiryType(mixed $value, bool $tracksExpiry): string
    {
        $type = strtolower(trim((string) $value));

        if (! $tracksExpiry) {
            return 'none';
        }

        return in_array($type, ['none', 'fixed_date', 'shelf_life'], true) ? $type : 'none';
    }

    protected function normalizeDecimal(mixed $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^0-9\.\-]/', '', $value) ?? '';

        return $value === '' ? null : (float) $value;
    }

    protected function nullableInt(mixed $value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) $value;
    }

    protected function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
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
        $baseSlug = Str::slug($slug) ?: 'product';
        $candidate = $baseSlug;
        $counter = 2;

        while (
            Product::withTrashed()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }
}
