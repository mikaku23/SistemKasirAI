@extends('template-admin.layout')

@section('title', 'Activity Logs')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/observability-logs.css') }}">
@endsection

@section('content')
@php
    $groups = $groups ?? [];
    $summary = $summary ?? [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'warning' => 0,
        'unique_users' => 0,
        'unique_modules' => 0,
        'today' => 0,
    ];

    $currentUser = $currentUser ?? auth()->user();
    $periodOptions = $periodOptions ?? [];
    $statusOptions = $statusOptions ?? [];
    $actionOptions = $actionOptions ?? [];
    $moduleOptions = $moduleOptions ?? [];
@endphp

<section class="page-card glass-card observability-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">OBSERVABILITY</p>
            <h2>Activity Logs</h2>
            <p>Seluruh aktivitas user yang sedang login ditampilkan otomatis dan dikelompokkan per hari.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('activity-logs.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                Reset
            </a>
        </div>
    </div>

    <div class="table-card glass-card observability-filter-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">FILTER</p>
                <h3>Filter activity log</h3>
                <p>Default menampilkan user aktif pada hari ini. Rentang waktu bisa diubah jika diperlukan.</p>
            </div>

            <div class="observability-filter-meta">
                <span class="observability-chip">
                    User aktif: <strong>{{ $currentUser?->name ?? '-' }}</strong>
                </span>
                <span class="observability-chip">
                    Periode aktif: <strong>{{ $activePeriodLabel ?? 'Hari ini' }}</strong>
                </span>
            </div>
        </div>

        <form method="GET" action="{{ route('activity-logs.index') }}" class="wizard-form page-form">
            <div class="wizard-form-grid">
                <label class="form-field">
                    <span>Pencarian</span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari action, module, route, target..." autocomplete="off">
                </label>

                <label class="form-field">
                    <span>Periode</span>
                    <select name="period" data-observability-period>
                        @foreach ($periodOptions as $key => $label)
                            <option value="{{ $key }}" {{ request('period', 'day') === (string) $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-field" data-observability-custom-range>
                    <span>Tanggal Mulai</span>
                    <input type="date" name="date_from" value="{{ request('date_from') }}">
                </label>

                <label class="form-field" data-observability-custom-range>
                    <span>Tanggal Akhir</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}">
                </label>

                <label class="form-field">
                    <span>Action</span>
                    <select name="action">
                        <option value="">Semua action</option>
                        @foreach ($actionOptions as $key => $label)
                            <option value="{{ $key }}" {{ request('action') === (string) $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-field">
                    <span>Module</span>
                    <select name="module">
                        <option value="">Semua module</option>
                        @foreach ($moduleOptions as $key => $label)
                            <option value="{{ $key }}" {{ request('module') === (string) $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-field">
                    <span>Status</span>
                    <select name="status">
                        <option value="">Semua status</option>
                        @foreach ($statusOptions as $key => $label)
                            <option value="{{ $key }}" {{ request('status') === (string) $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <div class="form-field">
                    <span>Aksi cepat</span>
                    <div class="page-card__actions observability-filter-actions">
                        <button type="submit" class="btn btn--primary">Terapkan Filter</button>
                        <a href="{{ route('activity-logs.index') }}" class="btn btn--secondary">Reset Filter</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="stats-grid observability-summary-grid">
        <div class="stat-card glass-card"><span>Total</span><strong>{{ $summary['total'] }}</strong></div>
        <div class="stat-card glass-card"><span>Success</span><strong>{{ $summary['success'] }}</strong></div>
        <div class="stat-card glass-card"><span>Warning</span><strong>{{ $summary['warning'] }}</strong></div>
        <div class="stat-card glass-card"><span>Failed</span><strong>{{ $summary['failed'] }}</strong></div>
        <div class="stat-card glass-card"><span>Hari Ini</span><strong>{{ $summary['today'] }}</strong></div>
        <div class="stat-card glass-card"><span>Module Unik</span><strong>{{ $summary['unique_modules'] }}</strong></div>
    </div>

    <div class="table-card glass-card observability-table-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">GROUP DATA</p>
                <h3>Activity log per user dan hari</h3>
                <p class="section-note">{{ $activeFilterLabel ?? 'Semua data' }}</p>
            </div>
            <button class="btn btn--secondary" type="button" data-action="export-table" data-target="#activityLogTable">Export</button>
        </div>

        <div class="table-responsive observability-table-wrap">
            <table class="data-table data-table--compact observability-table" id="activityLogTable" data-page-size="10">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tanggal</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Location</th>
                        <th>Time Range</th>
                        <th>Count</th>
                        <th>Status</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $group)
                        <tr data-search-text="{{ strtolower(trim(($group['date_label'] ?? '') . ' ' . ($group['user_name'] ?? '') . ' ' . ($group['user_role'] ?? '') . ' ' . ($group['user_location'] ?? '') . ' ' . implode(' ', array_map(fn ($item) => $item['value'] ?? '', $group['modules'] ?? [])) . ' ' . implode(' ', array_map(fn ($item) => $item['value'] ?? '', $group['actions'] ?? [])))) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="observability-stack">
                                    <strong class="observability-strong">{{ $group['date_label'] ?? '-' }}</strong>
                                    <span class="observability-muted">{{ $group['time_range'] ?? '-' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="observability-stack">
                                    <strong class="observability-strong">{{ $group['user_name'] ?? '-' }}</strong>
                                    <span class="observability-muted">ID: {{ $group['user_id'] ?? '-' }}</span>
                                </div>
                            </td>
                            <td>{{ $group['user_role'] ?? '-' }}</td>
                            <td>{{ $group['user_location'] ?? '-' }}</td>
                            <td>{{ $group['time_range'] ?? '-' }}</td>
                            <td>{{ $group['count'] ?? 0 }}</td>
                            <td>
                                <div class="observability-tags">
                                    <span class="status-pill status-pill--success">S {{ data_get($group, 'status_counts.success', 0) }}</span>
                                    <span class="status-pill status-pill--warning">W {{ data_get($group, 'status_counts.warning', 0) }}</span>
                                    <span class="status-pill status-pill--danger">F {{ data_get($group, 'status_counts.failed', 0) }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="observability-tags">
                                    @forelse (($group['modules'] ?? []) as $module)
                                        <span class="observability-tag">{{ $module['value'] }} <small>{{ $module['count'] }}</small></span>
                                    @empty
                                        <span class="observability-muted">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <div class="observability-tags">
                                    @forelse (($group['actions'] ?? []) as $action)
                                        <span class="observability-tag">{{ $action['value'] }} <small>{{ $action['count'] }}</small></span>
                                    @empty
                                        <span class="observability-muted">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <a href="{{ $group['show_url'] ?? route('activity-logs.index') }}" class="icon-btn" aria-label="Show activity group">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row>
                            <td colspan="11">
                                <div class="observability-empty">
                                    <strong>Belum ada activity log.</strong>
                                    <p>Silakan ubah filter atau tunggu aktivitas user tercatat.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination" data-table-pagination-target="#activityLogTable">
            <button type="button" class="btn btn--secondary table-pagination__btn" data-page-action="prev">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                Back
            </button>

            <div class="table-pagination__info" data-page-info>Showing 0-0 of 0</div>

            <button type="button" class="btn btn--secondary table-pagination__btn" data-page-action="next">
                Next
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
<script src="{{ asset('assets/js/observability-logs.js') }}"></script>
@endsection
