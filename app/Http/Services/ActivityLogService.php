<?php

namespace App\Http\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ActivityLogService extends ObservabilityLogService
{
    public function indexData(array $filters = []): array
    {
        $currentUser = Auth::user();
        $filters = $this->normalizeFilters($filters, $currentUser?->id);
        $query = $this->buildQuery($filters, $currentUser?->id);

        $summaryQuery = clone $query;
        $logsQuery = clone $query;

        $paginator = $logsQuery->paginate(20)->withQueryString();
        $paginator->getCollection()->transform(fn (ActivityLog $log) => $this->decorateLog($log));

        $allLogs = $summaryQuery->get()->map(fn (ActivityLog $log) => $this->decorateLog($log));

        return [
            'currentUser' => $currentUser,
            'filters' => $filters,
            'logs' => $paginator,
            'groups' => $this->groupLogs($paginator->getCollection()),
            'summary' => $this->summarize($allLogs),
            'periodOptions' => $this->periodOptions(),
            'statusOptions' => $this->statusOptions(),
            'actionOptions' => $this->actionOptions(),
            'moduleOptions' => $this->moduleOptions(),
            'activePeriodLabel' => $this->activePeriodLabel($filters),
            'activeFilterLabel' => $this->activeFilterLabel($filters, $currentUser),
        ];
    }

    public function showData(array $filters = []): array
    {
        $currentUser = Auth::user();
        $date = $this->parseDate($filters['date'] ?? null) ?? now();

        $query = ActivityLog::query()
            ->with(['user.role', 'user.location'])
            ->whereDate('created_at', $date->toDateString())
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($currentUser !== null) {
            $query->where('user_id', $currentUser->id);
        }

        $logs = $query->get()->map(fn (ActivityLog $log) => $this->decorateLog($log));

        $group = $this->buildGroupFromLogs($logs, $currentUser, $date);

        return [
            'currentUser' => $currentUser,
            'group' => $group,
            'logs' => $logs,
            'backUrl' => route('activity-logs.index'),
        ];
    }

    protected function buildQuery(array $filters, ?int $currentUserId = null): Builder
    {
        $query = ActivityLog::query()
            ->with(['user.role', 'user.location'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($currentUserId !== null) {
            $query->where('user_id', $currentUserId);
        }

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
                $builder->where('action', 'like', $term)
                    ->orWhere('module', 'like', $term)
                    ->orWhere('menu', 'like', $term)
                    ->orWhere('route', 'like', $term)
                    ->orWhere('target_type', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('ip_address', 'like', $term)
                    ->orWhere('user_agent', 'like', $term)
                    ->orWhereHas('user', function (Builder $userQuery) use ($term) {
                        $userQuery->where('name', 'like', $term)
                            ->orWhere('username', 'like', $term)
                            ->orWhere('email', 'like', $term);
                    });
            });
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    protected function normalizeFilters(array $filters, ?int $currentUserId = null): array
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
            'action' => trim((string) Arr::get($filters, 'action', '')),
            'module' => trim((string) Arr::get($filters, 'module', '')),
            'status' => trim((string) Arr::get($filters, 'status', '')),
            'user_id' => $currentUserId,
        ];
    }

    protected function summarize(Collection $logs): array
    {
        return [
            'total' => $logs->count(),
            'success' => $logs->where('status', 'success')->count(),
            'failed' => $logs->where('status', 'failed')->count(),
            'warning' => $logs->where('status', 'warning')->count(),
            'unique_users' => $logs->pluck('user_id')->filter()->unique()->count(),
            'unique_modules' => $logs->pluck('module')->filter()->unique()->count(),
            'today' => $logs->filter(fn (ActivityLog $log) => $log->created_at?->isToday() ?? false)->count(),
        ];
    }

    protected function groupLogs(Collection $logs): Collection
    {
        return $logs
            ->groupBy(function (ActivityLog $log) {
                $dateKey = optional($log->created_at)?->format('Y-m-d') ?? 'unknown-date';
                $userKey = (string) ($log->user_id ?? 'system');

                return $dateKey . '|' . $userKey;
            })
            ->map(function (Collection $items, string $key) {
                $first = $items->first();
                $date = $first?->created_at ? Carbon::parse($first->created_at) : null;
                $user = $first?->user;

                $statusCounts = [
                    'success' => $items->where('status', 'success')->count(),
                    'failed' => $items->where('status', 'failed')->count(),
                    'warning' => $items->where('status', 'warning')->count(),
                ];

                return [
                    'key' => $key,
                    'date_key' => $date?->format('Y-m-d') ?? 'unknown',
                    'date_label' => $date ? $date->format('l, d M Y') : 'Tanggal tidak diketahui',
                    'time_range' => $this->timeRangeLabel($items),
                    'user_id' => $user?->id ?? $first?->user_id,
                    'user_name' => $user?->name ?? 'System / Guest',
                    'user_role' => $user?->role?->name ?? 'No role',
                    'user_location' => $user?->location?->name ?? '-',
                    'user_initials' => $user?->initials() ?? 'SY',
                    'count' => $items->count(),
                    'items' => $items->values(),
                    'status_counts' => $statusCounts,
                    'modules' => $this->groupMetaList($items, 'module'),
                    'actions' => $this->groupMetaList($items, 'action'),
                    'show_url' => route('activity-logs.show', [
                        'date' => $date?->format('Y-m-d'),
                    ]),
                ];
            })
            ->sortByDesc(fn (array $group) => $group['date_key'] . '|' . $group['user_name'])
            ->values();
    }

