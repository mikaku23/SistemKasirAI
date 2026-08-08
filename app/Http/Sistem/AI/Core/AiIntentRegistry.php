<?php

namespace App\Http\Sistem\AI\Core;

use App\Models\User;

class AiIntentRegistry
{
    public function resolve(string $query, string $channelSlug, ?User $user = null): array
    {
        $normalized = $this->normalize($query);
        $tokens = $this->tokens($normalized);

        $definitions = $this->definitions();

        $best = [
            'intent_key' => 'knowledge.lookup',
            'title' => 'Knowledge lookup',
            'module' => 'knowledge',
            'action' => 'search',
            'confidence' => 0.25,
            'requires_confirmation' => false,
            'route_hint' => null,
            'description' => 'Fallback knowledge search.',
            'keywords' => [],
        ];

        foreach ($definitions as $definition) {
            if (! $this->appliesToChannel($definition, $channelSlug, $user)) {
                continue;
            }

            $score = $this->score($normalized, $tokens, $definition['keywords']);

            if ($score > $best['confidence']) {
                $best = array_merge($best, $definition, [
                    'confidence' => min(0.99, $score),
                ]);
            }
        }

        return $best;
    }

    public function definitions(): array
    {
        return [
            [
                'intent_key' => 'admin.overview',
                'title' => 'Admin overview',
                'module' => 'ai-core',
                'action' => 'read',
                'requires_confirmation' => false,
                'route_hint' => 'ai-core.index',
                'description' => 'Ringkasan pusat AI untuk admin.',
                'keywords' => ['dashboard', 'overview', 'ringkasan', 'status sistem', 'system status', 'monitoring', 'admin core', 'ai core'],
                'channels' => ['admin-core'],
            ],
            [
                'intent_key' => 'admin.channels.manage',
                'title' => 'Channel management',
                'module' => 'ai-channels',
                'action' => 'read',
                'requires_confirmation' => false,
                'route_hint' => 'ai-channels.index',
                'description' => 'Manajemen channel AI dan guardrail.',
                'keywords' => ['channel', 'channels', 'role channel', 'konfigurasi ai', 'manage ai'],
                'channels' => ['admin-core'],
            ],
            [
                'intent_key' => 'manager.daily.overview',
                'title' => 'Manager overview',
                'module' => 'dashboard',
                'action' => 'read',
                'requires_confirmation' => false,
                'route_hint' => null,
                'description' => 'Ringkasan harian untuk manager.',
                'keywords' => ['harian', 'daily', 'ringkasan penjualan', 'ringkasan stok', 'rekap', 'insight', 'manager'],
                'channels' => ['manager-chatbot'],
            ],
            [
                'intent_key' => 'manager.inventory.snapshot',
                'title' => 'Inventory snapshot',
                'module' => 'stock-batches',
                'action' => 'read',
                'requires_confirmation' => false,
                'route_hint' => 'stock-batches.index',
                'description' => 'Snapshot stok untuk manager.',
                'keywords' => ['stok', 'inventory', 'stock', 'barang', 'persediaan', 'produk habis', 'low stock'],
                'channels' => ['manager-chatbot', 'warehouse-search'],
            ],
            [
                'intent_key' => 'warehouse.product.search',
                'title' => 'Product search',
                'module' => 'products',
                'action' => 'search',
                'requires_confirmation' => false,
                'route_hint' => 'products.index',
                'description' => 'Pencarian produk untuk gudang.',
                'keywords' => ['cari produk', 'produk', 'sku', 'barcode', 'nama barang', 'item', 'product'],
                'channels' => ['warehouse-search'],
            ],
            [
                'intent_key' => 'warehouse.batch.search',
                'title' => 'Batch search',
                'module' => 'stock-batches',
                'action' => 'search',
                'requires_confirmation' => false,
                'route_hint' => 'stock-batches.index',
                'description' => 'Pencarian batch dan lot gudang.',
                'keywords' => ['batch', 'lot', 'expired', 'kedaluwarsa', 'tanggal terima', 'qty remaining', 'qty sisa'],
                'channels' => ['warehouse-search'],
            ],
            [
                'intent_key' => 'warehouse.return.search',
                'title' => 'Return search',
                'module' => 'supplier-returns',
                'action' => 'search',
                'requires_confirmation' => false,
                'route_hint' => 'supplier-returns.index',
                'description' => 'Pencarian dan ringkasan return supplier.',
                'keywords' => ['return', 'retur', 'supplier return', 'kembali ke supplier', 'pengembalian'],
                'channels' => ['warehouse-search'],
            ],
            [
                'intent_key' => 'warehouse.adjustment.compare',
                'title' => 'Adjustment compare',
                'module' => 'stock-adjustments',
                'action' => 'search',
                'requires_confirmation' => false,
                'route_hint' => 'stock-adjustments.index',
                'description' => 'Perbandingan stok sistem dan stok fisik.',
                'keywords' => ['stock adjustment', 'adjustment', 'selisih stok', 'bandingkan stok', 'stok fisik', 'stok sistem', 'stock opname'],
                'channels' => ['warehouse-search'],
            ],
            [
                'intent_key' => 'cs.transaction.help',
                'title' => 'Transaction help',
                'module' => 'transactions',
                'action' => 'read',
                'requires_confirmation' => false,
                'route_hint' => 'transactions.index',
                'description' => 'Bantuan transaksi untuk cashier dan gudang.',
                'keywords' => ['transaksi', 'receipt', 'struk', 'bayar', 'total', 'payment', 'checkout'],
                'channels' => ['customer-service'],
            ],
            [
                'intent_key' => 'cs.scan.help',
                'title' => 'Scan help',
                'module' => 'transactions',
                'action' => 'read',
                'requires_confirmation' => false,
                'route_hint' => 'transactions.index',
                'description' => 'Bantuan scan QR/barcode pada transaksi.',
                'keywords' => ['scan', 'qr', 'barcode', 'kamera', 'pindai', 'scanner'],
                'channels' => ['customer-service'],
            ],
            [
                'intent_key' => 'cs.print.help',
                'title' => 'Print help',
                'module' => 'transactions',
                'action' => 'read',
                'requires_confirmation' => false,
                'route_hint' => 'transactions.index',
                'description' => 'Bantuan print struk atau barcode.',
                'keywords' => ['print', 'cetak', 'struk', 'barcode print', 'pdf'],
                'channels' => ['customer-service'],
            ],
            [
                'intent_key' => 'cs.system.help',
                'title' => 'System help',
                'module' => 'ai-core',
                'action' => 'read',
                'requires_confirmation' => false,
                'route_hint' => null,
                'description' => 'Bantuan penggunaan sistem kasir management.',
                'keywords' => ['error', 'login', 'password', 'rol', 'middleware', 'route', 'controller', 'request', 'blade'],
                'channels' => ['customer-service'],
            ],
        ];
    }

    protected function appliesToChannel(array $definition, string $channelSlug, ?User $user = null): bool
    {
        $channels = $definition['channels'] ?? ['admin-core'];

        if (in_array($channelSlug, $channels, true)) {
            return true;
        }

        return $user !== null && strtolower((string) data_get($user, 'role.name')) === 'admin' && in_array('admin-core', $channels, true);
    }

    protected function score(string $normalized, array $tokens, array $keywords): float
    {
        if ($normalized === '') {
            return 0.25;
        }

        $score = 0.25;
        foreach ($keywords as $keyword) {
            $keyword = $this->normalize($keyword);
            if ($keyword === '') {
                continue;
            }

            if (str_contains($normalized, $keyword)) {
                $score += 0.32;
                continue;
            }

            $keywordTokens = $this->tokens($keyword);
            $hits = count(array_intersect($tokens, $keywordTokens));
            if ($hits > 0) {
                $score += min(0.2, $hits * 0.06);
            }
        }

        return min(0.99, $score);
    }

    protected function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9\s]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    protected function tokens(string $value): array
    {
        if ($value === '') {
            return [];
        }

        return collect(explode(' ', $value))
            ->map(fn (string $token): string => trim($token))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
