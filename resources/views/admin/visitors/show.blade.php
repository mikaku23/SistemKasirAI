@extends('template-admin.layout')

@section('title', 'Detail Visitor')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/observability-logs.css') }}">
@endsection

@section('content')
@php
$visitor = $visitor ?? [];
$history = $history ?? collect();
$summary = $summary ?? [
'total' => 0,
'today' => 0,
'this_week' => 0,
'this_month' => 0,
'this_year' => 0,
];

$metadata = data_get($visitor, 'metadata', []);
$metadataArray = is_array($metadata) ? $metadata : (json_decode((string) $metadata, true) ?: []);
@endphp

<section class="page-card glass-card observability-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">VISITOR LOG</p>
            <h2>Detail Visitor</h2>
            <p>Menampilkan informasi sesi login, identitas perangkat, sumber akses, dan metadata yang tersimpan pada tabel visitor.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ $backUrl ?? route('visitors.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="stats-grid observability-summary-grid">
        <div class="stat-card glass-card"><span>Session Token</span><strong>{{ $visitor->session_token ?? '-' }}</strong></div>
        <div class="stat-card glass-card"><span>Nama</span><strong>{{ $visitor->name ?? '-' }}</strong></div>
        <div class="stat-card glass-card"><span>Login Hari Ini</span><strong>{{ $summary['today'] ?? 0 }}</strong></div>
        <div class="stat-card glass-card"><span>Minggu Ini</span><strong>{{ $summary['this_week'] ?? 0 }}</strong></div>
        <div class="stat-card glass-card"><span>Bulan Ini</span><strong>{{ $summary['this_month'] ?? 0 }}</strong></div>
        <div class="stat-card glass-card"><span>Tahun Ini</span><strong>{{ $summary['this_year'] ?? 0 }}</strong></div>
    </div>

    <div class="detail-card glass-card observability-detail-grid" style="margin-top: 1rem;">
        <div class="wizard-form-grid">
            <label class="form-field">
                <span>Nama</span>
                <input type="text" value="{{ $visitor->name ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Phone</span>
                <input type="text" value="{{ $visitor->phone ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Email</span>
                <input type="text" value="{{ $visitor->email ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>IP Address</span>
                <input type="text" value="{{ $visitor->ip_address ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Source</span>
                <input type="text" value="{{ $visitor->source ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Last Seen At</span>
                <input type="text" value="{{ optional($visitor->last_seen_at)->format('d M Y H:i:s') ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>User Agent</span>
                <textarea rows="3" disabled>{{ $visitor->user_agent ?? '-' }}</textarea>
            </label>

            <label class="form-field">
                <span>Metadata</span>
                <input type="text" value="{{ ! empty($metadataArray) ? 'Tersimpan' : '-' }}" disabled>
            </label>
        </div>
    </div>

    <div class="table-card glass-card observability-table-card" style="margin-top: 1rem;">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">RAW METADATA</p>
                <h3>Preview metadata JSON</h3>
            </div>
        </div>

        <pre class="observability-raw">{{ ! empty($metadataArray) ? json_encode($metadataArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '[]' }}</pre>
    </div>
</section>
@endsection