    protected function buildGroupFromLogs(Collection $logs, ?User $user, Carbon $date): array
    {
        $statusCounts = [
            'success' => $logs->where('status', 'success')->count(),
            'failed' => $logs->where('status', 'failed')->count(),
            'warning' => $logs->where('status', 'warning')->count(),
        ];

        return [
            'key' => $date->format('Y-m-d') . '|' . ($user?->id ?? 'system'),
            'date_key' => $date->format('Y-m-d'),
            'date_label' => $date->format('l, d M Y'),
            'time_range' => $this->timeRangeLabel($logs),
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System / Guest',
            'user_role' => $user?->role?->name ?? 'No role',
            'user_location' => $user?->location?->name ?? '-',
            'user_initials' => $user?->initials() ?? 'SY',
            'count' => $logs->count(),
            'items' => $logs->values(),
            'status_counts' => $statusCounts,
            'modules' => $this->groupMetaList($logs, 'module'),
            'actions' => $this->groupMetaList($logs, 'action'),
            'show_url' => route('activity-logs.show', [
                'date' => $date->format('Y-m-d'),
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

    protected function decorateLog(ActivityLog $log): ActivityLog
    {
        $log->setAttribute('status_badge_class', $this->statusBadgeClass($log->status, [
            'success' => 'status-pill--success',
            'warning' => 'status-pill--warning',
            'failed' => 'status-pill--danger',
        ]));

        $log->setAttribute('action_label', Str::headline(str_replace(['_', '-'], ' ', (string) $log->action)));
        $log->setAttribute('module_label', $log->module ? Str::headline(str_replace(['_', '-'], ' ', (string) $log->module)) : '-');
        $log->setAttribute('menu_label', $log->menu ?: '-');
        $log->setAttribute('route_label', $log->route ?: '-');
        $log->setAttribute('target_label', trim(($log->target_type ?: '-') . ' #' . ($log->target_id ?? '-')));
        $log->setAttribute('description_short', $this->truncate((string) ($log->description ?? '-'), 160));
        $log->setAttribute('created_at_label', $this->formatDateTime($log->created_at));
        $log->setAttribute('date_label', $this->formatDate($log->created_at));
        $log->setAttribute('user_label', $log->user?->name ?? 'System / Guest');
        $log->setAttribute('user_role_label', $log->user?->role?->name ?? 'No role');
        $log->setAttribute('user_location_label', $log->user?->location?->name ?? '-');
        $log->setAttribute('user_initials_label', $log->user?->initials() ?? 'SY');
        $log->setAttribute('context_count', is_array($log->metadata) ? count($log->metadata) : 0);

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

    protected function activeFilterLabel(array $filters, ?User $currentUser = null): string
    {
        $parts = [
            'Periode: ' . $this->activePeriodLabel($filters),
        ];

        $parts[] = 'User: ' . ($currentUser?->name ?? 'User aktif');

        if (! empty($filters['action'])) {
            $parts[] = 'Action: ' . Str::headline(str_replace(['_', '-'], ' ', $filters['action']));
        }

        if (! empty($filters['module'])) {
            $parts[] = 'Module: ' . Str::headline(str_replace(['_', '-'], ' ', $filters['module']));
        }

        if (! empty($filters['status'])) {
            $parts[] = 'Status: ' . Str::headline($filters['status']);
        }

        if (($filters['q'] ?? '') !== '') {
            $parts[] = 'Pencarian: "' . $filters['q'] . '"';
        }

        return implode(' · ', $parts);
    }

    protected function statusOptions(): array
    {
        return [
            'success' => 'Success',
            'warning' => 'Warning',
            'failed' => 'Failed',
        ];
    }

    protected function actionOptions(): array
    {
        return [
            'login' => 'Login',
            'logout' => 'Logout',
            'view' => 'View / Access',
            'create' => 'Create',
            'update' => 'Update',
            'delete' => 'Delete',
            'restore' => 'Restore',
            'force_delete' => 'Force delete',
        ];
    }

    protected function moduleOptions(): array
    {
        return [
            'auth' => 'Auth',
            'dashboard' => 'Dashboard',
            'user' => 'User',
            'product' => 'Product',
            'stock' => 'Stock',
            'transaction' => 'Transaction',
            'system' => 'System',
            'ai' => 'AI',
        ];
    }
}
