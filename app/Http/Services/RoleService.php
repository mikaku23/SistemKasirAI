<?php

namespace App\Http\Services;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class RoleService
{
    public function indexData(): array
    {
        return [
            'roles' => $this->activeRoles(),
            'trashedRoles' => $this->trashedRoles(),
            'roleStats' => $this->stats(),
        ];
    }

    public function activeRoles(): Collection
    {
        return Role::query()
            ->orderByDesc('updated_at')
            ->get();
    }

    public function trashedRoles(): Collection
    {
        return Role::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->get();
    }

    public function stats(): array
    {
        return [
            'total' => Role::query()->count(),
            'active' => Role::query()->where('is_active', true)->count(),
            'inactive' => Role::query()->where('is_active', false)->count(),
            'trashed' => Role::onlyTrashed()->count(),
        ];
    }

    public function store(array $data): Role
    {
        $payload = $this->normalizePayload($data);

        return DB::transaction(function () use ($payload) {
            $payload['slug'] = $this->uniqueSlug($payload['slug']);

            return Role::create($payload);
        });
    }

    public function update(Role $role, array $data): Role
    {
        $payload = $this->normalizePayload($data);

        return DB::transaction(function () use ($role, $payload) {
            $payload['slug'] = $this->uniqueSlug($payload['slug'], $role->id);

            $role->fill($payload);
            $role->save();

            return $role->refresh();
        });
    }

    public function trash(Role $role): void
    {
        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => 'Role ini masih dipakai oleh user. Pindahkan user ke role lain sebelum menghapus.',
            ]);
        }

        $role->delete();
    }

    public function restore(int $id): Role
    {
        $role = Role::onlyTrashed()->findOrFail($id);
        $role->restore();

        return $role->refresh();
    }

    public function forceDelete(int $id): void
    {
        $role = Role::onlyTrashed()->findOrFail($id);

        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => 'Role ini masih dipakai oleh user. Hapus atau pindahkan user terlebih dahulu sebelum penghapusan permanen.',
            ]);
        }

        $role->forceDelete();
    }

    public function payload(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'description' => $role->description,
            'is_active' => $role->is_active ? 1 : 0,
            'updated_at' => optional($role->updated_at)->format('d M Y H:i'),
            'deleted_at' => optional($role->deleted_at)->format('d M Y H:i'),
        ];
    }

    protected function normalizePayload(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $slugInput = trim((string) ($data['slug'] ?? ''));
        $description = array_key_exists('description', $data) ? trim((string) $data['description']) : null;

        return [
            'name' => $name,
            'slug' => Str::slug($slugInput !== '' ? $slugInput : $name),
            'description' => $description !== '' ? $description : null,
            'is_active' => $this->booleanValue($data['is_active'] ?? false),
        ];
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
        $baseSlug = Str::slug($slug) ?: 'role';
        $candidate = $baseSlug;
        $counter = 2;

        while (
            Role::withTrashed()
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
