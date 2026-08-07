<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitorController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $filters = $this->normaliseFilters($request);

        $query = Visitor::query()
            ->when($filters['trashed'] === 'only', fn ($q) => $q->onlyTrashed())
            ->when($filters['trashed'] === 'all', fn ($q) => $q->withTrashed());

        $this->applyFilters($query, $filters);

        if ($filters['export'] === 'csv') {
            $rows = (clone $query)
                ->orderByDesc('last_seen_at')
                ->orderByDesc('id')
                ->get([
                    'session_token',
                    'name',
                    'phone',
                    'email',
                    'ip_address',
                    'user_agent',
                    'last_seen_at',
                    'source',
                    'metadata',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]);

            return response()->streamDownload(function () use ($rows): void {
                $handle = fopen('php://output', 'wb');

                if ($handle === false) {
                    return;
                }

                fwrite($handle, "\xEF\xBB\xBF");

                fputcsv($handle, [
                    'session_token',
                    'name',
                    'phone',
                    'email',
                    'ip_address',
                    'user_agent',
                    'last_seen_at',
                    'source',
                    'metadata',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]);

                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->session_token,
                        $row->name,
                        $row->phone,
                        $row->email,
                        $row->ip_address,
                        $row->user_agent,
                        optional($row->last_seen_at)->toDateTimeString(),
                        $row->source,
                        json_encode($row->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        optional($row->created_at)->toDateTimeString(),
                        optional($row->updated_at)->toDateTimeString(),
                        optional($row->deleted_at)->toDateTimeString(),
                    ]);
                }

                fclose($handle);
            }, 'visitors-' . now()->format('Ymd-His') . '.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $perPage = in_array($filters['per_page'], [10, 15, 25, 50, 100], true) ? $filters['per_page'] : 15;

        $visitors = (clone $query)
            ->orderByDesc('last_seen_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $summaryQuery = clone $query;

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'today' => (clone $summaryQuery)->whereDate('last_seen_at', today())->count(),
            'week' => (clone $summaryQuery)->whereBetween('last_seen_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'month' => (clone $summaryQuery)->whereYear('last_seen_at', now()->year)->whereMonth('last_seen_at', now()->month)->count(),
            'year' => (clone $summaryQuery)->whereYear('last_seen_at', now()->year)->count(),
            'unique_ips' => (clone $summaryQuery)->distinct('ip_address')->count('ip_address'),
            'active_15m' => (clone $summaryQuery)->where('last_seen_at', '>=', now()->subMinutes(15))->count(),
        ];

        $periodOptions = [
            'all' => 'Semua waktu',
            'day' => 'Hari ini',
            'week' => 'Minggu ini',
            'month' => 'Bulan ini',
            'year' => 'Tahun ini',
            'custom' => 'Rentang custom',
        ];

        $sortOptions = [
            'latest' => 'Terbaru',
            'oldest' => 'Terlama',
            'name_asc' => 'Nama A-Z',
            'name_desc' => 'Nama Z-A',
        ];

        $trashedOptions = [
            'active' => 'Aktif saja',
            'all' => 'Termasuk terhapus',
            'only' => 'Hanya terhapus',
        ];

        $userOptions = User::query()
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->values();

        $sourceOptions = Visitor::query()
            ->whereNotNull('source')
            ->distinct()
            ->orderBy('source')
            ->pluck('source')
            ->filter()
            ->values();

        return view('admin.visitors.index', [
            'menu'  => 'visitors',
            'visitors' => $visitors,
            'summary' => $summary,
            'periodOptions' => $periodOptions,
            'sortOptions' => $sortOptions,
            'trashedOptions' => $trashedOptions,
            'userOptions' => $userOptions,
            'sourceOptions' => $sourceOptions,
            'filters' => $filters,
            'activeFilterLabel' => $this->buildActiveFilterLabel($filters, $periodOptions),
        ]);
    }

    public function show(Visitor $visitor): View
    {
        return view('admin.visitors.show', [
            'menu' => 'visitors',
            'visitor' => $visitor,
        ]);
    }

    protected function normaliseFilters(Request $request): array
    {
        return [
            'q' => trim((string) $request->input('q', '')),
            'period' => (string) $request->input('period', 'day'),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'name' => trim((string) $request->input('name', '')),
            'source' => trim((string) $request->input('source', '')),
            'sort' => (string) $request->input('sort', 'latest'),
            'per_page' => (int) $request->input('per_page', 15),
            'trashed' => (string) $request->input('trashed', 'active'),
            'export' => strtolower(trim((string) $request->input('export', ''))),
        ];
    }

    protected function applyFilters($query, array $filters): void
    {
        if ($filters['q'] !== '') {
            $keyword = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']) . '%';

            $query->where(function ($subQuery) use ($keyword): void {
                $subQuery->where('session_token', 'like', $keyword)
                    ->orWhere('name', 'like', $keyword)
                    ->orWhere('phone', 'like', $keyword)
                    ->orWhere('email', 'like', $keyword)
                    ->orWhere('ip_address', 'like', $keyword)
                    ->orWhere('user_agent', 'like', $keyword)
                    ->orWhere('source', 'like', $keyword);
            });
        }

        if ($filters['name'] !== '') {
            $query->where('name', $filters['name']);
        }

        if ($filters['source'] !== '') {
            $query->where('source', $filters['source']);
        }

        $this->applyPeriodFilter($query, $filters['period'], $filters['date_from'], $filters['date_to']);

        match ($filters['sort']) {
            'oldest' => $query->orderBy('last_seen_at')->orderBy('id'),
            'name_asc' => $query->orderBy('name')->orderByDesc('last_seen_at'),
            'name_desc' => $query->orderByDesc('name')->orderByDesc('last_seen_at'),
            default => $query->orderByDesc('last_seen_at')->orderByDesc('id'),
        };
    }

    protected function applyPeriodFilter($query, string $period, string $dateFrom, string $dateTo): void
    {
        match ($period) {
            'day' => $query->whereDate('last_seen_at', today()),
            'week' => $query->whereBetween('last_seen_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereYear('last_seen_at', now()->year)->whereMonth('last_seen_at', now()->month),
            'year' => $query->whereYear('last_seen_at', now()->year),
            'custom' => $this->applyCustomPeriod($query, $dateFrom, $dateTo),
            default => null,
        };
    }

    protected function applyCustomPeriod($query, string $dateFrom, string $dateTo): void
    {
        if ($dateFrom !== '' && $dateTo !== '') {
            $query->whereBetween('last_seen_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ]);
        } elseif ($dateFrom !== '') {
            $query->whereDate('last_seen_at', '>=', $dateFrom);
        } elseif ($dateTo !== '') {
            $query->whereDate('last_seen_at', '<=', $dateTo);
        }
    }

    protected function buildActiveFilterLabel(array $filters, array $periodOptions): string
    {
        $labels = [];

        $labels[] = $periodOptions[$filters['period']] ?? 'Semua waktu';

        if ($filters['name'] !== '') {
            $labels[] = 'User: ' . $filters['name'];
        }

        if ($filters['source'] !== '') {
            $labels[] = 'Source: ' . $filters['source'];
        }

        if ($filters['q'] !== '') {
            $labels[] = 'Cari: ' . $filters['q'];
        }

        return implode(' • ', $labels);
    }
}
