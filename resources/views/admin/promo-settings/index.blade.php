@extends('template-admin.layout')

@section('title', 'Promo Setting')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $promoProducts = $promoProducts ?? [];
    $promoStats = $promoStats ?? ['total' => 0, 'active' => 0, 'inactive' => 0, 'with_discount' => 0];
@endphp

<section class="page-card glass-card">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">PROMO SETTINGS</p>
            <h2>Daftar Promo Product</h2>
            <p>Diskon promo disimpan langsung ke data product dan akan dipakai otomatis pada transaksi.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('promo-settings.create') }}" class="btn btn--primary"><i class="fa-solid fa-plus"></i> Set Promo</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card glass-card"><span>Total</span><strong>{{ $promoStats['total'] }}</strong></div>
        <div class="stat-card glass-card"><span>Active</span><strong>{{ $promoStats['active'] }}</strong></div>
        <div class="stat-card glass-card"><span>Inactive</span><strong>{{ $promoStats['inactive'] }}</strong></div>
        <div class="stat-card glass-card"><span>With Discount</span><strong>{{ $promoStats['with_discount'] }}</strong></div>
    </div>

    <div class="table-card glass-card">
        <div class="table-responsive">
            <table class="data-table data-table--compact">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Sale Price</th>
                        <th>Promo / Pcs</th>
                        <th>Effective Price</th>
                        <th>Status</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($promoProducts as $product)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="td-strong">{{ $product->name }}</td>
                            <td>{{ optional($product->category)->name ?? '-' }}</td>
                            <td>Rp {{ number_format((int) $product->sale_price, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((int) $product->effective_discount_amount, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format(max(0, (int) $product->sale_price - (int) $product->effective_discount_amount), 0, ',', '.') }}</td>
                            <td><span class="status-pill {{ $product->promo_discount_is_active ? 'status-pill--success' : 'status-pill--muted' }}">{{ $product->promo_discount_is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <a href="{{ route('promo-settings.show', $product->id) }}" class="icon-btn"><i class="fa-solid fa-eye"></i></a>
                                    <a href="{{ route('promo-settings.edit', $product->id) }}" class="icon-btn"><i class="fa-solid fa-pen-to-square"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="empty-state"><strong>Belum ada promo.</strong></div></td></tr>
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
