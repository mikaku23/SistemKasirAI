@extends('template-admin.layout')

@section('title', 'Detail System Log')

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
            <h2>Detail System Log</h2>
            <p>Menampilkan seluruh data dalam group yang sama: tanggal dan channel yang dipilih.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ $backUrl ?? route('system-logs.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="stats-grid observability-summary-grid">
        <div class="stat-card glass-card"><span>Channel</span><strong>{{ $group['channel_name'] ?? '-' }}</strong></div>
        <div class="stat-card glass-card"><span>Tanggal</span><strong>{{ $group['date_label'] ?? '-' }}</strong></div>
        <div class="stat-card glass-card"><span>Total</span><strong>{{ $group['count'] ?? 0 }}</strong></div>
        <div class="stat-card glass-card"><span>Error</span><strong>{{ data_get($group, 'level_counts.error', 0) }}</strong></div>
        <div class="stat-card glass-card"><span>Warning</span><strong>{{ data_get($group, 'level_counts.warning', 0) }}</strong></div>
        <div class="stat-card glass-card"><span>Critical</span><strong>{{ data_get($group, 'level_counts.critical', 0) }}</strong></div>
    </div>

    <div class="detail-card glass-card observability-detail-grid" style="margin-top: 1rem;">
        <div class="wizard-form-grid">
            <label class="form-field">
                <span>Channel</span>
                <input type="text" value="{{ $group['channel_name'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Date</span>
                <input type="text" value="{{ $group['date_label'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Time Range</span>
                <input type="text" value="{{ $group['time_range'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Count</span>
                <input type="text" value="{{ $group['count'] ?? 0 }}" disabled>
            </label>

            <label class="form-field">
                <span>Levels</span>
                <input type="text" value="Debug {{ data_get($group, 'level_counts.debug', 0) }}, Info {{ data_get($group, 'level_counts.info', 0) }}, Notice {{ data_get($group, 'level_counts.notice', 0) }}, Warning {{ data_get($group, 'level_counts.warning', 0) }}, Error {{ data_get($group, 'level_counts.error', 0) }}, Critical {{ data_get($group, 'level_counts.critical', 0) }}" disabled>
            </label>

            <label class="form-field">
                <span>Channel Tags</span>
                <input type="text" value="{{ ! empty($group['channels']) ? implode(', ', array_map(fn ($item) => $item['value'], $group['channels'])) : '-' }}" disabled>
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
                        <th>Level</th>
                        <th>Channel</th>
                        <th>Message</th>
                        <th>Context</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $log->created_at_label ?? '-' }}</td>
                            <td>
                                <span class="status-pill {{ $log->level_badge_class ?? 'status-pill--muted' }}">
                                    {{ $log->level_label ?? '-' }}
                                </span>
                            </td>
                            <td class="observability-strong">{{ $log->channel_label ?? '-' }}</td>
                            <td>
                                <div class="observability-stack">
                                    <span>{{ $log->message_short ?? '-' }}</span>
                                    <small class="observability-muted">{{ $log->date_label ?? '-' }}</small>
                                </div>
                            </td>
                            <td>
                                @if (! empty($log->context))
                                    <span class="observability-tag">JSON <small>{{ $log->context_count ?? 0 }}</small></span>
                                @else
                                    <span class="observability-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
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
                <p class="eyebrow">RAW CONTEXT</p>
                <h3>Preview context JSON</h3>
            </div>
        </div>

        <pre class="observability-raw">{{ ! empty($logs) ? json_encode($logs->pluck('context')->values(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '[]' }}</pre>
    </div>
</section>
@endsection
