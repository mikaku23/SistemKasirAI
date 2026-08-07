<?php

namespace App\Http\Services;


use App\Support\AuditTrail;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LocationService
{
    use AuditTrail;

    public function indexData(): array
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        return [
            'locations' => $this->activeLocations(),
            'trashedLocations' => $this->trashedLocations(),
            'locationStats' => $this->stats(),
        ];
    }

    public function activeLocations(): Collection
    {
        return Location::query()
            ->withCount([
                'users',
                'products',
                'stockBatches',
                'stockMovements',
                'stockAdjustments',
                'stockOpnames',
                'transactions',
                'returns',
            ])
            ->orderByDesc('updated_at')
            ->get();
    }

    public function trashedLocations(): Collection
    {
        return Location::onlyTrashed()
            ->withCount([
                'users',
                'products',
                'stockBatches',
                'stockMovements',
                'stockAdjustments',
                'stockOpnames',
                'transactions',
                'returns',
            ])
            ->orderByDesc('deleted_at')
            ->get();
    }

    public function stats(): array
    {
        return [
            'total' => Location::query()->count(),
            'active' => Location::query()->where('is_active', true)->count(),
            'inactive' => Location::query()->where('is_active', false)->count(),
            'in_use' => $this->operationalLocationsCount(),
            'trashed' => Location::onlyTrashed()->count(),
        ];
    }

    public function showData(Location $location): array
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        return [
            'locationUsage' => $this->usageCounts($location),
            'locationPayload' => $this->payload($location),
        ];
    }

    public function store(array $data): Location
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $payload = $this->normalizePayload($data);

        return DB::transaction(function () use ($payload) {
            $payload['code'] = $this->ensureUniqueCode($payload['code']);

            return Location::create($payload);
        });
    }

    public function update(Location $location, array $data): Location
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $payload = $this->normalizePayload($data, $location);

        return DB::transaction(function () use ($location, $payload) {
            if ($payload['code'] !== $location->code) {
                $payload['code'] = $this->ensureUniqueCode($payload['code'], $location->id);
            }

            $location->fill($payload);
            $location->save();

            return $location->refresh();
        });
    }

    public function trash(Location $location): void
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        if ($this->hasOperationalUsage($location)) {
            throw ValidationException::withMessages([
                'location' => 'Location ini masih dipakai oleh data operasional. Pindahkan referensi dulu sebelum menghapus.',
            ]);
        }

        $location->delete();
    }

    public function restore(int $id): Location
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $location = Location::onlyTrashed()->findOrFail($id);
        $location->restore();

        return $location->refresh();
    }

    public function forceDelete(int $id): void
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $location = Location::onlyTrashed()->findOrFail($id);

        if ($this->hasOperationalUsage($location)) {
            throw ValidationException::withMessages([
                'location' => 'Location ini masih dipakai oleh data operasional dan tidak dapat dihapus permanen.',
            ]);
        }

        $location->forceDelete();
    }

    public function payload(Location $location): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'code' => $location->code,
            'address' => $location->address,
            'phone' => $location->phone,
            'is_active' => $location->is_active ? 1 : 0,
            'updated_at' => optional($location->updated_at)->format('d M Y H:i'),
            'deleted_at' => optional($location->deleted_at)->format('d M Y H:i'),
        ];
    }

    public function usageCounts(Location $location): array
    {
        return [
            'users' => $location->users()->count(),
            'products' => $location->products()->count(),
            'stock_batches' => $location->stockBatches()->count(),
            'stock_movements' => $location->stockMovements()->count(),
            'stock_adjustments' => $location->stockAdjustments()->count(),
            'stock_opnames' => $location->stockOpnames()->count(),
            'transactions' => $location->transactions()->count(),
            'returns' => $location->returns()->count(),
        ];
    }

    protected function hasOperationalUsage(Location $location): bool
    {
        foreach ($this->usageCounts($location) as $count) {
            if ((int) $count > 0) {
                return true;
            }
        }

        return false;
    }

    protected function operationalLocationsCount(): int
    {
        return Location::query()
            ->where(function ($query) {
                $query->whereHas('users')
                    ->orWhereHas('products')
                    ->orWhereHas('stockBatches')
                    ->orWhereHas('stockMovements')
                    ->orWhereHas('stockAdjustments')
                    ->orWhereHas('stockOpnames')
                    ->orWhereHas('transactions')
                    ->orWhereHas('returns');
            })
            ->count();
    }

    protected function normalizePayload(array $data, ?Location $location = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $rawCode = trim((string) ($data['code'] ?? ''));
        $address = trim((string) ($data['address'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));

        return [
            'name' => $name,
            'code' => $rawCode !== ''
                ? $this->normalizeCode($rawCode)
                : ($location?->code ?? $this->generateCodeFromName($name)),
            'address' => $address !== '' ? $address : null,
            'phone' => $phone !== '' ? $phone : null,
            'is_active' => $this->booleanValue($data['is_active'] ?? true),
        ];
    }

    protected function generateCodeFromName(string $name): string
    {
        $prefix = $this->namePrefix($name);
        $base = 'LOC-' . $prefix;
        $candidate = $base . '-001';
        $counter = 1;

        while (
            Location::withTrashed()
                ->where('code', $candidate)
                ->exists()
        ) {
            $counter++;
            $candidate = $base . '-' . str_pad((string) $counter, 3, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }

    protected function ensureUniqueCode(string $code, ?int $ignoreId = null): string
    {
        $base = $this->normalizeCode($code) ?: 'LOC-DEFAULT';
        $candidate = $base;
        $counter = 1;

        while (
            Location::withTrashed()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('code', $candidate)
                ->exists()
        ) {
            $counter++;
            $candidate = $base . '-' . str_pad((string) $counter, 2, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }

    protected function normalizeCode(string $code): string
    {
        $code = Str::upper(trim($code));
        $code = preg_replace('/[^A-Z0-9]+/', '-', $code) ?? '';
        $code = trim($code, '-');

        return $code !== '' ? $code : 'LOC-DEFAULT';
    }

    protected function namePrefix(string $name): string
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];
        $stopWords = ['dan', 'of', 'the', 'di', 'de', 'da', '&'];

        $tokens = array_values(array_filter($words, static function ($word) use ($stopWords) {
            $normalized = Str::lower(preg_replace('/[^A-Za-z0-9]/', '', $word) ?? '');

            return $normalized !== '' && ! in_array($normalized, $stopWords, true);
        }));

        $prefix = '';

        foreach (array_slice($tokens, 0, 3) as $word) {
            $clean = preg_replace('/[^A-Za-z0-9]/', '', $word) ?? '';
            $prefix .= Str::upper(Str::substr($clean, 0, 1));
        }

        if ($prefix === '') {
            $clean = preg_replace('/[^A-Za-z0-9]/', '', $name) ?? '';
            $prefix = Str::upper(Str::substr($clean, 0, 3));
        }

        return $prefix !== '' ? $prefix : 'LOC';
    }

    protected function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }
}
