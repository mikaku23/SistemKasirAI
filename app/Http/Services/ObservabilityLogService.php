<?php

namespace App\Http\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class ObservabilityLogService
{
    protected function normalizePeriod(array $filters, array $allowed = ['all', 'day', 'week', 'month', 'year', 'custom']): string
    {
        $period = trim((string) ($filters['period'] ?? 'day'));

        if (($filters['date_from'] ?? null) !== null || ($filters['date_to'] ?? null) !== null) {
            $period = 'custom';
        }

        return in_array($period, $allowed, true) ? $period : 'day';
    }

    protected function resolveDateRange(string $period, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        return match ($period) {
            'day' => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            'week' => [
                'start' => now()->startOfWeek(),
                'end' => now()->endOfWeek(),
            ],
            'month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
            'year' => [
                'start' => now()->startOfYear(),
                'end' => now()->endOfYear(),
            ],
            'custom' => $this->resolveCustomRange($dateFrom, $dateTo),
            default => [
                'start' => null,
                'end' => null,
            ],
        };
    }

    protected function resolveCustomRange(?string $dateFrom, ?string $dateTo): array
    {
        $from = $this->parseDate($dateFrom);
        $to = $this->parseDate($dateTo);

        if ($from === null && $to === null) {
            $from = now()->startOfDay();
            $to = now()->endOfDay();
        } elseif ($from !== null && $to === null) {
            $to = $from->copy()->endOfDay();
        } elseif ($from === null && $to !== null) {
            $from = $to->copy()->startOfDay();
        }

        return [
            'start' => $from?->startOfDay(),
            'end' => $to?->endOfDay(),
        ];
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function nullableInt(mixed $value): ?int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return (int) $value;
    }

    protected function formatDateTime(mixed $value): string
    {
        if ($value === null) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('d M Y H:i:s');
        } catch (\Throwable) {
            return '-';
        }
    }

    protected function formatDate(mixed $value): string
    {
        if ($value === null) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('d M Y');
        } catch (\Throwable) {
            return '-';
        }
    }

    protected function truncate(string $text, int $limit = 140): string
    {
        $text = trim($text);

        if ($text === '') {
            return '-';
        }

        return Str::limit($text, $limit);
    }

    protected function customRangeLabel(array $filters): string
    {
        $from = trim((string) ($filters['date_from'] ?? ''));
        $to = trim((string) ($filters['date_to'] ?? ''));

        if ($from !== '' && $to !== '') {
            return 'Rentang ' . Carbon::parse($from)->format('d M Y') . ' - ' . Carbon::parse($to)->format('d M Y');
        }

        if ($from !== '') {
            return 'Mulai ' . Carbon::parse($from)->format('d M Y');
        }

        if ($to !== '') {
            return 'Sampai ' . Carbon::parse($to)->format('d M Y');
        }

        return 'Rentang waktu';
    }

    protected function statusBadgeClass(?string $value, array $map = []): string
    {
        $value = strtolower(trim((string) $value));

        return $map[$value] ?? 'status-pill--muted';
    }

    protected function groupMetaList(Collection $items, string $field): array
    {
        return $items
            ->pluck($field)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => trim((string) $value))
            ->countBy()
            ->map(fn (int $count, string $value) => [
                'value' => $value,
                'count' => $count,
            ])
            ->values()
            ->all();
    }

    protected function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    protected function prettyJson(mixed $value): string
    {
        $payload = $this->decodeJson($value);

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function periodOptions(): array
    {
        return [
            'all' => 'Semua data',
            'day' => 'Hari ini',
            'week' => 'Minggu ini',
            'month' => 'Bulan ini',
            'year' => 'Tahun ini',
            'custom' => 'Rentang waktu',
        ];
    }
}
