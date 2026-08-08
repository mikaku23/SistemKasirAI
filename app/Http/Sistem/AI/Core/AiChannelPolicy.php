<?php

namespace App\Http\Sistem\AI\Core;

use App\Models\AiChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AiChannelPolicy
{
    public function channels(): Collection
    {
        $defaults = $this->defaultChannels();

        foreach ($defaults as $channel) {
            AiChannel::query()->updateOrCreate(
                ['slug' => $channel['slug']],
                $channel
            );
        }

        return AiChannel::query()
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    public function resolveChannel(?string $slug, ?User $user = null): AiChannel
    {
        $slug = $slug ?: $this->defaultChannelSlugForUser($user);
        $channel = AiChannel::query()->where('slug', $slug)->first();

        if (! $channel) {
            $fallbackSlug = $this->defaultChannelSlugForUser($user);
            $channel = AiChannel::query()->where('slug', $fallbackSlug)->first();
        }

        if (! $channel) {
            $channel = AiChannel::query()->where('slug', 'admin-core')->firstOrFail();
        }

        return $channel;
    }

    public function authorize(AiChannel $channel, ?User $user = null): array
    {
        $role = $this->normalizeRole((string) data_get($user, 'role.slug', data_get($user, 'role.name', 'guest')));
        $allowedRoles = $this->allowedRoles($channel);

        $allowed = in_array($role, $allowedRoles, true) || $role === 'admin';

        return [
            'allowed' => $allowed,
            'role' => $role,
            'allowed_roles' => $allowedRoles,
            'channel' => $channel,
            'reason' => $allowed
                ? null
                : 'Role Anda tidak memiliki akses ke channel AI ini.',
        ];
    }

    public function allowedRoles(AiChannel|string $channel): array
    {
        $channel = $channel instanceof AiChannel ? $channel : AiChannel::query()->where('slug', $channel)->first();

        if (! $channel) {
            return ['admin'];
        }

        $roles = is_array($channel->metadata ?? null) ? Arr::get($channel->metadata, 'roles', []) : [];
        $roles = array_merge($roles, $this->metadataRoles($channel->slug), $channel->type === 'admin' ? ['admin'] : []);

        return collect($roles)
            ->map(fn (string $role): string => $this->normalizeRole($role))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function defaultChannelSlugForUser(?User $user = null): string
    {
        $role = $this->normalizeRole((string) data_get($user, 'role.slug', data_get($user, 'role.name', 'guest')));

        return match ($role) {
            'admin' => 'admin-core',
            'manager' => 'manager-chatbot',
            'gudang', 'warehouse', 'storehouse' => 'warehouse-search',
            'cashier', 'kasir' => 'customer-service',
            default => 'admin-core',
        };
    }

    public function crudPolicy(string $module, string $action, ?User $user = null): array
    {
        $role = $this->normalizeRole((string) data_get($user, 'role.slug', data_get($user, 'role.name', 'guest')));
        $action = strtolower(trim($action));
        $module = strtolower(trim($module));
        $isReadAction = in_array($action, ['index', 'show', 'search', 'list', 'view'], true);
        $isWriteAction = in_array($action, ['store', 'update', 'destroy', 'restore', 'forceDelete', 'delete'], true);

        if ($role === 'admin') {
            return [
                'allowed' => true,
                'requires_confirmation' => $isWriteAction,
                'reason' => null,
                'visibility' => 'full',
            ];
        }

        if ($isReadAction) {
            $visibility = match ($role) {
                'manager' => in_array($module, ['products', 'stock-batches', 'transactions', 'supplier-returns', 'stock-adjustments', 'stock-movements', 'locations', 'categories', 'units', 'suppliers'], true) ? 'read' : 'limited',
                'gudang', 'warehouse', 'storehouse' => in_array($module, ['products', 'stock-batches', 'supplier-returns', 'stock-adjustments', 'stock-movements'], true) ? 'read' : 'limited',
                'cashier', 'kasir' => in_array($module, ['transactions', 'products', 'stock-batches'], true) ? 'read' : 'limited',
                default => 'limited',
            };

            return [
                'allowed' => true,
                'requires_confirmation' => false,
                'reason' => null,
                'visibility' => $visibility,
            ];
        }

        return [
            'allowed' => false,
            'requires_confirmation' => true,
            'reason' => 'Aksi tulis/destruktif hanya boleh dijalankan oleh Admin. Role lain hanya bisa membuat preview atau meminta approval.',
            'visibility' => 'blocked',
        ];
    }

    protected function defaultChannels(): array
    {
        return [
            [
                'name' => 'AI Core',
                'slug' => 'admin-core',
                'type' => 'admin',
                'is_active' => true,
                'description' => 'Inti AI utama untuk admin. Mengorkestrasi routing, guardrail, notifikasi, dan audit.',
                'system_prompt' => 'Anda adalah AI Core sistem kasir management. Prioritas Anda adalah keamanan role, audit, guardrail, dan orkestrasi channel AI bawahan.',
                'allowed_tools' => ['dashboard', 'audit', 'guard_crud', 'persist_message', 'toast'],
                'metadata' => [
                    'roles' => ['admin'],
                    'scope' => 'system-core',
                ],
            ],
            [
                'name' => 'Manager Chatbot',
                'slug' => 'manager-chatbot',
                'type' => 'public',
                'is_active' => true,
                'description' => 'Chatbot operasional untuk manager. Hanya membaca data harian, ringkasan, dan rekomendasi.',
                'system_prompt' => 'Anda adalah asisten manager. Dilarang mengakses data sensitif yang hanya untuk admin. Beri ringkasan, insight, dan arah tindakan.',
                'allowed_tools' => ['read_inventory', 'read_transactions', 'read_adjustments', 'read_returns', 'toast'],
                'metadata' => [
                    'roles' => ['admin', 'manager'],
                    'scope' => 'manager-assistant',
                ],
            ],
            [
                'name' => 'Warehouse Search',
                'slug' => 'warehouse-search',
                'type' => 'search',
                'is_active' => true,
                'description' => 'AI pencarian data untuk gudang. Fokus pada stok, batch, return, dan stock adjustment.',
                'system_prompt' => 'Anda adalah AI pencarian gudang. Fokus pada stok, batch, return, dan perbandingan stok sistem vs fisik. Jangan bocorkan data di luar otorisasi role.',
                'allowed_tools' => ['search_products', 'search_batches', 'search_returns', 'search_adjustments', 'toast'],
                'metadata' => [
                    'roles' => ['admin', 'gudang', 'warehouse', 'storehouse'],
                    'scope' => 'warehouse-search',
                ],
            ],
            [
                'name' => 'Customer Service',
                'slug' => 'customer-service',
                'type' => 'customer_service',
                'is_active' => true,
                'description' => 'Asisten CS untuk cashier dan gudang terkait cara memakai sistem kasir.',
                'system_prompt' => 'Anda adalah customer service internal sistem kasir. Jawab cara pakai sistem, error umum, scan QR, print, dan alur transaksi tanpa membuka data rahasia.',
                'allowed_tools' => ['help_articles', 'system_help', 'toast'],
                'metadata' => [
                    'roles' => ['admin', 'cashier', 'kasir', 'gudang', 'warehouse', 'storehouse'],
                    'scope' => 'support',
                ],
            ],
        ];
    }

    protected function metadataRoles(string $slug): array
    {
        return match ($slug) {
            'manager-chatbot' => ['admin', 'manager'],
            'warehouse-search' => ['admin', 'gudang', 'warehouse', 'storehouse'],
            'customer-service' => ['admin', 'cashier', 'kasir', 'gudang', 'warehouse', 'storehouse'],
            default => ['admin'],
        };
    }

    protected function normalizeRole(string $role): string
    {
        return strtolower(trim(str_replace(['-', '_'], ' ', $role)));
    }
}
