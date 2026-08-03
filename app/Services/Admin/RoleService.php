<?php

namespace App\Services\Admin;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RoleService
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Role::query()
            ->withCount(['users', 'permissions'])
            ->latest();

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function trashed(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Role::onlyTrashed()->withCount(['users', 'permissions'])->latest('deleted_at');

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function allPermissionsGrouped(): Collection
    {
        return Permission::query()
            ->ordered()
            ->get()
            ->groupBy('module');
    }

    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create(Arr::only($data, ['name', 'slug', 'description', 'is_active']));
            $this->syncPermissions($role, $data['permissions'] ?? []);

            return $role->load('permissions');
        });
    }

    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $role->update(Arr::only($data, ['name', 'slug', 'description', 'is_active']));
            $this->syncPermissions($role, $data['permissions'] ?? []);

            return $role->load('permissions');
        });
    }

    public function syncPermissions(Role $role, array $permissionIds): Role
    {
        $permissionIds = collect($permissionIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $role->permissions()->sync($permissionIds);

        return $role->load('permissions');
    }

    public function canDelete(Role $role): bool
    {
        return ! $role->users()->exists();
    }

    public function destroy(Role $role): void
    {
        if (! $this->canDelete($role)) {
            throw new RuntimeException('Role masih dipakai oleh user, jadi tidak bisa dihapus.');
        }

        $role->delete();
    }

    public function restore(Role $role): void
    {
        $role->restore();
    }

    public function forceDelete(Role $role): void
    {
        if (! $this->canDelete($role)) {
            throw new RuntimeException('Role masih dipakai oleh user, jadi tidak bisa dihapus permanen.');
        }

        $role->forceDelete();
    }
}
