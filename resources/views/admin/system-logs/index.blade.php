@extends('template-admin.layout')

@section('title', 'System Logs')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/observability-logs.css') }}">
@endsection

@section('content')
@php
    $groups = $groups ?? [];
    $summary = $summary ?? [
        'total' => 0,
        'debug' => 0,
        'info' => 0,
        'notice' => 0,
        'warning' => 0,
        'error' => 0,
        'critical' => 0,
        'unique_channels' => 0,
        'today' => 0,
    ];

    $periodOptions = $periodOptions ?? [];
    $levelOptions = $levelOptions ?? [];
    $channelOptions = $channelOptions ?? [];
@endphp

<section class="page-card glass-card observability-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">OBSERVABILITY</p>
            <h2>System Logs</h2>
            <p>Log sistem dikelompokkan berdasarkan channel dan hari agar error, warning, dan event teknis lebih mudah ditelusuri.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('system-logs.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                Reset
            </a>
        </div>
    </div>

    <div class="table-card glass-card observability-filter-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">FILTER</p>
                <h3>Filter system log</h3>
                <p>Gunakan periode, level, channel, dan pencarian cepat untuk mempermudah audit teknis.</p>
            </div>

            <div class="observability-filter-meta">
                <span class="observability-chip">
                    Periode aktif: <strong>{{ $activePeriodLabel ?? 'Hari ini' }}</strong>
                </span>
            </div>
        </div>

        <form method="GET" action="{{ route('system-logs.index') }}" class="wizard-form page-form">
            <div class="wizard-form-grid">
                <label class="form-field">
                    <span>Pencarian</span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari level, channel, message, context..." autocomplete="off">
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
                    <span>Level</span>
                    <select name="level">
                        <option value="">Semua level</option>
                        @foreach ($levelOptions as $key => $label)
                            <option value="{{ $key }}" {{ request('level') === (string) $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-field">
                    <span>Channel</span>
                    <select name="channel">
                        <option value="">Semua channel</option>
                        @foreach ($channelOptions as $key => $label)
                            <option value="{{ $key }}" {{ request('channel') === (string) $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <div class="form-field">
                    <span>Aksi cepat</span>
                    <div class="page-card__actions observability-filter-actions">
                        <button type="submit" class="btn btn--primary">Terapkan Filter</button>
                        <a href="{{ route('system-logs.index') }}" class="btn btn--secondary">Reset Filter</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="stats-grid observability-summary-grid">
        <div class="stat-card glass-card"><span>Total</span><strong>{{ $summary['total'] }}</strong></div>
        <div class="stat-card glass-card"><span>Error</span><strong>{{ $summary['error'] }}</strong></div>
        <div class="stat-card glass-card"><span>Warning</span><strong>{{ $summary['warning'] }}</strong></div>
        <div class="stat-card glass-card"><span>Critical</span><strong>{{ $summary['critical'] }}</strong></div>
        <div class="stat-card glass-card"><span>Hari Ini</span><strong>{{ $summary['today'] }}</strong></div>
        <div class="stat-card glass-card"><span>Channel Unik</span><strong>{{ $summary['unique_channels'] }}</strong></div>
    </div>

    <div class="table-card glass-card observability-table-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">GROUP DATA</p>
                <h3>System log per channel dan hari</h3>
                <p class="section-note">{{ $activeFilterLabel ?? 'Semua data' }}</p>
            </div>
            <button class="btn btn--secondary" type="button" data-action="export-table" data-target="#systemLogTable">Export</button>
        </div>

        <div class="table-responsive observability-table-wrap">
            <table class="data-table data-table--compact observability-table" id="systemLogTable" data-page-size="10">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tanggal</th>
                        <th>Channel</th>
                        <th>Time Range</th>
                        <th>Count</th>
                        <th>Debug</th>
                        <th>Info</th>
                        <th>Notice</th>
                        <th>Warning</th>
                        <th>Error</th>
                        <th>Critical</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $group)
                        <tr data-search-text="{{ strtolower(trim(($group['date_label'] ?? '') . ' ' . ($group['channel_name'] ?? '') . ' ' . implode(' ', array_map(fn ($item) => $item['value'] ?? '', $group['channels'] ?? [])))) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="observability-stack">
                                    <strong class="observability-strong">{{ $group['date_label'] ?? '-' }}</strong>
                                    <span class="observability-muted">{{ $group['time_range'] ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="observability-strong">{{ $group['channel_name'] ?? '-' }}</td>
                            <td>{{ $group['time_range'] ?? '-' }}</td>
                            <td>{{ $group['count'] ?? 0 }}</td>
                            <td>{{ data_get($group, 'level_counts.debug', 0) }}</td>
                            <td>{{ data_get($group, 'level_counts.info', 0) }}</td>
                            <td>{{ data_get($group, 'level_counts.notice', 0) }}</td>
                            <td>{{ data_get($group, 'level_counts.warning', 0) }}</td>
                            <td>{{ data_get($group, 'level_counts.error', 0) }}</td>
                            <td>{{ data_get($group, 'level_counts.critical', 0) }}</td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <a href="{{ $group['show_url'] ?? route('system-logs.index') }}" class="icon-btn" aria-label="Show system group">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row>
                            <td colspan="12">
                                <div class="observability-empty">
                                    <strong>Belum ada system log.</strong>
                                    <p>Silakan ubah filter periode atau tunggu sistem mencatat event baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination" data-table-pagination-target="#systemLogTable">
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
