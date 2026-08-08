<?php

namespace App\Http\Sistem\AI\Core;

use App\Http\Controllers\AiCoreController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockBatchController;
use App\Http\Controllers\SupplierReturnController;
use App\Http\Controllers\TransactionController;
use App\Models\AiPermission;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AiControllerBridge
{
    public function syncPermissions(): Collection
    {
        foreach ($this->blueprints() as $blueprint) {
            AiPermission::query()->updateOrCreate(
                ['intent_key' => $blueprint['intent_key']],
                $blueprint
            );
        }

        return AiPermission::query()
            ->orderBy('module')
            ->orderBy('intent_key')
            ->get();
    }

    public function guard(string $module, string $action, array $payload = [], ?User $user = null): array
    {
        $intentKey = strtolower(trim($module . '.' . $action));
        $permission = AiPermission::query()->where('intent_key', $intentKey)->first()
            ?? AiPermission::query()->where('intent_key', 'crud.' . $intentKey)->first();

        $normalizedPayload = $this->normalizePayload($payload);

        if (! $permission) {
            return [
                'allowed' => false,
                'requires_confirmation' => true,
                'reason' => 'Blueprint AI belum terdaftar untuk intent ini.',
                'intent_key' => $intentKey,
                'payload' => $normalizedPayload,
                'permission' => null,
            ];
        }

        $role = strtolower((string) data_get($user, 'role.slug', data_get($user, 'role.name', 'guest')));
        $isAdmin = $role === 'admin';
        $isReadAction = in_array($action, ['index', 'show', 'search', 'list', 'view'], true);
        $writeRequested = ! $isReadAction;

        if ($isAdmin) {
            return [
                'allowed' => true,
                'requires_confirmation' => (bool) $permission->requires_confirmation,
                'reason' => null,
                'intent_key' => $intentKey,
                'payload' => $normalizedPayload,
                'permission' => $permission,
            ];
        }

        if ($permission->can_read && $isReadAction) {
            return [
                'allowed' => true,
                'requires_confirmation' => false,
                'reason' => null,
                'intent_key' => $intentKey,
                'payload' => $normalizedPayload,
                'permission' => $permission,
            ];
        }

        return [
            'allowed' => false,
            'requires_confirmation' => true,
            'reason' => $writeRequested
                ? 'Aksi tulis hanya bisa dijalankan oleh Admin.'
                : 'Intent ini dibatasi untuk Admin.',
            'intent_key' => $intentKey,
            'payload' => $normalizedPayload,
            'permission' => $permission,
        ];
    }

    public function resolveRoute(string $intentKey): ?array
    {
        $permission = AiPermission::query()->where('intent_key', $intentKey)->first();

        if (! $permission) {
            return null;
        }

        return [
            'intent_key' => $permission->intent_key,
            'controller_class' => $permission->controller_class,
            'controller_method' => $permission->controller_method,
            'module' => $permission->module,
            'can_read' => (bool) $permission->can_read,
            'can_write' => (bool) $permission->can_write,
            'requires_confirmation' => (bool) $permission->requires_confirmation,
            'is_active' => (bool) $permission->is_active,
            'description' => $permission->description,
            'metadata' => $permission->metadata ?? [],
        ];
    }

    protected function blueprints(): array
    {
        return [
            [
                'intent_key' => 'admin.overview',
                'controller_class' => AiCoreController::class,
                'controller_method' => 'index',
                'module' => 'ai-core',
                'can_read' => true,
                'can_write' => false,
                'requires_confirmation' => false,
                'is_active' => true,
                'description' => 'Dashboard AI Core admin.',
                'metadata' => ['route' => 'ai-core.index'],
            ],
            [
                'intent_key' => 'admin.channels.manage',
                'controller_class' => AiCoreController::class,
                'controller_method' => 'channels',
                'module' => 'ai-channels',
                'can_read' => true,
                'can_write' => false,
                'requires_confirmation' => false,
                'is_active' => true,
                'description' => 'Manajemen channel AI.',
                'metadata' => ['route' => 'ai-channels.index'],
            ],
            [
                'intent_key' => 'manager.daily.overview',
                'controller_class' => AiCoreController::class,
                'controller_method' => 'managerChatbot',
                'module' => 'manager-chatbot',
                'can_read' => true,
                'can_write' => false,
                'requires_confirmation' => false,
                'is_active' => true,
                'description' => 'Ringkasan harian manager.',
                'metadata' => ['route' => 'ai-core.chatbot'],
            ],
            [
                'intent_key' => 'manager.inventory.snapshot',
                'controller_class' => StockBatchController::class,
                'controller_method' => 'index',
                'module' => 'stock-batches',
                'can_read' => true,
                'can_write' => false,
                'requires_confirmation' => false,
                'is_active' => true,
                'description' => 'Snapshot stok untuk manager dan gudang.',
                'metadata' => ['route' => 'stock-batches.index'],
            ],
            [
                'intent_key' => 'warehouse.product.search',
                'controller_class' => ProductController::class,
                'controller_method' => 'index',
                'module' => 'products',
                'can_read' => true,
                'can_write' => false,
                'requires_confirmation' => false,
                'is_active' => true,
                'description' => 'Pencarian produk gudang.',
                'metadata' => ['route' => 'products.index'],
            ],
            [
                'intent_key' => 'warehouse.batch.search',
                'controller_class' => StockBatchController::class,
                'controller_method' => 'index',
                'module' => 'stock-batches',
                'can_read' => true,
                'can_write' => false,
                'requires_confirmation' => false,
                'is_active' => true,
                'description' => 'Pencarian batch gudang.',
                'metadata' => ['route' => 'stock-batches.index'],
            ],
            [
                'intent_key' => 'warehouse.return.search',
                'controller_class' => SupplierReturnController::class,
                'controller_method' => 'index',
                'module' => 'supplier-returns',
                'can_read' => true,
                'can_write' => false,
                'requires_confirmation' => false,
                'is_active' => true,
                'description' => 'Pencarian return supplier.',
                'metadata' => ['route' => 'supplier-returns.index'],
            ],
            [
                'intent_key' => 'warehouse.adjustment.compare',
                'controller_class' => StockAdjustmentController::class,
                'controller_method' => 'index',
                'module' => 'stock-adjustments',
                'can_read' => true,
                'can_write' => false,
                'requires_confirmation' => false,
                'is_active' => true,
                'description' => 'Perbandingan stok sistem dan fisik.',
                'metadata' => ['route' => 'stock-adjustments.index'],
            ],
            [
                'intent_key' => 'cs.transaction.help',
                'controller_class' => TransactionController::class,
                'controller_method' => 'index',
                'module' => 'transactions',
                'can_read' => true,
                'can_write' => false,
                'requires_confirmation' => false,
                'is_active' => true,
                'description' => 'Bantuan transaksi cashier.',
                'metadata' => ['route' => 'transactions.index'],
            ],
            [
                'intent_key' => 'cs.scan.help',
                'controller_class' => AiCoreController::class,
                'controller_method' => 'customerService',
                'module' => 'customer-service',
                'can_read' => true,
                'can_write' => false,
                'requires_confirmation' => false,
                'is_active' => true,
                'description' => 'Bantuan scan QR/barcode.',
                'metadata' => ['route' => 'ai-core.customer-service'],
            ],
            [
                'intent_key' => 'cs.print.help',
                'controller_class' => AiCoreController::class,
                'controller_method' => 'customerService',
                'module' => 'customer-service',
                'can_read' => true,
                'can_write' => false,
                'requires_confirmation' => false,
                'is_active' => true,
                'description' => 'Bantuan print struk/barcode.',
                'metadata' => ['route' => 'ai-core.customer-service'],
            ],
            [
                'intent_key' => 'crud.products.index',
                'controller_class' => ProductController::class,
                'controller_method' => 'index',
                'module' => 'products',
                'can_read' => true,
                'can_write' => false,
                'requires_confirmation' => false,
                'is_active' => true,
                'description' => 'Guard CRUD products index.',
                'metadata' => ['route' => 'products.index'],
            ],
            [
                'intent_key' => 'crud.products.store',
                'controller_class' => ProductController::class,
                'controller_method' => 'store',
                'module' => 'products',
                'can_read' => false,
                'can_write' => true,
                'requires_confirmation' => true,
                'is_active' => true,
                'description' => 'Guard CRUD products store.',
                'metadata' => ['route' => 'products.store'],
            ],
            [
                'intent_key' => 'crud.products.update',
                'controller_class' => ProductController::class,
                'controller_method' => 'update',
                'module' => 'products',
                'can_read' => false,
                'can_write' => true,
                'requires_confirmation' => true,
                'is_active' => true,
                'description' => 'Guard CRUD products update.',
                'metadata' => ['route' => 'products.update'],
            ],
            [
                'intent_key' => 'crud.products.destroy',
                'controller_class' => ProductController::class,
                'controller_method' => 'destroy',
                'module' => 'products',
                'can_read' => false,
                'can_write' => true,
                'requires_confirmation' => true,
                'is_active' => true,
                'description' => 'Guard CRUD products destroy.',
                'metadata' => ['route' => 'products.destroy'],
            ],
            [
                'intent_key' => 'crud.stock-batches.store',
                'controller_class' => StockBatchController::class,
                'controller_method' => 'store',
                'module' => 'stock-batches',
                'can_read' => false,
                'can_write' => true,
                'requires_confirmation' => true,
                'is_active' => true,
                'description' => 'Guard CRUD stock batches store.',
                'metadata' => ['route' => 'stock-batches.store'],
            ],
            [
                'intent_key' => 'crud.stock-batches.update',
                'controller_class' => StockBatchController::class,
                'controller_method' => 'update',
                'module' => 'stock-batches',
                'can_read' => false,
                'can_write' => true,
                'requires_confirmation' => true,
                'is_active' => true,
                'description' => 'Guard CRUD stock batches update.',
                'metadata' => ['route' => 'stock-batches.update'],
            ],
            [
                'intent_key' => 'crud.stock-batches.destroy',
                'controller_class' => StockBatchController::class,
                'controller_method' => 'destroy',
                'module' => 'stock-batches',
                'can_read' => false,
                'can_write' => true,
                'requires_confirmation' => true,
                'is_active' => true,
                'description' => 'Guard CRUD stock batches destroy.',
                'metadata' => ['route' => 'stock-batches.destroy'],
            ],
            [
                'intent_key' => 'crud.supplier-returns.store',
                'controller_class' => SupplierReturnController::class,
                'controller_method' => 'store',
                'module' => 'supplier-returns',
                'can_read' => false,
                'can_write' => true,
                'requires_confirmation' => true,
                'is_active' => true,
                'description' => 'Guard CRUD supplier returns store.',
                'metadata' => ['route' => 'supplier-returns.store'],
            ],
            [
                'intent_key' => 'crud.transactions.store',
                'controller_class' => TransactionController::class,
                'controller_method' => 'store',
                'module' => 'transactions',
                'can_read' => false,
                'can_write' => true,
                'requires_confirmation' => true,
                'is_active' => true,
                'description' => 'Guard CRUD transactions store.',
                'metadata' => ['route' => 'transactions.store'],
            ],
        ];
    }

    protected function normalizePayload(array $payload): array
    {
        return collect($payload)->map(function ($value) {
            if (is_string($value)) {
                return trim($value);
            }

            if (is_array($value)) {
                return $this->normalizePayload($value);
            }

            return $value;
        })->toArray();
    }
}
