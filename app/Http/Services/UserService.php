<?php

namespace App\Http\Services;


use App\Support\AuditTrail;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserService
{
    use AuditTrail;

    public function indexData(): array
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        return [
            'users' => $this->activeUsers(),
            'trashedUsers' => $this->trashedUsers(),
            'userStats' => $this->stats(),
        ];
    }

    public function formData(): array
    {
        return [
            'roles' => Role::query()->orderBy('name')->get(),
            'locations' => Location::query()->orderBy('name')->get(),
        ];
    }

    public function activeUsers(): Collection
    {
        return User::query()
            ->with(['role', 'location'])
            ->orderByDesc('updated_at')
            ->get();
    }

    public function trashedUsers(): Collection
    {
        return User::onlyTrashed()
            ->with(['role', 'location'])
            ->orderByDesc('deleted_at')
            ->get();
    }

    public function stats(): array
    {
        $hasIsActiveColumn = Schema::hasColumn('users', 'is_active');

        return [
            'total' => User::query()->count(),
            'active' => $hasIsActiveColumn ? User::query()->where('is_active', true)->count() : 0,
            'inactive' => $hasIsActiveColumn ? User::query()->where('is_active', false)->count() : 0,
            'trashed' => User::onlyTrashed()->count(),
            'admins' => User::query()
                ->whereHas('role', fn($query) => $query->where('slug', 'admin'))
                ->count(),
        ];
    }

    public function store(array $data, ?UploadedFile $avatar = null): User
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $payload = $this->normalizePayload($data);

        return DB::transaction(function () use ($payload, $avatar) {
            $user = User::create($payload);

            if ($avatar !== null) {
                $user->avatar = $this->storeAvatar($avatar, $user->id);
                $user->save();
            }

            return $user->refresh();
        });
    }

    public function update(User $user, array $data, ?UploadedFile $avatar = null): User
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $payload = $this->normalizePayload($data, $user);

        return DB::transaction(function () use ($user, $payload, $avatar) {
            $oldAvatar = $user->avatar;

            $user->fill($payload);
            $user->save();

            if ($avatar !== null) {
                $this->deleteAvatarIfExists($oldAvatar);
                $user->avatar = $this->storeAvatar($avatar, $user->id);
                $user->save();
            }

            return $user->refresh();
        });
    }

    public function trash(User $user): void
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        if ((int) Auth::id() === (int) $user->id) {
            throw ValidationException::withMessages([
                'user' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ]);
        }

        if ($this->isLastActiveAdmin($user)) {
            throw ValidationException::withMessages([
                'user' => 'Admin terakhir tidak boleh dihapus. Tambahkan admin lain terlebih dahulu.',
            ]);
        }

        $user->delete();
    }

    public function restore(int $id): User
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        return $user->refresh();
    }

    public function forceDelete(int $id): void
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $user = User::onlyTrashed()->findOrFail($id);

        if ($this->isLastActiveAdmin($user)) {
            throw ValidationException::withMessages([
                'user' => 'Admin terakhir tidak boleh dihapus permanen.',
            ]);
        }

        if ($this->hasCriticalReferences($user->id)) {
            throw ValidationException::withMessages([
                'user' => 'User masih memiliki data transaksi/aktivitas. Pindahkan atau arsipkan data terkait terlebih dahulu sebelum penghapusan permanen.',
            ]);
        }

        $this->deleteAvatarIfExists($user->avatar);
        $user->forceDelete();
    }


    public function payload(User $user): array
    {
        $user->loadMissing(['role', 'location']);

        return [
            'id' => $user->id,
            'role_id' => $user->role_id,
            'location_id' => $user->location_id,
            'role_name' => $user->role?->name,
            'location_name' => $user->location?->name,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'no_hp' => $user->no_hp,
            'security_question' => $user->security_question,
            'security_answer' => $user->security_answer,
            'avatar' => $user->avatar,
            'avatar_url' => $user->avatar ? Storage::disk('public')->url($user->avatar) : null,
            'last_login_at_input' => optional($user->last_login_at)?->format('Y-m-d\TH:i'),
            'last_password_changed_at_input' => optional($user->last_password_changed_at)?->format('Y-m-d\TH:i'),
            'initials' => $user->initials(),
            'is_active' => Schema::hasColumn('users', 'is_active') ? (int) ($user->is_active ? 1 : 0) : null,
            'updated_at' => optional($user->updated_at)->format('d M Y H:i'),
            'deleted_at' => optional($user->deleted_at)->format('d M Y H:i'),
        ];
    }

    protected function normalizePayload(array $data, ?User $user = null): array
    {
        $payload = [
            'role_id' => $this->nullableInteger($data['role_id'] ?? null),
            'location_id' => $this->nullableInteger($data['location_id'] ?? null),
            'name' => trim((string) ($data['name'] ?? '')),
            'username' => $this->normalizeUsername($data['username'] ?? ''),
            'email' => $this->normalizeEmail($data['email'] ?? null),
            'no_hp' => $this->normalizePhone($data['no_hp'] ?? ''),
            'security_question' => $this->normalizeText($data['security_question'] ?? null),
            'security_answer' => $this->normalizeText($data['security_answer'] ?? null),
            'last_login_at' => $this->nullableDateTime($data['last_login_at'] ?? null),
            'last_password_changed_at' => $this->nullableDateTime($data['last_password_changed_at'] ?? null),
        ];

        if (Schema::hasColumn('users', 'is_active')) {
            if (array_key_exists('is_active', $data)) {
                $payload['is_active'] = $this->booleanValue($data['is_active']);
            } elseif ($user === null) {
                $payload['is_active'] = true;
            }
        }

        if (array_key_exists('password', $data)) {
            $password = trim((string) $data['password']);

            if ($password !== '') {
                $payload['password'] = $password;
            } elseif ($user === null) {
                $payload['password'] = '';
            }
        }

        return $payload;
    }

    protected function normalizeUsername(mixed $value): string
    {
        $username = Str::lower(trim((string) $value));
        $username = preg_replace('/\s+/', '', $username) ?? $username;

        return $username;
    }

    protected function normalizeEmail(mixed $value): ?string
    {
        $email = trim((string) ($value ?? ''));

        return $email !== '' ? Str::lower($email) : null;
    }

    protected function normalizeText(mixed $value, bool $uppercase = false): ?string
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return null;
        }

        return $uppercase ? Str::upper($text) : $text;
    }

    protected function normalizePhone(mixed $value): string
    {
        $phone = trim((string) $value);
        $phone = preg_replace('/\s+/', '', $phone) ?? $phone;

        return $phone;
    }

    protected function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    protected function nullableDateTime(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text !== '' ? $text : null;
    }

    protected function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    protected function storeAvatar(UploadedFile $avatar, int $userId): string
    {
        $folder = 'avatars/users/' . $userId;

        return $avatar->store($folder, 'public');
    }

    protected function deleteAvatarIfExists(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    protected function isLastActiveAdmin(User $target): bool
    {
        if (! $target->relationLoaded('role')) {
            $target->load('role');
        }

        $isAdmin = $target->role?->slug === 'admin';

        if (! $isAdmin) {
            return false;
        }

        $activeAdminCount = User::query()
            ->whereHas('role', fn($query) => $query->where('slug', 'admin'))
            ->where('is_active', true)
            ->whereKeyNot($target->id)
            ->count();

        return $activeAdminCount === 0;
    }

    protected function hasCriticalReferences(int $userId): bool
    {
        $tables = [
            ['transactions', 'cashier_id'],
            ['stock_batches', 'received_by'],
            ['stock_movements', 'user_id'],
            ['stock_adjustments', 'user_id'],
            ['stock_opnames', 'user_id'],
            ['returns', 'user_id'],
            ['ai_conversations', 'user_id'],
            ['ai_messages', 'user_id'],
            ['activity_logs', 'user_id'],
        ];

        foreach ($tables as [$table, $column]) {
            if (DB::table($table)->where($column, $userId)->exists()) {
                return true;
            }
        }

        return false;
    }
}
