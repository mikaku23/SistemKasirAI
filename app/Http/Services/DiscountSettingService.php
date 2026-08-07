<?php

namespace App\Http\Services;


use App\Support\AuditTrail;
use App\Models\DiscountSetting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DiscountSettingService
{
    use AuditTrail;

    public function indexData(): array
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $this->syncExpiredDiscountSettings();

        return [
            'discountSettings' => $this->activeDiscountSettings(),
            'trashedDiscountSettings' => $this->trashedDiscountSettings(),
            'discountStats' => $this->stats(),
        ];
    }

    public function referenceData(): array
    {
        $this->syncExpiredDiscountSettings();

        $settings = $this->activeDiscountSettings();

        return [
            'discountSettings' => $settings,
            'defaultDiscountSetting' => $settings->firstWhere('is_default', true) ?: $settings->first(),
        ];
    }

    public function activeDiscountSettings(): Collection
    {
        $this->syncExpiredDiscountSettings();

        return DiscountSetting::query()
            ->orderByDesc('is_default')
            ->orderByDesc('priority')
            ->orderBy('minimum_total_amount')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function trashedDiscountSettings(): Collection
    {
        return DiscountSetting::onlyTrashed()->orderByDesc('deleted_at')->get();
    }

    public function stats(): array
    {
        $this->syncExpiredDiscountSettings();

        return [
            'total' => DiscountSetting::query()->count(),
            'active' => DiscountSetting::query()->where('is_active', true)->count(),
            'inactive' => DiscountSetting::query()->where('is_active', false)->count(),
            'trashed' => DiscountSetting::onlyTrashed()->count(),
        ];
    }

    public function store(array $data): DiscountSetting
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $payload = $this->normalizePayload($data, true);

        return DB::transaction(function () use ($payload) {
            $payload['code'] = $this->uniqueCode($payload['code']);

            if ($payload['is_default']) {
                $this->clearDefaultFlags();
            }

            $setting = DiscountSetting::create($payload);

            $this->syncExpiredDiscountSettings();

            return $setting->refresh();
        });
    }

    public function update(DiscountSetting $discountSetting, array $data): DiscountSetting
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $payload = $this->normalizePayload($data, false, $discountSetting);

        return DB::transaction(function () use ($discountSetting, $payload) {
            $payload['code'] = $this->uniqueCode($payload['code'] ?: $discountSetting->code, $discountSetting->id);

            if ($payload['is_default']) {
                $this->clearDefaultFlags($discountSetting->id);
            }

            $discountSetting->fill($payload);
            $discountSetting->save();

            $this->syncExpiredDiscountSettings();

            return $discountSetting->refresh();
        });
    }

    public function trash(DiscountSetting $discountSetting): void
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $discountSetting->delete();
    }

    public function restore(int $id): DiscountSetting
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $setting = DiscountSetting::onlyTrashed()->findOrFail($id);
        $setting->restore();

        $this->syncExpiredDiscountSettings();

        return $setting->refresh();
    }

    public function forceDelete(int $id): void
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $setting = DiscountSetting::onlyTrashed()->findOrFail($id);
        $setting->forceDelete();
    }

    public function resolveApplicableSetting(int $eligibleAmount, ?Carbon $transactionAt = null): ?DiscountSetting
    {
        $transactionAt ??= now();
        $this->syncExpiredDiscountSettings($transactionAt);

        return DiscountSetting::query()
            ->where('is_active', true)
            ->where(function ($query) use ($transactionAt) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $transactionAt);
            })
            ->where(function ($query) use ($transactionAt) {
                $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $transactionAt->toDateString());
            })
            ->where('minimum_total_amount', '<=', $eligibleAmount)
            ->orderByDesc('is_default')
            ->orderByDesc('priority')
            ->orderByDesc('minimum_total_amount')
            ->orderByDesc('discount_value')
            ->orderByDesc('id')
            ->first();
    }

    public function calculateDiscountAmount(?DiscountSetting $setting, int $baseAmount): int
    {
        if (! $setting || $baseAmount <= 0) {
            return 0;
        }

        if ($setting->discount_type === 'percent') {
            return max(0, (int) round(($baseAmount * max(0, (int) $setting->discount_value)) / 100));
        }

        return min($baseAmount, max(0, (int) $setting->discount_value));
    }

    public function payload(DiscountSetting $setting): array
    {
        return [
            'id' => $setting->id,
            'name' => $setting->name,
            'code' => $setting->code,
            'discount_type' => $setting->discount_type,
            'discount_type_label' => $setting->discount_type_label,
            'display_value' => $setting->display_value,
            'minimum_total_amount' => $setting->minimum_total_amount,
            'minimum_total_amount_formatted' => 'Rp ' . number_format((int) $setting->minimum_total_amount, 0, ',', '.'),
            'starts_at' => optional($setting->starts_at)->format('d M Y H:i'),
            'ends_at' => optional($setting->ends_at)->format('d M Y'),
            'priority' => $setting->priority,
            'is_default' => $setting->is_default ? 1 : 0,
            'is_active' => $setting->is_active ? 1 : 0,
            'status_label' => $setting->status_label,
            'status_class' => $setting->status_class,
            'description' => $setting->description,
            'updated_at' => optional($setting->updated_at)->format('d M Y H:i'),
            'deleted_at' => optional($setting->deleted_at)->format('d M Y H:i'),
        ];
    }

    public function syncExpiredDiscountSettings(?Carbon $at = null): int
    {
        $at ??= now();

        $expiredSettings = DiscountSetting::query()
            ->where('is_active', true)
            ->whereNotNull('ends_at')
            ->whereDate('ends_at', '<', $at->toDateString())
            ->get();

        foreach ($expiredSettings as $setting) {
            $setting->forceFill([
                'is_active' => false,
                'metadata' => array_merge($setting->metadata ?? [], [
                    'auto_inactivated_at' => $at->toDateTimeString(),
                    'auto_inactivated_reason' => 'period_ended',
                ]),
            ])->saveQuietly();
        }

        return $expiredSettings->count();
    }

    protected function normalizePayload(array $data, bool $forCreate, ?DiscountSetting $current = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $code = trim((string) ($data['code'] ?? ''));
        $description = array_key_exists('description', $data) ? trim((string) $data['description']) : null;
        $endsAt = $data['ends_at'] ?? null;

        return [
            'name' => $name,
            'code' => $code !== '' ? $code : ($forCreate ? $this->generateCode() : ''),
            'discount_type' => in_array(($data['discount_type'] ?? 'percent'), ['percent', 'fixed'], true) ? $data['discount_type'] : 'percent',
            'discount_value' => max(1, (int) ($data['discount_value'] ?? 0)),
            'minimum_total_amount' => max(0, (int) ($data['minimum_total_amount'] ?? 0)),
            'starts_at' => $forCreate ? now() : ($current?->starts_at ?? now()),
            'ends_at' => $endsAt ? Carbon::parse($endsAt)->startOfDay() : null,
            'priority' => max(0, (int) ($data['priority'] ?? 0)),
            'is_default' => $this->booleanValue($data['is_default'] ?? false),
            'is_active' => $this->booleanValue($data['is_active'] ?? true),
            'description' => $this->nullableString($description),
            'metadata' => [
                'created_by_system' => true,
                'rule_type' => 'transaction_threshold',
            ],
        ];
    }

    protected function clearDefaultFlags(?int $exceptId = null): void
    {
        DiscountSetting::query()
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->update(['is_default' => false]);
    }

    protected function generateCode(): string
    {
        $prefix = 'DISC-' . strtoupper(now()->format('My'));
        $maxSequence = 0;

        DiscountSetting::withTrashed()
            ->where('code', 'like', $prefix . '-%')
            ->pluck('code')
            ->each(function ($code) use (&$maxSequence, $prefix) {
                if (! is_string($code)) {
                    return;
                }

                if (! preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', $code, $matches)) {
                    return;
                }

                $maxSequence = max($maxSequence, (int) $matches[1]);
            });

        return $prefix . '-' . str_pad((string) ($maxSequence + 1), 3, '0', STR_PAD_LEFT);
    }

    protected function uniqueCode(string $code, ?int $ignoreId = null): string
    {
        $base = trim($code) !== '' ? trim($code) : $this->generateCode();
        $candidate = $base;
        $counter = 2;

        while (
            DiscountSetting::withTrashed()
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

    protected function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
