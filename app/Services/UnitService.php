<?php

namespace App\Services;

use App\Models\Unit;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class UnitService
{
    public function index(): Collection
    {
        return Unit::latest()->get();
    }

    public function recycle(): Collection
    {
        return Unit::onlyTrashed()->latest()->get();
    }

    public function store(array $data): Unit
    {
        $payload = $this->preparePayload($data);

        return Unit::create($payload);
    }

    public function update(Unit $unit, array $data): Unit
    {
        $payload = $this->preparePayload($data);

        $unit->update($payload);

        return $unit->refresh();
    }

    public function destroy(Unit $unit): void
    {
        $unit->delete();
    }

    public function restore(int $id): Unit
    {
        $unit = Unit::onlyTrashed()->findOrFail($id);
        $unit->restore();

        return $unit->refresh();
    }

    public function forceDelete(int $id): void
    {
        $unit = Unit::withTrashed()->findOrFail($id);

        if ($unit->products()->exists()) {
            throw new DomainException('Satuan tidak bisa dihapus permanen karena masih dipakai oleh produk.');
        }

        $unit->forceDelete();
    }

    private function preparePayload(array $data): array
    {
        return [
            'name' => trim((string) ($data['name'] ?? '')),
            'symbol' => trim((string) ($data['symbol'] ?? '')),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}
