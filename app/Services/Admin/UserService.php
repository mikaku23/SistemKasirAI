<?php

namespace App\Services\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class UserService
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['role', 'location'])
            ->latest();

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['role_id'])) {
            $query->where('role_id', (int) $filters['role_id']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function trashed(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = User::onlyTrashed()->with(['role', 'location'])->latest('deleted_at');

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create($this->preparePayload($data));
            return $user->load(['role', 'location']);
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $payload = $this->preparePayload($data, $user);

            $user->update($payload);

            if (! empty($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
                $avatarPath = $this->storeAvatar($user, $data['avatar']);
                $user->forceFill(['avatar' => $avatarPath])->save();
            }

            return $user->load(['role', 'location']);
        });
    }

    public function destroy(User $user): void
    {
        if ($this->isLastActiveAdmin($user)) {
            throw new RuntimeException('User admin terakhir tidak boleh dihapus.');
        }

        $user->delete();
    }

    public function restore(User $user): void
    {
        $user->restore();
    }

    public function forceDelete(User $user): void
    {
        if ($this->isLastActiveAdmin($user)) {
            throw new RuntimeException('User admin terakhir tidak boleh dihapus permanen.');
        }

        $this->removeAvatarIfExists($user);
        $user->forceDelete();
    }

    public function isLastActiveAdmin(User $user): bool
    {
        if (! $user->relationLoaded('role')) {
            $user->loadMissing('role');
        }

        if (strtolower((string) $user->role?->slug) !== 'admin') {
            return false;
        }

        return User::query()
            ->whereHas('role', fn ($q) => $q->whereRaw('LOWER(slug) = ?', ['admin']))
            ->whereNull('deleted_at')
            ->when($user->exists, fn ($q) => $q->whereKeyNot($user->getKey()))
            ->count() === 0;
    }

    protected function preparePayload(array $data, ?User $user = null): array
    {
        $payload = Arr::only($data, [
            'role_id',
            'location_id',
            'name',
            'username',
            'email',
            'nim',
            'nip',
            'no_hp',
            'security_question',
            'security_answer',
        ]);

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
            $payload['last_password_changed_at'] = now();
        }

        if (! empty($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
            $payload['avatar'] = $this->storeAvatar($user, $data['avatar']);
        }

        return $payload;
    }

    protected function storeAvatar(?User $user, ?UploadedFile $file): ?string
    {
        if (! $file) {
            return $user?->avatar;
        }

        if ($user?->avatar) {
            $this->removeAvatarIfExists($user);
        }

        $path = $file->store('avatars', 'public');

        return $path;
    }

    protected function removeAvatarIfExists(User $user): void
    {
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }
    }
}
