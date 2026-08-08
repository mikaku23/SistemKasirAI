<?php
namespace App\Http\Sistem\AI\Core;

use App\Models\AiPermission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

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
        $isReadAction = in_array($action, ['index', 'show', 'search', 'list', 'view', 'create', 'edit', 'recycle', 'lookupBarcode', 'print'], true);
        $allowedRoles = array_map(
            static fn ($allowedRole) => strtolower((string) $allowedRole),
            (array) data_get($permission, 'metadata.allowed_roles', [])
        );

        if (! $isAdmin) {
            if ($allowedRoles !== [] && ! in_array($role, $allowedRoles, true)) {
                return [
                    'allowed' => false,
                    'requires_confirmation' => true,
                    'reason' => 'Role tidak memiliki akses ke intent ini.',
                    'intent_key' => $intentKey,
                    'payload' => $normalizedPayload,
                    'permission' => $permission,
                ];
            }
        }

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

        if ($isReadAction && $permission->can_read) {
            return [
                'allowed' => true,
                'requires_confirmation' => false,
                'reason' => null,
                'intent_key' => $intentKey,
                'payload' => $normalizedPayload,
                'permission' => $permission,
            ];
        }

        if (! $isReadAction && $permission->can_write) {
            return [
                'allowed' => true,
                'requires_confirmation' => (bool) $permission->requires_confirmation,
                'reason' => null,
                'intent_key' => $intentKey,
                'payload' => $normalizedPayload,
                'permission' => $permission,
            ];
        }

        return [
            'allowed' => false,
            'requires_confirmation' => true,
            'reason' => $permission->can_write
                ? 'Aksi tulis hanya bisa dijalankan sesuai pembatasan role.'
                : 'Intent ini dibatasi untuk role tertentu.',
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
        return array_merge(
            [
                [
                    'intent_key' => 'admin.overview',
                    'controller_class' => '\\App\\Http\\Controllers\\AiCoreController::class',
                    'controller_method' => 'index',
                    'module' => 'ai-core',
                    'can_read' => 'true',
                    'can_write' => 'false',
                    'requires_confirmation' => 'false',
                    'is_active' => 'true',
                    'description' => 'Dashboard AI Core admin.',
                    'route' => 'ai-core.index',
                    'metadata' => ['route' => 'ai-core.index', 'allowed_roles' => ['admin']],
                ],
                [
                    'intent_key' => 'admin.channels.manage',
                    'controller_class' => '\\App\\Http\\Controllers\\AiCoreController::class',
                    'controller_method' => 'channels',
                    'module' => 'ai-channels',
                    'can_read' => 'true',
                    'can_write' => 'false',
                    'requires_confirmation' => 'false',
                    'is_active' => 'true',
                    'description' => 'Manajemen channel AI.',
                    'route' => 'ai-channels.index',
                    'metadata' => ['route' => 'ai-channels.index', 'allowed_roles' => ['admin']],
                ],
                [
                    'intent_key' => 'manager.daily.overview',
                    'controller_class' => '\\App\\Http\\Controllers\\AiCoreController::class',
                    'controller_method' => 'chatbot',
                    'module' => 'manager-chatbot',
                    'can_read' => 'true',
                    'can_write' => 'false',
                    'requires_confirmation' => 'false',
                    'is_active' => 'true',
                    'description' => 'Ringkasan harian manager.',
                    'route' => 'ai-core.chatbot',
                    'metadata' => ['route' => 'ai-core.chatbot', 'allowed_roles' => ['admin', 'manager']],
                ],
                [
                    'intent_key' => 'manager.inventory.snapshot',
                    'controller_class' => '\\App\\Http\\Controllers\\StockBatchController::class',
                    'controller_method' => 'index',
                    'module' => 'stock-batches',
                    'can_read' => 'true',
                    'can_write' => 'false',
                    'requires_confirmation' => 'false',
                    'is_active' => 'true',
                    'description' => 'Snapshot stok untuk manager dan gudang.',
                    'route' => 'stock-batches.index',
                    'metadata' => ['route' => 'stock-batches.index', 'allowed_roles' => ['admin', 'manager', 'gudang']],
                ],
                [
                    'intent_key' => 'warehouse.product.search',
                    'controller_class' => '\\App\\Http\\Controllers\\ProductController::class',
                    'controller_method' => 'index',
                    'module' => 'products',
                    'can_read' => 'true',
                    'can_write' => 'false',
                    'requires_confirmation' => 'false',
                    'is_active' => 'true',
                    'description' => 'Pencarian produk gudang.',
                    'route' => 'products.index',
                    'metadata' => ['route' => 'products.index', 'allowed_roles' => ['admin', 'manager', 'gudang']],
                ],
                [
                    'intent_key' => 'warehouse.batch.search',
                    'controller_class' => '\\App\\Http\\Controllers\\StockBatchController::class',
                    'controller_method' => 'index',
                    'module' => 'stock-batches',
                    'can_read' => 'true',
                    'can_write' => 'false',
                    'requires_confirmation' => 'false',
                    'is_active' => 'true',
                    'description' => 'Pencarian batch gudang.',
                    'route' => 'stock-batches.index',
                    'metadata' => ['route' => 'stock-batches.index', 'allowed_roles' => ['admin', 'manager', 'gudang']],
                ],
                [
                    'intent_key' => 'warehouse.return.search',
                    'controller_class' => '\\App\\Http\\Controllers\\SupplierReturnController::class',
                    'controller_method' => 'index',
                    'module' => 'supplier-returns',
                    'can_read' => 'true',
                    'can_write' => 'false',
                    'requires_confirmation' => 'false',
                    'is_active' => 'true',
                    'description' => 'Pencarian return supplier.',
                    'route' => 'supplier-returns.index',
                    'metadata' => ['route' => 'supplier-returns.index', 'allowed_roles' => ['admin', 'manager', 'gudang']],
                ],
                [
                    'intent_key' => 'warehouse.adjustment.compare',
                    'controller_class' => '\\App\\Http\\Controllers\\StockAdjustmentController::class',
                    'controller_method' => 'index',
                    'module' => 'stock-adjustments',
                    'can_read' => 'true',
                    'can_write' => 'false',
                    'requires_confirmation' => 'false',
                    'is_active' => 'true',
                    'description' => 'Perbandingan stok sistem dan fisik.',
                    'route' => 'stock-adjustments.index',
                    'metadata' => ['route' => 'stock-adjustments.index', 'allowed_roles' => ['admin', 'manager', 'gudang']],
                ],
                [
                    'intent_key' => 'cs.transaction.help',
                    'controller_class' => '\\App\\Http\\Controllers\\TransactionController::class',
                    'controller_method' => 'index',
                    'module' => 'transactions',
                    'can_read' => 'true',
                    'can_write' => 'false',
                    'requires_confirmation' => 'false',
                    'is_active' => 'true',
                    'description' => 'Bantuan transaksi cashier.',
                    'route' => 'transactions.index',
                    'metadata' => ['route' => 'transactions.index', 'allowed_roles' => ['admin', 'manager', 'cashier']],
                ],
                [
                    'intent_key' => 'cs.scan.help',
                    'controller_class' => '\\App\\Http\\Controllers\\AiCoreController::class',
                    'controller_method' => 'customerService',
                    'module' => 'customer-service',
                    'can_read' => 'true',
                    'can_write' => 'false',
                    'requires_confirmation' => 'false',
                    'is_active' => 'true',
                    'description' => 'Bantuan scan QR/barcode.',
                    'route' => 'ai-core.customer-service',
                    'metadata' => ['route' => 'ai-core.customer-service', 'allowed_roles' => ['admin', 'manager', 'cashier', 'gudang']],
                ],
                [
                    'intent_key' => 'cs.print.help',
                    'controller_class' => '\\App\\Http\\Controllers\\AiCoreController::class',
                    'controller_method' => 'customerService',
                    'module' => 'customer-service',
                    'can_read' => 'true',
                    'can_write' => 'false',
                    'requires_confirmation' => 'false',
                    'is_active' => 'true',
                    'description' => 'Bantuan print struk/barcode.',
                    'route' => 'ai-core.customer-service',
                    'metadata' => ['route' => 'ai-core.customer-service', 'allowed_roles' => ['admin', 'manager', 'cashier', 'gudang']],
                ],
            ],
            $this->crudBlueprints()
        );
    }

    protected function crudBlueprints(): array
    {
        $blueprints = [];

        foreach ($this->crudDefinitions() as $definition) {
            foreach ($definition['actions'] as $action) {
                $isWrite = in_array($action, ['store', 'update', 'destroy', 'restore', 'forceDelete', 'confirmSystemCorrect', 'applyCorrection'], true);
                $allowedRoles = $isWrite
                    ? ($definition['allowed_roles_write'] ?? $definition['allowed_roles'] ?? ['admin'])
                    : ($definition['allowed_roles_read'] ?? $definition['allowed_roles'] ?? ['admin']);

                $blueprints[] = [
                    'intent_key' => 'crud.' . $definition['module'] . '.' . $action,
                    'controller_class' => $definition['controller_class'],
                    'controller_method' => $action,
                    'module' => $definition['module'],
                    'can_read' => ! $isWrite,
                    'can_write' => $isWrite,
                    'requires_confirmation' => $isWrite,
                    'is_active' => true,
                    'description' => 'Guard ' . $definition['label'] . ' ' . $action . '.',
                    'metadata' => [
                        'route' => $definition['route_prefix'] . '.' . Str::of($action)->kebab()->toString(),
                        'allowed_roles' => $allowedRoles,
                    ],
                ];
            }
        }

        return $blueprints;
    }

    protected function crudDefinitions(): array
    {
        return [
            [
                'module' => 'activity-logs',
                'controller_class' => \App\Http\Controllers\ActivityLogController::class,
                'route_prefix' => 'activity-logs',
                'label' => 'Activity log',
                'allowed_roles' => ['admin'],
                'actions' => ['index', 'show'],
            ],
            [
                'module' => 'ai-core',
                'controller_class' => \App\Http\Controllers\AiCoreController::class,
                'route_prefix' => 'ai-core',
                'label' => 'AI Core',
                'allowed_roles' => ['admin'],
                'actions' => ['index', 'channels', 'dispatch', 'chatbot', 'search', 'customerService', 'guard'],
            ],
            [
                'module' => 'ai-channels',
                'controller_class' => \App\Http\Controllers\AiChannelController::class,
                'route_prefix' => 'ai-channels',
                'label' => 'AI channel',
                'allowed_roles' => ['admin'],
                'actions' => ['index'],
            ],
            [
                'module' => 'categories',
                'controller_class' => \App\Http\Controllers\CategoryController::class,
                'route_prefix' => 'categories',
                'label' => 'Category',
                'allowed_roles' => ['admin'],
                'actions' => ['index', 'recycle', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'restore', 'forceDelete'],
            ],
            [
                'module' => 'discount-settings',
                'controller_class' => \App\Http\Controllers\DiscountSettingController::class,
                'route_prefix' => 'discount-settings',
                'label' => 'Discount setting',
                'allowed_roles' => ['admin'],
                'actions' => ['index', 'recycle', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'restore', 'forceDelete'],
            ],
            [
                'module' => 'locations',
                'controller_class' => \App\Http\Controllers\LocationController::class,
                'route_prefix' => 'locations',
                'label' => 'Location',
                'allowed_roles' => ['admin'],
                'actions' => ['index', 'recycle', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'restore', 'forceDelete'],
            ],
            [
                'module' => 'log-tc',
                'controller_class' => \App\Http\Controllers\LogTcController::class,
                'route_prefix' => 'log-tc',
                'label' => 'Transaction log',
                'allowed_roles' => ['admin'],
                'actions' => ['index', 'show'],
            ],
            [
                'module' => 'products',
                'controller_class' => \App\Http\Controllers\ProductController::class,
                'route_prefix' => 'products',
                'label' => 'Product',
                'allowed_roles_read' => ['admin', 'manager', 'gudang'],
                'allowed_roles_write' => ['admin'],
                'actions' => ['index', 'recycle', 'create', 'store', 'show', 'edit', 'update', 'printBarcode', 'destroy', 'restore', 'forceDelete'],
            ],
            [
                'module' => 'promo-settings',
                'controller_class' => \App\Http\Controllers\ProductPromoSettingController::class,
                'route_prefix' => 'promo-settings',
                'label' => 'Promo setting',
                'allowed_roles' => ['admin'],
                'actions' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'],
            ],
            [
                'module' => 'roles',
                'controller_class' => \App\Http\Controllers\RoleController::class,
                'route_prefix' => 'roles',
                'label' => 'Role',
                'allowed_roles' => ['admin'],
                'actions' => ['index', 'recycle', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'restore', 'forceDelete'],
            ],
            [
                'module' => 'stock-adjustments',
                'controller_class' => \App\Http\Controllers\StockAdjustmentController::class,
                'route_prefix' => 'stock-adjustments',
                'label' => 'Stock adjustment',
                'allowed_roles_read' => ['admin', 'manager', 'gudang'],
                'allowed_roles_write' => ['admin', 'gudang'],
                'actions' => ['index', 'create', 'store', 'show', 'confirmSystemCorrect', 'applyCorrection'],
            ],
            [
                'module' => 'stock-batches',
                'controller_class' => \App\Http\Controllers\StockBatchController::class,
                'route_prefix' => 'stock-batches',
                'label' => 'Stock batch',
                'allowed_roles_read' => ['admin', 'manager', 'gudang'],
                'allowed_roles_write' => ['admin', 'gudang'],
                'actions' => ['index', 'recycle', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'restore', 'forceDelete'],
            ],
            [
                'module' => 'stock-movements',
                'controller_class' => \App\Http\Controllers\StockMovementController::class,
                'route_prefix' => 'stock-movements',
                'label' => 'Stock movement',
                'allowed_roles' => ['admin', 'manager', 'gudang'],
                'actions' => ['index', 'show'],
            ],
            [
                'module' => 'supplier-returns',
                'controller_class' => \App\Http\Controllers\SupplierReturnController::class,
                'route_prefix' => 'supplier-returns',
                'label' => 'Supplier return',
                'allowed_roles_read' => ['admin', 'manager', 'gudang'],
                'allowed_roles_write' => ['admin', 'gudang'],
                'actions' => ['index', 'recycle', 'create', 'store', 'show', 'destroy', 'restore', 'forceDelete'],
            ],
            [
                'module' => 'suppliers',
                'controller_class' => \App\Http\Controllers\SupplierController::class,
                'route_prefix' => 'suppliers',
                'label' => 'Supplier',
                'allowed_roles' => ['admin'],
                'actions' => ['index', 'recycle', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'restore', 'forceDelete'],
            ],
            [
                'module' => 'system-logs',
                'controller_class' => \App\Http\Controllers\SystemLogController::class,
                'route_prefix' => 'system-logs',
                'label' => 'System log',
                'allowed_roles' => ['admin'],
                'actions' => ['index', 'show'],
            ],
            [
                'module' => 'tax-settings',
                'controller_class' => \App\Http\Controllers\TaxSettingController::class,
                'route_prefix' => 'tax-settings',
                'label' => 'Tax setting',
                'allowed_roles' => ['admin'],
                'actions' => ['index', 'recycle', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'restore', 'forceDelete'],
            ],
            [
                'module' => 'transactions',
                'controller_class' => \App\Http\Controllers\TransactionController::class,
                'route_prefix' => 'transactions',
                'label' => 'Transaction',
                'allowed_roles_read' => ['admin', 'manager', 'cashier'],
                'allowed_roles_write' => ['admin', 'cashier'],
                'actions' => ['index', 'create', 'store', 'show', 'lookupBarcode', 'print'],
            ],
            [
                'module' => 'units',
                'controller_class' => \App\Http\Controllers\UnitController::class,
                'route_prefix' => 'units',
                'label' => 'Unit',
                'allowed_roles' => ['admin'],
                'actions' => ['index', 'recycle', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'restore', 'forceDelete'],
            ],
            [
                'module' => 'users',
                'controller_class' => \App\Http\Controllers\UserController::class,
                'route_prefix' => 'users',
                'label' => 'User',
                'allowed_roles' => ['admin'],
                'actions' => ['index', 'recycle', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'restore', 'forceDelete'],
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
