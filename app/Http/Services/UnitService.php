<?php

namespace App\Http\Services;


use App\Support\AuditTrail;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UnitService
{
    use AuditTrail;

    public function indexData(): array
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        return [
            'units' => $this->activeUnits(),
            'trashedUnits' => $this->trashedUnits(),
            'unitsStats' => $this->stats(),
        ];
    }

    public function activeUnits(): Collection
    {
        return Unit::query()
            ->orderByDesc('updated_at')
            ->get();
    }

    public function trashedUnits(): Collection
    {
        return Unit::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->get();
    }

    public function stats(): array
    {
        return [
            'total' => Unit::query()->count(),
            'active' => Unit::query()->where('is_active', true)->count(),
            'inactive' => Unit::query()->where('is_active', false)->count(),
            'trashed' => Unit::onlyTrashed()->count(),
        ];
    }

    public function store(array $data): Unit
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $payload = $this->normalizePayload($data);

        return DB::transaction(function () use ($payload) {
            $payload['name'] = $this->uniqueName($payload['name']);
            $payload['symbol'] = $this->uniqueSymbol($payload['symbol']);

            return Unit::create($payload);
        });
    }

    public function update(Unit $unit, array $data): Unit
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $payload = $this->normalizePayload($data);

        return DB::transaction(function () use ($unit, $payload) {
            $payload['name'] = $this->uniqueName($payload['name'], $unit->id);
            $payload['symbol'] = $this->uniqueSymbol($payload['symbol'], $unit->id);

            $unit->fill($payload);
            $unit->save();

            return $unit->refresh();
        });
    }

    public function trash(Unit $unit): void
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        if ($unit->products()->exists()) {
            throw ValidationException::withMessages([
                'unit' => 'Unit ini masih dipakai oleh produk. Pindahkan produk ke unit lain sebelum menghapus.',
            ]);
        }

        $unit->delete();
    }

    public function restore(int $id): Unit
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $unit = Unit::onlyTrashed()->findOrFail($id);
        $unit->restore();

        return $unit->refresh();
    }

    public function forceDelete(int $id): void
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $unit = Unit::onlyTrashed()->findOrFail($id);

        if ($unit->products()->exists()) {
            throw ValidationException::withMessages([
                'unit' => 'Unit ini masih dipakai oleh produk. Pindahkan produk ke unit lain sebelum penghapusan permanen.',
            ]);
        }

        $unit->forceDelete();
    }

    public function payload(Unit $unit): array
    {
        return [
            'id' => $unit->id,
            'name' => $unit->name,
            'symbol' => $unit->symbol,
            'is_active' => $unit->is_active ? 1 : 0,
            'updated_at' => optional($unit->updated_at)->format('d M Y H:i'),
            'deleted_at' => optional($unit->deleted_at)->format('d M Y H:i'),
        ];
    }

    protected function normalizePayload(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $symbol = strtoupper(trim((string) ($data['symbol'] ?? '')));

        return [
            'name' => $name,
            'symbol' => $symbol,
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

    protected function uniqueName(string $name, ?int $ignoreId = null): string
    {
        $baseName = trim($name) !== '' ? trim($name) : 'Unit';
        $candidate = $baseName;
        $counter = 2;

        while (
            Unit::withTrashed()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($candidate)])
                ->exists()
        ) {
            $candidate = $baseName . ' ' . $counter;
            $counter++;
        }

        return $candidate;
    }

    protected function uniqueSymbol(string $symbol, ?int $ignoreId = null): string
    {
        $baseSymbol = strtoupper(trim($symbol)) ?: 'U';
        $candidate = $baseSymbol;
        $counter = 2;

        while (
            Unit::withTrashed()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->whereRaw('LOWER(symbol) = ?', [mb_strtolower($candidate)])
                ->exists()
        ) {
            $candidate = $baseSymbol . $counter;
            $counter++;
        }

        return $candidate;
    }
}
