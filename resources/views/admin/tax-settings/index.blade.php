@extends('template-admin.layout')

@section('title', 'Daftar Setting Pajak')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $taxSettings = $taxSettings ?? [];
    $taxStats = $taxStats ?? ['total' => 0, 'active' => 0, 'default' => 0, 'trashed' => 0];
@endphp

<section class="page-card glass-card">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">TAX SETTINGS</p>
            <h2>Daftar Setting Pajak</h2>
            <p>Pajak yang dipakai transaksi akan otomatis terisi dari setting default aktif.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('tax-settings.recycle') }}" class="btn btn--ghost">
                <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                Recycle
            </a>
            <a href="{{ route('tax-settings.create') }}" class="btn btn--primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Tambah Pajak
            </a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card glass-card"><span>Total</span><strong>{{ $taxStats['total'] }}</strong></div>
        <div class="stat-card glass-card"><span>Active</span><strong>{{ $taxStats['active'] }}</strong></div>
        <div class="stat-card glass-card"><span>Default</span><strong>{{ $taxStats['default'] }}</strong></div>
        <div class="stat-card glass-card"><span>Recycle</span><strong>{{ $taxStats['trashed'] }}</strong></div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">TAX</p>
                <h3>Tabel setting pajak</h3>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Default</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($taxSettings as $taxSetting)
                        <tr data-search-text="{{ strtolower(trim(($taxSetting->code ?? '') . ' ' . ($taxSetting->name ?? '') . ' ' . ($taxSetting->tax_type ?? '') . ' ' . ($taxSetting->display_value ?? ''))) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td><span class="mono-chip">{{ $taxSetting->code }}</span></td>
                            <td class="td-strong">{{ $taxSetting->name }}</td>
                            <td>{{ $taxSetting->tax_type_label }}</td>
                            <td>{{ $taxSetting->display_value }}</td>
                            <td>
                                <span class="status-pill {{ $taxSetting->is_default ? 'status-pill--success' : 'status-pill--muted' }}">
                                    {{ $taxSetting->is_default ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td>
                                <span class="status-pill {{ $taxSetting->is_active ? 'status-pill--success' : 'status-pill--muted' }}">
                                    {{ $taxSetting->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $taxSetting->updated_at ? $taxSetting->updated_at->format('d M Y H:i') : '-' }}</td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <a href="{{ route('tax-settings.show', $taxSetting->id) }}" class="icon-btn" aria-label="Show tax setting">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </a>
                                    <a href="{{ route('tax-settings.edit', $taxSetting->id) }}" class="icon-btn" aria-label="Edit tax setting">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </a>
                                    <form action="{{ route('tax-settings.destroy', $taxSetting->id) }}" method="POST" class="inline-form" data-confirm-form data-confirm-title="Hapus setting pajak?" data-confirm-message="Setting pajak ini akan dipindahkan ke recycle bin. Lanjutkan?" data-confirm-variant="danger" data-confirm-icon="fa-solid fa-trash">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Delete tax setting">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <div class="empty-state__icon">
                                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                    </div>
                                    <strong>Belum ada setting pajak.</strong>
                                    <p>Tekan tombol <b>Tambah Pajak</b> untuk membuat data pertama.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection
