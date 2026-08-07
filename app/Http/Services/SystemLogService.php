<?php

namespace App\Http\Services;

use App\Models\SystemLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SystemLogService extends ObservabilityLogService
{
    public function indexData(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);
        $query = $this->buildQuery($filters);

        $summaryQuery = clone $query;
        $logsQuery = clone $query;

        $paginator = $logsQuery->paginate(20)->withQueryString();
        $paginator->getCollection()->transform(fn (SystemLog $log) => $this->decorateLog($log));

        $allLogs = $summaryQuery->get()->map(fn (SystemLog $log) => $this->decorateLog($log));

        return [
            'filters' => $filters,
            'logs' => $paginator,
            'groups' => $this->groupLogs($paginator->getCollection()),
            'summary' => $this->summarize($allLogs),
            'periodOptions' => $this->periodOptions(),
            'levelOptions' => $this->levelOptions(),
            'channelOptions' => $this->channelOptions(),
            'activePeriodLabel' => $this->activePeriodLabel($filters),
            'activeFilterLabel' => $this->activeFilterLabel($filters),
        ];
    }

    public function showData(array $filters = []): array
    {
        $date = $this->parseDate($filters['date'] ?? null) ?? now();
        $channel = trim((string) ($filters['channel'] ?? ''));

        $query = SystemLog::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->whereDate('created_at', $date->toDateString());

        if ($channel !== '') {
            $query->whereRaw('LOWER(channel) = ?', [Str::lower($channel)]);
        }

        $logs = $query->get()->map(fn (SystemLog $log) => $this->decorateLog($log));

        $group = $this->buildGroupFromLogs($logs, $date, $channel);

        return [
            'group' => $group,
            'logs' => $logs,
            'backUrl' => route('system-logs.index'),
        ];
    }

    protected function buildQuery(array $filters): Builder
    {
        $query = SystemLog::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $range = $this->resolveDateRange($filters['period'], $filters['date_from'] ?? null, $filters['date_to'] ?? null);

        if ($range['start'] !== null) {
            $query->where('created_at', '>=', $range['start']);
        }

        if ($range['end'] !== null) {
            $query->where('created_at', '<=', $range['end']);
        }

        if (($search = $filters['q'] ?? '') !== '') {
            $term = '%' . $search . '%';

            $query->where(function (Builder $builder) use ($term) {
                $builder->where('level', 'like', $term)
                    ->orWhere('channel', 'like', $term)
                    ->orWhere('message', 'like', $term)
                    ->orWhere('context', 'like', $term);
            });
        }

        if (! empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (! empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        return $query;
    }

    protected function normalizeFilters(array $filters): array
    {
        $period = (string) Arr::get($filters, 'period', 'day');
        $dateFrom = Arr::get($filters, 'date_from');
        $dateTo = Arr::get($filters, 'date_to');

        if ($dateFrom || $dateTo) {
            $period = 'custom';
        }

        return [
            'q' => trim((string) Arr::get($filters, 'q', '')),
            'period' => in_array($period, ['all', 'day', 'week', 'month', 'year', 'custom'], true) ? $period : 'day',
            'date_from' => trim((string) $dateFrom),
            'date_to' => trim((string) $dateTo),
            'level' => trim((string) Arr::get($filters, 'level', '')),
            'channel' => trim((string) Arr::get($filters, 'channel', '')),
        ];
    }

    protected function summarize(Collection $logs): array
    {
        return [
            'total' => $logs->count(),
            'debug' => $logs->where('level', 'debug')->count(),
            'info' => $logs->where('level', 'info')->count(),
            'notice' => $logs->where('level', 'notice')->count(),
            'warning' => $logs->where('level', 'warning')->count(),
            'error' => $logs->where('level', 'error')->count(),
            'critical' => $logs->where('level', 'critical')->count(),
            'unique_channels' => $logs->pluck('channel')->filter()->unique()->count(),
            'today' => $logs->filter(fn (SystemLog $log) => $log->created_at?->isToday() ?? false)->count(),
        ];
    }

    protected function groupLogs(Collection $logs): Collection
    {
        return $logs
            ->groupBy(function (SystemLog $log) {
                $dateKey = optional($log->created_at)?->format('Y-m-d') ?? 'unknown-date';
                $channelKey = strtolower((string) ($log->channel ?: 'default'));

                return $dateKey . '|' . $channelKey;
            })
            ->map(function (Collection $items, string $key) {
                $first = $items->first();
                $date = $first?->created_at ? Carbon::parse($first->created_at) : null;

                $levelCounts = [
                    'debug' => $items->where('level', 'debug')->count(),
                    'info' => $items->where('level', 'info')->count(),
                    'notice' => $items->where('level', 'notice')->count(),
                    'warning' => $items->where('level', 'warning')->count(),
                    'error' => $items->where('level', 'error')->count(),
                    'critical' => $items->where('level', 'critical')->count(),
                ];

                return [
                    'key' => $key,
                    'date_key' => $date?->format('Y-m-d') ?? 'unknown',
                    'date_label' => $date ? $date->format('l, d M Y') : 'Tanggal tidak diketahui',
                    'time_range' => $this->timeRangeLabel($items),
                    'channel_name' => $first?->channel ?: 'default',
                    'count' => $items->count(),
                    'items' => $items->values(),
                    'level_counts' => $levelCounts,
                    'channels' => $this->groupMetaList($items, 'channel'),
                    'show_url' => route('system-logs.show', [
                        'date' => $date?->format('Y-m-d'),
                        'channel' => $first?->channel ?: 'default',
                    ]),
                ];
            })
            ->sortByDesc(fn (array $group) => $group['date_key'] . '|' . $group['channel_name'])
            ->values();
    }

    protected function buildGroupFromLogs(Collection $logs, Carbon $date, string $channel): array
    {
        $levelCounts = [
            'debug' => $logs->where('level', 'debug')->count(),
            'info' => $logs->where('level', 'info')->count(),
            'notice' => $logs->where('level', 'notice')->count(),
            'warning' => $logs->where('level', 'warning')->count(),
            'error' => $logs->where('level', 'error')->count(),
            'critical' => $logs->where('level', 'critical')->count(),
        ];

        return [
            'key' => $date->format('Y-m-d') . '|' . $channel,
            'date_key' => $date->format('Y-m-d'),
            'date_label' => $date->format('l, d M Y'),
            'time_range' => $this->timeRangeLabel($logs),
            'channel_name' => $channel !== '' ? $channel : 'default',
            'count' => $logs->count(),
            'items' => $logs->values(),
            'level_counts' => $levelCounts,
            'channels' => $this->groupMetaList($logs, 'channel'),
            'show_url' => route('system-logs.show', [
                'date' => $date->format('Y-m-d'),
                'channel' => $channel !== '' ? $channel : 'default',
            ]),
        ];
    }

    protected function timeRangeLabel(Collection $items): string
    {
        $first = $items->sortBy('created_at')->first()?->created_at;
        $last = $items->sortByDesc('created_at')->first()?->created_at;

        if ($first !== null && $last !== null) {
            return Carbon::parse($first)->format('H:i') . ' - ' . Carbon::parse($last)->format('H:i');
        }

        return '-';
    }

    protected function decorateLog(SystemLog $log): SystemLog
    {
        $log->setAttribute('level_badge_class', $this->statusBadgeClass($log->level, [
            'debug' => 'status-pill--muted',
            'info' => 'status-pill--info',
            'notice' => 'status-pill--info',
            'warning' => 'status-pill--warning',
            'error' => 'status-pill--danger',
            'critical' => 'status-pill--danger',
        ]));

        $log->setAttribute('level_label', Str::headline((string) $log->level));
        $log->setAttribute('channel_label', $log->channel ?: 'default');
        $log->setAttribute('message_short', $this->truncate((string) $log->message, 160));
        $log->setAttribute('created_at_label', $this->formatDateTime($log->created_at));
        $log->setAttribute('date_label', $this->formatDate($log->created_at));
        $log->setAttribute('context_count', is_array($log->context) ? count($log->context) : 0);

        return $log;
    }

    protected function activePeriodLabel(array $filters): string
    {
        return match ($filters['period'] ?? 'day') {
            'day' => 'Hari ini',
            'week' => 'Minggu ini',
            'month' => 'Bulan ini',
            'year' => 'Tahun ini',
            'custom' => $this->customRangeLabel($filters),
            default => 'Semua data',
        };
    }

    protected function activeFilterLabel(array $filters): string
    {
        $parts = [
            'Periode: ' . $this->activePeriodLabel($filters),
        ];

        if (! empty($filters['level'])) {
            $parts[] = 'Level: ' . Str::headline($filters['level']);
        }

        if (! empty($filters['channel'])) {
            $parts[] = 'Channel: ' . $filters['channel'];
        }

        if (($filters['q'] ?? '') !== '') {
            $parts[] = 'Pencarian: "' . $filters['q'] . '"';
        }

        return implode(' · ', $parts);
    }

    protected function levelOptions(): array
    {
        return [
            'debug' => 'Debug',
            'info' => 'Info',
            'notice' => 'Notice',
            'warning' => 'Warning',
            'error' => 'Error',
            'critical' => 'Critical',
        ];
    }

    protected function channelOptions(): array
    {
        return [
            'auth' => 'Auth',
            'database' => 'Database',
            'cache' => 'Cache',
            'queue' => 'Queue',
            'api' => 'API',
            'system' => 'System',
            'ai' => 'AI',
            'default' => 'Default',
        ];
    }
}
