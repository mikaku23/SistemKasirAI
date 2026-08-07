@extends('template-admin.layout')

@section('title', 'Detail Activity Log')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/observability-logs.css') }}">
@endsection

@section('content')
@php
    $group = $group ?? [];
    $logs = $logs ?? collect();
@endphp

<section class="page-card glass-card observability-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">OBSERVABILITY</p>
            <h2>Detail Activity Log</h2>
            <p>Menampilkan seluruh aktivitas dalam group yang sama: user aktif dan hari yang dipilih.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ $backUrl ?? route('activity-logs.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="stats-grid observability-summary-grid">
        <div class="stat-card glass-card"><span>User</span><strong>{{ $group['user_name'] ?? '-' }}</strong></div>
        <div class="stat-card glass-card"><span>Tanggal</span><strong>{{ $group['date_label'] ?? '-' }}</strong></div>
        <div class="stat-card glass-card"><span>Total</span><strong>{{ $group['count'] ?? 0 }}</strong></div>
        <div class="stat-card glass-card"><span>Success</span><strong>{{ data_get($group, 'status_counts.success', 0) }}</strong></div>
        <div class="stat-card glass-card"><span>Warning</span><strong>{{ data_get($group, 'status_counts.warning', 0) }}</strong></div>
        <div class="stat-card glass-card"><span>Failed</span><strong>{{ data_get($group, 'status_counts.failed', 0) }}</strong></div>
    </div>

    <div class="detail-card glass-card observability-detail-grid" style="margin-top: 1rem;">
        <div class="wizard-form-grid">
            <label class="form-field">
                <span>User</span>
                <input type="text" value="{{ $group['user_name'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Role</span>
                <input type="text" value="{{ $group['user_role'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Location</span>
                <input type="text" value="{{ $group['user_location'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Time Range</span>
                <input type="text" value="{{ $group['time_range'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Module</span>
                <input type="text" value="{{ ! empty($group['modules']) ? implode(', ', array_map(fn ($item) => $item['value'], $group['modules'])) : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Action</span>
                <input type="text" value="{{ ! empty($group['actions']) ? implode(', ', array_map(fn ($item) => $item['value'], $group['actions'])) : '-' }}" disabled>
            </label>

            <label class="form-field form-field--full">
                <span>Status Breakdown</span>
                <input type="text" value="Success {{ data_get($group, 'status_counts.success', 0) }}, Warning {{ data_get($group, 'status_counts.warning', 0) }}, Failed {{ data_get($group, 'status_counts.failed', 0) }}" disabled>
            </label>
        </div>
    </div>

    <div class="table-card glass-card observability-table-card" style="margin-top: 1rem;">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">DETAIL DATA</p>
                <h3>Seluruh log dalam group</h3>
            </div>
        </div>

        <div class="table-responsive observability-table-wrap">
            <table class="data-table data-table--compact observability-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Time</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Menu</th>
                        <th>Route</th>
                        <th>Target</th>
                        <th>Status</th>
                        <th>IP</th>
                        <th>Description</th>
                        <th>Context</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $log->created_at_label ?? '-' }}</td>
                            <td class="observability-strong">{{ $log->action_label ?? '-' }}</td>
                            <td>{{ $log->module_label ?? '-' }}</td>
                            <td>{{ $log->menu_label ?? '-' }}</td>
                            <td>{{ $log->route_label ?? '-' }}</td>
                            <td>{{ $log->target_label ?? '-' }}</td>
                            <td>
                                <span class="status-pill {{ $log->status_badge_class ?? 'status-pill--muted' }}">
                                    {{ $log->status ?? '-' }}
                                </span>
                            </td>
                            <td>{{ $log->ip_address ?? '-' }}</td>
                            <td>
                                <div class="observability-stack">
                                    <span>{{ $log->description_short ?? '-' }}</span>
                                    <small class="observability-muted">{{ $log->user_agent ? \Illuminate\Support\Str::limit($log->user_agent, 90) : '-' }}</small>
                                </div>
                            </td>
                            <td>
                                @if (! empty($log->metadata))
                                    <span class="observability-tag">JSON <small>{{ $log->context_count ?? 0 }}</small></span>
                                @else
                                    <span class="observability-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11">
                                <div class="observability-empty">
                                    <strong>Group ini belum memiliki log.</strong>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-card glass-card observability-table-card" style="margin-top: 1rem;">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">RAW METADATA</p>
                <h3>Preview metadata JSON</h3>
            </div>
        </div>

        <pre class="observability-raw">{{ ! empty($logs) ? json_encode($logs->pluck('metadata')->values(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '[]' }}</pre>
    </div>
</section>
@endsection
