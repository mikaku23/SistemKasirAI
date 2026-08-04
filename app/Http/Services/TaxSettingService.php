<?php

namespace App\Http\Services;

use App\Models\TaxSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TaxSettingService
{
    public function indexData(): array
    {
        return [
            'taxSettings' => $this->activeSettings(),
            'trashedTaxSettings' => $this->trashedSettings(),
            'taxStats' => $this->stats(),
        ];
    }

    public function activeSettings(): Collection
    {
        return TaxSetting::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function trashedSettings(): Collection
    {
        return TaxSetting::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->get();
    }

    public function stats(): array
    {
        return [
            'total' => TaxSetting::query()->count(),
            'active' => TaxSetting::query()->where('is_active', true)->count(),
            'default' => TaxSetting::query()->where('is_default', true)->count(),
            'trashed' => TaxSetting::onlyTrashed()->count(),
        ];
    }

    public function store(array $data): TaxSetting
    {
        $payload = $this->normalizePayload($data);

        return DB::transaction(function () use ($payload) {
            $payload['code'] = $this->uniqueCode($payload['code'] ?: $payload['name']);

            if ($payload['is_default']) {
                $this->clearDefault();
            }

            return TaxSetting::create($payload);
        });
    }

    public function update(TaxSetting $taxSetting, array $data): TaxSetting
    {
        $payload = $this->normalizePayload($data);

        return DB::transaction(function () use ($taxSetting, $payload) {
            $payload['code'] = $this->uniqueCode($payload['code'] ?: $payload['name'], $taxSetting->id);

            if ($payload['is_default']) {
                $this->clearDefault($taxSetting->id);
            }

            $taxSetting->fill($payload);
            $taxSetting->save();

            return $taxSetting->refresh();
        });
    }

    public function trash(TaxSetting $taxSetting): void
    {
        if ($taxSetting->transactions()->exists()) {
            throw ValidationException::withMessages([
                'tax_setting' => 'Setting pajak ini masih dipakai oleh transaksi. Nonaktifkan atau pindahkan dulu.',
            ]);
        }

        $taxSetting->delete();
    }

    public function restore(int $id): TaxSetting
    {
        $taxSetting = TaxSetting::onlyTrashed()->findOrFail($id);
        $taxSetting->restore();

        return $taxSetting->refresh();
    }

    public function forceDelete(int $id): void
    {
        $taxSetting = TaxSetting::onlyTrashed()->findOrFail($id);

        if ($taxSetting->transactions()->exists()) {
            throw ValidationException::withMessages([
                'tax_setting' => 'Setting pajak ini masih dipakai oleh transaksi. Hapus transaksi terkait terlebih dahulu.',
            ]);
        }

        $taxSetting->forceDelete();
    }

    public function defaultSetting(): ?TaxSetting
    {
        return TaxSetting::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->first();
    }

    public function payload(TaxSetting $taxSetting): array
    {
        return [
            'id' => $taxSetting->id,
            'name' => $taxSetting->name,
            'code' => $taxSetting->code,
            'tax_type' => $taxSetting->tax_type,
            'tax_type_label' => $taxSetting->tax_type_label,
            'tax_value' => $taxSetting->tax_value,
            'display_value' => $taxSetting->display_value,
            'is_default' => $taxSetting->is_default ? 1 : 0,
            'is_active' => $taxSetting->is_active ? 1 : 0,
            'updated_at' => optional($taxSetting->updated_at)->format('d M Y H:i'),
            'deleted_at' => optional($taxSetting->deleted_at)->format('d M Y H:i'),
        ];
    }

    protected function normalizePayload(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $code = trim((string) ($data['code'] ?? ''));
        $taxType = (string) ($data['tax_type'] ?? 'fixed');

        return [
            'name' => $name,
            'code' => $code !== '' ? Str::upper($code) : '',
            'tax_type' => in_array($taxType, ['fixed', 'percent'], true) ? $taxType : 'fixed',
            'tax_value' => max(0, (int) ($data['tax_value'] ?? 0)),
            'is_default' => $this->booleanValue($data['is_default'] ?? false),
            'is_active' => $this->booleanValue($data['is_active'] ?? true),
        ];
    }

    protected function clearDefault(?int $ignoreId = null): void
    {
        TaxSetting::query()
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->update(['is_default' => false]);
    }

    protected function uniqueCode(string $source, ?int $ignoreId = null): string
    {
        $base = Str::upper(Str::slug($source, ''));
        $base = $base !== '' ? $base : 'TAX';

        if (!Str::startsWith($base, 'TAX')) {
            $base = 'TAX' . $base;
        }

        $candidate = $base;
        $counter = 2;

        while (
            TaxSetting::withTrashed()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('code', $candidate)
                ->exists()
        ) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    protected function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }
}
