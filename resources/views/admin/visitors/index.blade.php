@extends('template-admin.layout')

@section('title', 'Visitor Logs')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/visitors.css') }}">
@endsection

@section('content')
@php
    $summary = $summary ?? [];
    $visitors = $visitors ?? collect();
    $periodOptions = $periodOptions ?? [];
    $sortOptions = $sortOptions ?? [];
    $trashedOptions = $trashedOptions ?? [];
    $userOptions = $userOptions ?? collect();
    $sourceOptions = $sourceOptions ?? collect();
@endphp

<section class="page-card glass-card visitors-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">OBSERVABILITY</p>
            <h2>Visitor Logs</h2>
            <p>Login sukses dan jejak sesi dicatat ke tabel <code>visitors</code> untuk audit dan pencarian data.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('visitors.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                Reset
            </a>

            <a href="{{ route('visitors.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn btn--secondary">
                <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i>
                CSV
            </a>
        </div>
    </div>

    <div class="stats-grid visitors-summary-grid">
        <div class="stat-card glass-card"><span>Total</span><strong>{{ number_format($summary['total'] ?? 0) }}</strong></div>
        <div class="stat-card glass-card"><span>Hari ini</span><strong>{{ number_format($summary['today'] ?? 0) }}</strong></div>
        <div class="stat-card glass-card"><span>Minggu ini</span><strong>{{ number_format($summary['week'] ?? 0) }}</strong></div>
        <div class="stat-card glass-card"><span>Bulan ini</span><strong>{{ number_format($summary['month'] ?? 0) }}</strong></div>
        <div class="stat-card glass-card"><span>IP unik</span><strong>{{ number_format($summary['unique_ips'] ?? 0) }}</strong></div>
        <div class="stat-card glass-card"><span>Aktif 15 menit</span><strong>{{ number_format($summary['active_15m'] ?? 0) }}</strong></div>
    </div>

    <div class="table-card glass-card visitors-filter-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">FILTER</p>
                <h3>Filter visitor log</h3>
                <p>Gunakan periode, user, source, soft delete, dan search untuk mempercepat pencarian.</p>
            </div>

            <div class="visitors-filter-meta">
                <span class="visitor-chip">
                    Filter aktif: <strong>{{ $activeFilterLabel ?? 'Semua data' }}</strong>
                </span>
            </div>
        </div>

        <form method="GET" action="{{ route('visitors.index') }}" class="wizard-form page-form">
            <div class="wizard-form-grid visitors-filter-grid">
                <label class="form-field">
                    <span>Pencarian</span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari token, nama, email, IP, source..." autocomplete="off">
                </label>

                <label class="form-field">
                    <span>Periode</span>
                    <select name="period" data-visitor-period>
                        @foreach ($periodOptions as $key => $label)
                            <option value="{{ $key }}" {{ request('period', 'day') === (string) $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-field" data-visitor-custom-range>
                    <span>Tanggal Mulai</span>
                    <input type="date" name="date_from" value="{{ request('date_from') }}">
                </label>

                <label class="form-field" data-visitor-custom-range>
                    <span>Tanggal Akhir</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}">
                </label>

                <label class="form-field">
                    <span>User</span>
                    <select name="name">
                        <option value="">Semua user</option>
                        @foreach ($userOptions as $userName)
                            <option value="{{ $userName }}" {{ request('name') === (string) $userName ? 'selected' : '' }}>
                                {{ $userName }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-field">
                    <span>Source</span>
                    <select name="source">
                        <option value="">Semua source</option>
                        @foreach ($sourceOptions as $source)
                            <option value="{{ $source }}" {{ request('source') === (string) $source ? 'selected' : '' }}>
                                {{ $source }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-field">
                    <span>Sort</span>
                    <select name="sort">
                        @foreach ($sortOptions as $key => $label)
                            <option value="{{ $key }}" {{ request('sort', 'latest') === (string) $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-field">
                    <span>Per halaman</span>
                    <select name="per_page">
                        @foreach ([10, 15, 25, 50, 100] as $size)
                            <option value="{{ $size }}" {{ (int) request('per_page', 15) === $size ? 'selected' : '' }}>
                                {{ $size }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-field">
                    <span>Soft delete</span>
                    <select name="trashed">
                        @foreach ($trashedOptions as $key => $label)
                            <option value="{{ $key }}" {{ request('trashed', 'active') === (string) $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <div class="form-field">
                   
                    <div class="page-card__actions visitors-filter-actions">
                        <button type="submit" class="btn btn--primary">Terapkan</button>
                        <a href="{{ route('visitors.index') }}" class="btn btn--secondary">Reset Filter</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="table-card glass-card visitors-table-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">DATA</p>
                <h3>Daftar visitor log</h3>
                <p class="section-note">{{ $activeFilterLabel ?? 'Semua data' }}</p>
            </div>
            <div class="page-card__actions">
                <span class="visitor-chip">Page {{ $visitors->currentPage() }} / {{ $visitors->lastPage() }}</span>
            </div>
        </div>

        <div class="table-responsive visitors-table-wrap">
            <table class="data-table data-table--compact visitors-table" id="visitorsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Kontak</th>
                        <th>IP</th>
                        <th>Source</th>
                        <th>Last Seen</th>
                        <th>Token</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($visitors as $visitor)
                        <tr>
                            <td>{{ $visitors->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="visitor-stack">
                                    <strong>{{ $visitor->name ?? '-' }}</strong>
                                    <span>{{ $visitor->email ?? 'Email tidak tersedia' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="visitor-stack">
                                    <span>{{ $visitor->phone ?? '-' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="visitor-stack">
                                    <strong>{{ $visitor->ip_address ?? '-' }}</strong>
                                    <span>{{ $visitor->user_agent ? str($visitor->user_agent)->limit(45) : '-' }}</span>
                                </div>
                            </td>
                            <td>{{ $visitor->source ?? '-' }}</td>
                            <td>{{ optional($visitor->last_seen_at)->format('d M Y, H:i:s') ?? '-' }}</td>
                            <td class="visitor-token-cell">
                                <code>{{ $visitor->session_token }}</code>
                            </td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <a href="{{ route('visitors.show', $visitor) }}" class="icon-btn" aria-label="Show visitor">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </a>
                                    <button type="button"
                                            class="icon-btn"
                                            data-copy-target="{{ $visitor->session_token }}"
                                            data-copy-label="Session token"
                                            aria-label="Copy session token">
                                        <i class="fa-solid fa-copy" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="visitors-empty">
                                    <strong>Belum ada visitor log.</strong>
                                    <p>Ubah filter atau jalankan login agar data masuk ke tabel <code>visitors</code>.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            {{ $visitors->links() }}
        </div>
    </div>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
<script src="{{ asset('assets/js/visitors.js') }}"></script>
@endsection
