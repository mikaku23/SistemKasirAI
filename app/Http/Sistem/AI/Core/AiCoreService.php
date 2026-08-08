<?php

namespace App\Http\Sistem\AI\Core;

use App\Models\AiChannel;
use App\Models\AiConversation;
use App\Models\AiConversationMessage;
use App\Models\AiHandoff;
use App\Models\AiMessages;
use App\Models\AiPermission;
use App\Models\AiSearchLog;
use App\Models\AiKnowlegdeArticle;
use App\Models\Location;
use App\Models\Product;
use App\Models\Returns;
use App\Models\StockAdjustment;
use App\Models\StockBatches;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class AiCoreService
{
    public function __construct(
        protected AiChannelPolicy $policy,
        protected AiIntentRegistry $intents,
        protected AiKnowledgeBase $knowledgeBase,
        protected AiConversationManager $conversations,
        protected AiNotificationService $notifications,
        protected AiControllerBridge $bridge
    ) {
    }

    public function bootstrap(): void
    {
        $this->policy->channels();
        $this->bridge->syncPermissions();
    }

    public function dashboard(?User $user = null): array
    {
        $this->bootstrap();

        $channels = $this->policy->channels();
        $permissions = AiPermission::query()
            ->where('is_active', true)
            ->orderBy('module')
            ->orderBy('intent_key')
            ->get();

        $statistics = [
            'channels' => $channels->count(),
            'active_channels' => $channels->where('is_active', true)->count(),
            'permissions' => $permissions->count(),
            'conversations' => AiConversation::query()->count(),
            'messages' => AiConversationMessage::query()->count(),
            'search_logs' => AiSearchLog::query()->count(),
            'handoffs' => AiHandoff::query()->whereIn('status', ['open', 'pending', 'assigned'])->count(),
            'knowledge_articles' => AiKnowlegdeArticle::query()->where('is_active', true)->count(),
            'transactions_today' => Transaction::query()
                ->whereDate('transaction_at', now()->toDateString())
                ->where('status', 'success')
                ->count(),
            'products_active' => Product::query()->where('is_active', true)->count(),
            'stock_batches_active' => StockBatches::query()->count(),
            'stock_adjustments' => StockAdjustment::query()->count(),
            'returns' => Returns::query()->where('return_type', 'supplier')->count(),
        ];

        return [
            'channels' => $channels,
            'permissions' => $permissions,
            'statistics' => $statistics,
            'recent_conversations' => AiConversation::query()
                ->with(['aiChannel', 'user'])
                ->orderByDesc('last_activity_at')
                ->limit(8)
                ->get(),
            'recent_messages' => AiConversationMessage::query()
                ->with(['conversation.aiChannel'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(),
            'recent_search_logs' => AiSearchLog::query()
                ->with(['user', 'aiChannel', 'clickedProduct'])
                ->orderByDesc('created_at')
                ->limit(8)
                ->get(),
            'recent_handoffs' => AiHandoff::query()
                ->with(['aiConversation.aiChannel', 'assignedTo', 'user'])
                ->orderByDesc('updated_at')
                ->limit(8)
                ->get(),
            'allowed_channel_slug' => $this->policy->defaultChannelSlugForUser($user),
            'user_role' => strtolower((string) data_get($user, 'role.name', 'guest')),
        ];
    }

    public function handle(array $input, ?User $user = null): array
    {
        $this->bootstrap();

        $user ??= auth()->user();
        if (! $user) {
            return $this->blockedResponse('guest', 'Anda harus login untuk menggunakan AI Core.');
        }

        $message = trim((string) Arr::get($input, 'message', ''));
        $channelSlug = (string) Arr::get($input, 'channel_slug', $this->policy->defaultChannelSlugForUser($user));

        $channel = $this->policy->resolveChannel($channelSlug, $user);
        $authorization = $this->policy->authorize($channel, $user);

        if (! $authorization['allowed']) {
            $toast = $this->notifications->buildToast('danger', 'Akses ditolak', $authorization['reason'] ?? 'Channel tidak dapat diakses.');
            $this->notifications->flash($toast);
            return [
                'allowed' => false,
                'channel' => $channel,
                'toast' => $toast,
                'message' => $toast['message'],
                'reason' => $authorization['reason'],
            ];
        }

        if ($message === '') {
            $toast = $this->notifications->buildToast('warn', 'Pesan kosong', 'Masukkan pertanyaan atau perintah AI yang valid.');
            $this->notifications->flash($toast);

            return [
                'allowed' => false,
                'channel' => $channel,
                'toast' => $toast,
                'message' => $toast['message'],
            ];
        }

        $conversation = $this->conversations->resolve($user, $channelSlug);
        $intent = $this->intents->resolve($message, $channelSlug, $user);
        $knowledge = $this->knowledgeBase->answer($message, $channelSlug, $user);
        $guard = $this->bridge->guard($intent['module'], $intent['action'], $input['payload'] ?? [], $user);

        $userMessage = $this->conversations->logUserMessage($conversation, $channelSlug, $message, $user, [
            'intent_key' => $intent['intent_key'],
            'confidence_score' => $intent['confidence'],
            'requires_confirmation' => $guard['requires_confirmation'],
            'requires_handoff' => false,
            'channel_authorized' => true,
        ]);

        $result = match ($intent['intent_key']) {
            'admin.overview' => $this->handleAdminOverview($user, $channelSlug, $conversation, $message, $intent),
            'admin.channels.manage' => $this->handleChannelsOverview($user, $channelSlug, $conversation, $intent),
            'manager.daily.overview' => $this->handleManagerOverview($user, $channelSlug, $conversation, $intent),
            'manager.inventory.snapshot' => $this->handleInventorySnapshot($user, $channelSlug, $conversation, $intent),
            'warehouse.product.search' => $this->handleProductSearch($user, $channelSlug, $conversation, $message, $intent),
            'warehouse.batch.search' => $this->handleBatchSearch($user, $channelSlug, $conversation, $message, $intent),
            'warehouse.return.search' => $this->handleReturnSearch($user, $channelSlug, $conversation, $message, $intent),
            'warehouse.adjustment.compare' => $this->handleAdjustmentCompare($user, $channelSlug, $conversation, $message, $intent),
            'cs.transaction.help' => $this->handleTransactionHelp($user, $channelSlug, $conversation, $knowledge, $intent),
            'cs.scan.help' => $this->handleSupportHelp($user, $channelSlug, $conversation, $knowledge, $intent, 'scan'),
            'cs.print.help' => $this->handleSupportHelp($user, $channelSlug, $conversation, $knowledge, $intent, 'print'),
            'cs.system.help' => $this->handleSupportHelp($user, $channelSlug, $conversation, $knowledge, $intent, 'system'),
            default => $this->handleKnowledgeFallback($user, $channelSlug, $conversation, $knowledge, $intent),
        };

        $toast = $result['toast'] ?? $this->notifications->buildToast(
            $result['toast_variant'] ?? 'info',
            $result['toast_title'] ?? 'AI Core',
            $result['message'] ?? 'Selesai.',
            ['intent_key' => $intent['intent_key']]
        );

        $this->notifications->flash($toast);
        $this->notifications->persist($user, $conversation, $toast, [
            'source_type' => 'ai-core',
            'source_id' => data_get($userMessage, 'id'),
            'priority' => $toast['variant'] === 'danger' ? 9 : ($toast['variant'] === 'warn' ? 7 : 5),
            'target_type' => data_get($result, 'target_type'),
            'target_id' => data_get($result, 'target_id'),
            'target_url' => data_get($result, 'target_url'),
        ]);

        $this->conversations->logAiMessage($conversation, $channelSlug, (string) data_get($result, 'message', 'Selesai.'), [
            'intent_key' => $intent['intent_key'],
            'confidence_score' => $intent['confidence'],
            'status' => data_get($result, 'status', 'executed'),
            'requires_confirmation' => (bool) data_get($result, 'requires_confirmation', false),
            'requires_handoff' => (bool) data_get($result, 'requires_handoff', false),
            'tool_name' => data_get($result, 'tool_name'),
            'tool_payload' => data_get($result, 'tool_payload'),
            'tool_result' => data_get($result, 'tool_result'),
            'payload' => data_get($result, 'payload'),
        ]);

        return array_merge($result, [
            'allowed' => true,
            'channel' => $channel,
            'intent' => $intent,
            'conversation' => $conversation,
            'toast' => $toast,
            'knowledge' => $knowledge,
            'guard' => $guard,
        ]);
    }

    public function guardCrud(string $module, string $action, array $payload = [], ?User $user = null): array
    {
        $user ??= auth()->user();

        $guard = $this->bridge->guard($module, $action, $payload, $user);

        if (! $guard['allowed']) {
            return $guard;
        }

        return array_merge($guard, [
            'payload' => $this->sanitizePayload($payload),
        ]);
    }

    public function summarizeProducts(string $query = '', int $limit = 8): array
    {
        $normalized = strtolower(trim($query));

        $products = Product::query()
            ->with(['category', 'unit', 'supplier', 'location'])
            ->when($normalized !== '', function ($queryBuilder) use ($normalized): void {
                $queryBuilder->where(function ($query) use ($normalized): void {
                    $query->where('name', 'like', '%' . $normalized . '%')
                        ->orWhere('sku', 'like', '%' . $normalized . '%')
                        ->orWhere('barcode', 'like', '%' . $normalized . '%')
                        ->orWhere('search_keywords', 'like', '%' . $normalized . '%');
                });
            })
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();

        return $products->map(fn (Product $product): array => [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'stock_on_hand' => (int) $product->stock_on_hand,
            'sale_price' => (int) $product->sale_price,
            'category' => $product->category?->name,
            'unit' => $product->unit?->name,
            'supplier' => $product->supplier?->name,
            'location' => $product->location?->name,
            'expiry_status' => $product->expiry_status,
            'expiry_label' => $product->expiry_status_label,
        ])->all();
    }

    public function summarizeBatches(string $query = '', int $limit = 8): array
    {
        $normalized = strtolower(trim($query));

        $batches = StockBatches::query()
            ->with(['product', 'supplier', 'location', 'receiver'])
            ->when($normalized !== '', function ($queryBuilder) use ($normalized): void {
                $queryBuilder->where(function ($query) use ($normalized): void {
                    $query->where('batch_code', 'like', '%' . $normalized . '%')
                        ->orWhere('lot_number', 'like', '%' . $normalized . '%');
                });
            })
            ->orderByDesc('received_at')
            ->limit($limit)
            ->get();

        return $batches->map(fn (StockBatches $batch): array => [
            'id' => $batch->id,
            'batch_code' => $batch->batch_code,
            'lot_number' => $batch->lot_number,
            'product' => $batch->product?->name,
            'supplier' => $batch->supplier?->name,
            'location' => $batch->location?->name,
            'qty_received' => (int) $batch->qty_received,
            'qty_remaining' => (int) $batch->qty_remaining,
            'status' => $batch->status,
            'expiry_status' => $batch->expiry_status,
            'expiry_label' => $batch->expiry_status_label,
        ])->all();
    }

    public function summarizeReturns(string $query = '', int $limit = 8): array
    {
        $normalized = strtolower(trim($query));

        $returns = Returns::query()
            ->with(['supplier', 'location', 'user', 'items.product'])
            ->when($normalized !== '', function ($queryBuilder) use ($normalized): void {
                $queryBuilder->where(function ($query) use ($normalized): void {
                    $query->where('return_code', 'like', '%' . $normalized . '%')
                        ->orWhere('reason', 'like', '%' . $normalized . '%');
                });
            })
            ->orderByDesc('return_at')
            ->limit($limit)
            ->get();

        return $returns->map(fn (Returns $return): array => [
            'id' => $return->id,
            'return_code' => $return->return_code,
            'supplier' => $return->supplier?->name,
            'location' => $return->location?->name,
            'status' => $return->status,
            'qty_returned' => (int) $return->items->sum('quantity'),
            'total_amount' => (float) $return->total_amount,
            'reason' => $return->reason,
        ])->all();
    }

    public function summarizeAdjustments(string $query = '', int $limit = 8): array
    {
        $normalized = strtolower(trim($query));

        $adjustments = StockAdjustment::query()
            ->with(['product', 'stockBatch', 'location', 'user'])
            ->when($normalized !== '', function ($queryBuilder) use ($normalized): void {
                $queryBuilder->where(function ($query) use ($normalized): void {
                    $query->whereHas('product', function ($productQuery) use ($normalized): void {
                        $productQuery->where('name', 'like', '%' . $normalized . '%')
                            ->orWhere('sku', 'like', '%' . $normalized . '%');
                    })->orWhere('adjustment_type', 'like', '%' . $normalized . '%');
                });
            })
            ->orderByDesc('adjusted_at')
            ->limit($limit)
            ->get();

        return $adjustments->map(fn (StockAdjustment $adjustment): array => [
            'id' => $adjustment->id,
            'adjustment_code' => $adjustment->adjustment_code,
            'product' => $adjustment->product?->name,
            'location' => $adjustment->location?->name,
            'system_qty' => (float) $adjustment->system_qty,
            'physical_qty' => (float) $adjustment->physical_qty,
            'difference_qty' => (float) $adjustment->difference_qty,
            'difference_label' => $adjustment->difference_label,
            'review_status' => $adjustment->review_status,
            'review_status_label' => $adjustment->review_status_label,
        ])->all();
    }

    public function summarizeTransactions(string $query = '', int $limit = 8): array
    {
        $normalized = strtolower(trim($query));

        $transactions = Transaction::query()
            ->with(['cashier', 'location'])
            ->when($normalized !== '', function ($queryBuilder) use ($normalized): void {
                $queryBuilder->where(function ($query) use ($normalized): void {
                    $query->where('transaction_code', 'like', '%' . $normalized . '%')
                        ->orWhere('customer_name', 'like', '%' . $normalized . '%')
                        ->orWhere('payment_method', 'like', '%' . $normalized . '%');
                });
            })
            ->orderByDesc('transaction_at')
            ->limit($limit)
            ->get();

        return $transactions->map(fn (Transaction $transaction): array => [
            'id' => $transaction->id,
            'transaction_code' => $transaction->transaction_code,
            'location' => $transaction->location?->name,
            'cashier' => $transaction->cashier?->name,
            'status' => $transaction->status,
            'total_amount' => (int) $transaction->total_amount,
            'paid_amount' => (int) $transaction->paid_amount,
            'payment_method' => $transaction->payment_method_label,
            'shift' => $transaction->shift_label,
        ])->all();
    }

    protected function handleAdminOverview(User $user, string $channelSlug, AiConversation $conversation, string $message, array $intent): array
    {
        $stats = $this->dashboard($user)['statistics'];

        return [
            'status' => 'executed',
            'toast_variant' => 'success',
            'toast_title' => 'AI Core Admin',
            'message' => sprintf(
                'Admin core aktif. Channel: %d, permission aktif: %d, pesan conversation: %d, transaksi hari ini: %d.',
                $stats['channels'],
                $stats['permissions'],
                $stats['messages'],
                $stats['transactions_today']
            ),
            'tool_name' => 'dashboard_summary',
            'tool_result' => $stats,
            'payload' => [
                'summary' => $stats,
            ],
            'target_url' => route('ai-core.index'),
        ];
    }

    protected function handleChannelsOverview(User $user, string $channelSlug, AiConversation $conversation, array $intent): array
    {
        $channels = $this->policy->channels();

        return [
            'status' => 'executed',
            'toast_variant' => 'info',
            'toast_title' => 'Channel AI',
            'message' => sprintf('Terdapat %d channel aktif. Admin memegang kendali penuh, sedangkan manager, gudang, dan cashier dibatasi sesuai scope.', $channels->count()),
            'tool_name' => 'channels_overview',
            'tool_result' => $channels->map(fn (AiChannel $channel): array => [
                'name' => $channel->name,
                'slug' => $channel->slug,
                'type' => $channel->type,
                'is_active' => (bool) $channel->is_active,
                'roles' => data_get($channel->metadata, 'roles', []),
            ])->all(),
            'target_url' => route('ai-channels.index'),
        ];
    }

    protected function handleManagerOverview(User $user, string $channelSlug, AiConversation $conversation, array $intent): array
    {
        $todayTransactions = Transaction::query()
            ->whereDate('transaction_at', now()->toDateString())
            ->where('status', 'success')
            ->count();

        $todayRevenue = (int) Transaction::query()
            ->whereDate('transaction_at', now()->toDateString())
            ->where('status', 'success')
            ->sum('total_amount');

        $lowStock = Product::query()
            ->where('is_active', true)
            ->whereColumn('stock_on_hand', '<=', 'min_stock')
            ->count();

        return [
            'status' => 'executed',
            'toast_variant' => 'success',
            'toast_title' => 'Manager overview',
            'message' => sprintf(
                'Ringkasan harian: %d transaksi sukses, omzet %s, dan %d produk berada di batas stok minimum.',
                $todayTransactions,
                number_format($todayRevenue, 0, ',', '.'),
                $lowStock
            ),
            'tool_name' => 'manager_daily_summary',
            'tool_result' => compact('todayTransactions', 'todayRevenue', 'lowStock'),
        ];
    }

    protected function handleInventorySnapshot(User $user, string $channelSlug, AiConversation $conversation, array $intent): array
    {
        $products = $this->summarizeProducts('', 8);
        $batches = $this->summarizeBatches('', 8);

        return [
            'status' => 'executed',
            'toast_variant' => 'info',
            'toast_title' => 'Inventory snapshot',
            'message' => sprintf(
                'Snapshot stok siap. %d produk dan %d batch terakhir ditampilkan dalam ringkasan gudang.',
                count($products),
                count($batches)
            ),
            'tool_name' => 'inventory_snapshot',
            'tool_result' => [
                'products' => $products,
                'batches' => $batches,
            ],
        ];
    }

    protected function handleProductSearch(User $user, string $channelSlug, AiConversation $conversation, string $message, array $intent): array
    {
        $products = $this->summarizeProducts($message, 10);

        AiSearchLog::query()->create([
            'ai_channel_id' => $conversation->ai_channel_id,
            'visitor_id' => null,
            'user_id' => $user->id,
            'ai_conversation_id' => $conversation->id,
            'query_text' => $message,
            'resolved_intent' => $intent['intent_key'],
            'result_count' => count($products),
            'clicked_product_id' => $products[0]['id'] ?? null,
            'filters' => ['channel' => $channelSlug],
            'confidence_score' => $intent['confidence'],
            'metadata' => ['scope' => 'product_search'],
        ]);

        return [
            'status' => 'executed',
            'toast_variant' => 'success',
            'toast_title' => 'Pencarian produk',
            'message' => count($products) > 0
                ? sprintf('Ditemukan %d produk terkait "%s".', count($products), $message)
                : sprintf('Tidak ada produk yang cocok dengan "%s".', $message),
            'tool_name' => 'product_search',
            'tool_result' => $products,
        ];
    }

    protected function handleBatchSearch(User $user, string $channelSlug, AiConversation $conversation, string $message, array $intent): array
    {
        $batches = $this->summarizeBatches($message, 10);

        AiSearchLog::query()->create([
            'ai_channel_id' => $conversation->ai_channel_id,
            'visitor_id' => null,
            'user_id' => $user->id,
            'ai_conversation_id' => $conversation->id,
            'query_text' => $message,
            'resolved_intent' => $intent['intent_key'],
            'result_count' => count($batches),
            'clicked_product_id' => null,
            'filters' => ['channel' => $channelSlug],
            'confidence_score' => $intent['confidence'],
            'metadata' => ['scope' => 'batch_search'],
        ]);

        return [
            'status' => 'executed',
            'toast_variant' => 'info',
            'toast_title' => 'Pencarian batch',
            'message' => count($batches) > 0
                ? sprintf('Ditemukan %d batch terkait "%s".', count($batches), $message)
                : sprintf('Tidak ada batch yang cocok dengan "%s".', $message),
            'tool_name' => 'batch_search',
            'tool_result' => $batches,
        ];
    }

    protected function handleReturnSearch(User $user, string $channelSlug, AiConversation $conversation, string $message, array $intent): array
    {
        $returns = $this->summarizeReturns($message, 10);

        AiSearchLog::query()->create([
            'ai_channel_id' => $conversation->ai_channel_id,
            'visitor_id' => null,
            'user_id' => $user->id,
            'ai_conversation_id' => $conversation->id,
            'query_text' => $message,
            'resolved_intent' => $intent['intent_key'],
            'result_count' => count($returns),
            'clicked_product_id' => null,
            'filters' => ['channel' => $channelSlug],
            'confidence_score' => $intent['confidence'],
            'metadata' => ['scope' => 'return_search'],
        ]);

        return [
            'status' => 'executed',
            'toast_variant' => 'info',
            'toast_title' => 'Pencarian return',
            'message' => count($returns) > 0
                ? sprintf('Ditemukan %d return supplier terkait "%s".', count($returns), $message)
                : sprintf('Tidak ada return yang cocok dengan "%s".', $message),
            'tool_name' => 'return_search',
            'tool_result' => $returns,
        ];
    }

    protected function handleAdjustmentCompare(User $user, string $channelSlug, AiConversation $conversation, string $message, array $intent): array
    {
        $adjustments = $this->summarizeAdjustments($message, 10);

        AiSearchLog::query()->create([
            'ai_channel_id' => $conversation->ai_channel_id,
            'visitor_id' => null,
            'user_id' => $user->id,
            'ai_conversation_id' => $conversation->id,
            'query_text' => $message,
            'resolved_intent' => $intent['intent_key'],
            'result_count' => count($adjustments),
            'clicked_product_id' => null,
            'filters' => ['channel' => $channelSlug],
            'confidence_score' => $intent['confidence'],
            'metadata' => ['scope' => 'adjustment_compare'],
        ]);

        return [
            'status' => 'executed',
            'toast_variant' => 'warn',
            'toast_title' => 'Perbandingan stok',
            'message' => count($adjustments) > 0
                ? sprintf('Ditemukan %d stock adjustment untuk analisa selisih stok.', count($adjustments))
                : 'Belum ada stock adjustment yang cocok dengan pencarian ini.',
            'tool_name' => 'adjustment_compare',
            'tool_result' => $adjustments,
        ];
    }

    protected function handleTransactionHelp(User $user, string $channelSlug, AiConversation $conversation, array $knowledge, array $intent): array
    {
        return [
            'status' => 'executed',
            'toast_variant' => 'success',
            'toast_title' => 'Bantuan transaksi',
            'message' => $knowledge['answer'] ?? 'Gunakan alur transaksi standar: scan barang, hitung total, simpan, lalu print struk.',
            'tool_name' => 'transaction_help',
            'tool_result' => $knowledge,
            'target_url' => route('transactions.index'),
        ];
    }

    protected function handleSupportHelp(User $user, string $channelSlug, AiConversation $conversation, array $knowledge, array $intent, string $mode): array
    {
        return [
            'status' => 'executed',
            'toast_variant' => 'info',
            'toast_title' => strtoupper($mode) . ' support',
            'message' => $knowledge['answer'] ?? 'Baca panduan sistem atau jelaskan error yang muncul agar saya bisa memetakan langkah berikutnya.',
            'tool_name' => $mode . '_support',
            'tool_result' => $knowledge,
        ];
    }

    protected function handleKnowledgeFallback(User $user, string $channelSlug, AiConversation $conversation, array $knowledge, array $intent): array
    {
        return [
            'status' => 'executed',
            'toast_variant' => 'info',
            'toast_title' => 'Knowledge lookup',
            'message' => $knowledge['answer'] ?? 'Saya belum menemukan jawaban yang cocok.',
            'tool_name' => 'knowledge_lookup',
            'tool_result' => $knowledge,
        ];
    }

    protected function blockedResponse(string $role, string $message): array
    {
        $toast = $this->notifications->buildToast('danger', 'Akses ditolak', $message);
        $this->notifications->flash($toast);

        return [
            'allowed' => false,
            'role' => $role,
            'toast' => $toast,
            'message' => $message,
        ];
    }

    protected function sanitizePayload(array $payload): array
    {
        return collect($payload)->map(function ($value) {
            if (is_string($value)) {
                return trim($value);
            }

            if (is_array($value)) {
                return $this->sanitizePayload($value);
            }

            if ($value instanceof Carbon) {
                return $value->toDateTimeString();
            }

            return $value;
        })->toArray();
    }
}
