@extends('template-admin.layout')
@section('title', 'Daftar Diskon Transaksi')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">@endsection
@section('content')
@php $discountSettings = $discountSettings ?? []; $discountStats = $discountStats ?? ['total'=>0,'active'=>0,'inactive'=>0,'trashed'=>0]; @endphp
<section class="page-card glass-card">
  <div class="page-card__head">
    <div class="page-card__title"><p class="eyebrow">DISCOUNT SETTINGS</p><h2>Daftar Diskon Transaksi</h2><p>Diskon otomatis berdasarkan batas transaksi.</p></div>
    <div class="page-card__actions">
      <a href="{{ route('discount-settings.recycle') }}" class="btn btn--ghost">Recycle</a>
      <a href="{{ route('discount-settings.create') }}" class="btn btn--primary">Tambah Diskon</a>
    </div>
  </div>
  <div class="stats-grid">
    <div class="stat-card glass-card"><span>Total</span><strong>{{ $discountStats['total'] }}</strong></div>
    <div class="stat-card glass-card"><span>Active</span><strong>{{ $discountStats['active'] }}</strong></div>
    <div class="stat-card glass-card"><span>Inactive</span><strong>{{ $discountStats['inactive'] }}</strong></div>
    <div class="stat-card glass-card"><span>Recycle</span><strong>{{ $discountStats['trashed'] }}</strong></div>
  </div>
  <div class="table-card glass-card">
    <div class="table-responsive">
      <table class="data-table data-table--compact" id="discountSettingsTable">
        <thead><tr><th>#</th><th>Name</th><th>Code</th><th>Type</th><th>Value</th><th>Min</th><th>Period</th><th>Status</th><th class="th-actions">Actions</th></tr></thead>
        <tbody>
          @forelse($discountSettings as $setting)
          <tr data-status="{{ $setting->is_active ? 'active' : 'inactive' }}">
            <td>{{ $loop->iteration }}</td><td class="td-strong">{{ $setting->name }}</td><td><span class="mono-chip">{{ $setting->code }}</span></td><td>{{ $setting->discount_type_label }}</td><td>{{ $setting->display_value }}</td><td>Rp {{ number_format((int)$setting->minimum_total_amount,0,',','.') }}</td><td>{{ $setting->starts_at ? $setting->starts_at->format('d M Y H:i') : '-' }} - {{ $setting->ends_at ? $setting->ends_at->format('d M Y') : '-' }}</td><td><span class="status-pill {{ $setting->status_class }}">{{ $setting->status_label }}</span></td>
            <td><div class="inline-actions"><a href="{{ route('discount-settings.show', $setting->id) }}" class="icon-btn"><i class="fa-solid fa-eye"></i></a><a href="{{ route('discount-settings.edit', $setting->id) }}" class="icon-btn"><i class="fa-solid fa-pen-to-square"></i></a><form action="{{ route('discount-settings.destroy', $setting->id) }}" method="POST" class="inline-form" data-confirm-form data-confirm-title="Hapus diskon?" data-confirm-message="Pindahkan ke recycle bin?" data-confirm-variant="danger"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE"><button type="submit" class="icon-btn icon-btn--danger"><i class="fa-solid fa-trash"></i></button></form></div></td>
          </tr>
          @empty
          <tr><td colspan="9"><div class="empty-state"><strong>Belum ada data diskon.</strong></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</section>
@endsection
@section('js')<script src="{{ asset('assets/js/layout.js') }}"></script>@endsection
