<?php

namespace App\Http\Services;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SupplierService
{
    public function indexData(): array
    {
        return [
            'suppliers' => $this->activeSuppliers(),
            'trashedSuppliers' => $this->trashedSuppliers(),
            'supplierStats' => $this->stats(),
        ];
    }

    public function activeSuppliers(): Collection
    {
        return Supplier::query()
            ->withCount(['products', 'stockBatches', 'returns'])
            ->orderByDesc('updated_at')
            ->get();
    }

    public function trashedSuppliers(): Collection
    {
        return Supplier::onlyTrashed()
            ->withCount(['products', 'stockBatches', 'returns'])
            ->orderByDesc('deleted_at')
            ->get();
    }

    public function stats(): array
    {
        return [
            'total' => Supplier::query()->count(),
            'active' => Supplier::query()->where('is_active', true)->count(),
            'inactive' => Supplier::query()->where('is_active', false)->count(),
            'trashed' => Supplier::onlyTrashed()->count(),
        ];
    }

    public function store(array $data): Supplier
    {
        $payload = $this->normalizePayload($data);

        return DB::transaction(function () use ($payload) {
            $payload['code'] = $this->uniqueCode($payload['code']);

            return Supplier::create($payload);
        });
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $payload = $this->normalizePayload($data);

        return DB::transaction(function () use ($supplier, $payload) {
            $payload['code'] = $this->uniqueCode($payload['code'], $supplier->id);

            $supplier->fill($payload);
            $supplier->save();

            return $supplier->refresh();
        });
    }

    public function trash(Supplier $supplier): void
    {
        if ($this->hasCriticalReferences($supplier->id)) {
            throw ValidationException::withMessages([
                'supplier' => 'Supplier masih dipakai pada produk, batch stok, atau data retur. Pindahkan referensi terlebih dahulu sebelum menghapus.',
            ]);
        }

        $supplier->delete();
    }

    public function restore(int $id): Supplier
    {
        $supplier = Supplier::onlyTrashed()->findOrFail($id);
        $supplier->restore();

        return $supplier->refresh();
    }

    public function forceDelete(int $id): void
    {
        $supplier = Supplier::onlyTrashed()->findOrFail($id);

        if ($this->hasCriticalReferences($supplier->id)) {
            throw ValidationException::withMessages([
                'supplier' => 'Supplier masih memiliki referensi data. Hapus atau pindahkan referensi terlebih dahulu sebelum penghapusan permanen.',
            ]);
        }

        $supplier->forceDelete();
    }

    public function payload(Supplier $supplier): array
    {
        return [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'code' => $supplier->code,
            'phone' => $supplier->phone,
            'email' => $supplier->email,
            'address' => $supplier->address,
            'notes' => $supplier->notes,
            'is_active' => $supplier->is_active ? 1 : 0,
            'updated_at' => optional($supplier->updated_at)->format('d M Y H:i'),
            'deleted_at' => optional($supplier->deleted_at)->format('d M Y H:i'),
        ];
    }

    protected function normalizePayload(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $codeInput = trim((string) ($data['code'] ?? ''));

        return [
            'name' => $name,
            'code' => $this->normalizeCode($codeInput !== '' ? $codeInput : $name),
            'phone' => $this->normalizeText($data['phone'] ?? null),
            'email' => $this->normalizeEmail($data['email'] ?? null),
            'address' => $this->normalizeText($data['address'] ?? null, false, 1000),
            'notes' => $this->normalizeText($data['notes'] ?? null, false, 2000),
            'is_active' => $this->booleanValue($data['is_active'] ?? false),
        ];
    }

    protected function normalizeCode(mixed $value): string
    {
        $code = trim((string) ($value ?? ''));

        if ($code === '') {
            $code = 'SUP-' . Str::upper(Str::random(6));
        }

        $code = Str::upper(preg_replace('/\s+/', '-', $code) ?? $code);

        return $code;
    }

    protected function normalizeText(mixed $value, bool $uppercase = false, int $max = 500): ?string
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return null;
        }

        $text = Str::limit($text, $max, '');

        return $uppercase ? Str::upper($text) : $text;
    }

    protected function normalizeEmail(mixed $value): ?string
    {
        $email = trim((string) ($value ?? ''));

        return $email !== '' ? Str::lower($email) : null;
    }

    protected function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    protected function uniqueCode(string $code, ?int $ignoreId = null): string
    {
        $baseCode = Str::upper(Str::limit(preg_replace('/\s+/', '-', trim($code)) ?? $code, 120, ''));
        $baseCode = $baseCode !== '' ? $baseCode : 'SUP-' . Str::upper(Str::random(6));

        $candidate = $baseCode;
        $counter = 2;

        while (
            Supplier::withTrashed()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('code', $candidate)
                ->exists()
        ) {
            $candidate = $baseCode . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    protected function hasCriticalReferences(int $supplierId): bool
    {
        $tables = [
            ['products', 'supplier_id'],
            ['stock_batches', 'supplier_id'],
            ['returns', 'supplier_id'],
        ];

        foreach ($tables as [$table, $column]) {
            if (DB::table($table)->where($column, $supplierId)->exists()) {
                return true;
            }
        }

        return false;
    }
}
