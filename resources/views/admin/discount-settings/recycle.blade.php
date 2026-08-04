@extends('template-admin.layout')
@section('title', 'Recycle Diskon')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">@endsection
@section('content')
@php $trashedDiscountSettings = $trashedDiscountSettings ?? []; @endphp
<section class="page-card glass-card">
  <div class="page-card__head"><div class="page-card__title"><p class="eyebrow">DISCOUNT SETTINGS</p><h2>Recycle Bin</h2><p>Diskon yang dihapus sementara.</p></div><div class="page-card__actions"><a href="{{ route('discount-settings.index') }}" class="btn btn--secondary">Kembali</a></div></div>
  <div class="table-card glass-card"><div class="table-responsive"><table class="data-table data-table--compact"><thead><tr><th>#</th><th>Name</th><th>Code</th><th>Value</th><th>Min</th><th>Deleted At</th><th class="th-actions">Actions</th></tr></thead><tbody>@forelse($trashedDiscountSettings as $setting)<tr><td>{{ $loop->iteration }}</td><td class="td-strong">{{ $setting->name }}</td><td><span class="mono-chip">{{ $setting->code }}</span></td><td>{{ $setting->display_value }}</td><td>Rp {{ number_format((int)$setting->minimum_total_amount,0,',','.') }}</td><td>{{ $setting->deleted_at ? $setting->deleted_at->format('d M Y H:i') : '-' }}</td><td><div class="inline-actions"><form action="{{ route('discount-settings.restore', $setting->id) }}" method="POST" class="inline-form" data-confirm-form><input type="hidden" name="_token" value="{{ csrf_token() }}"><button type="submit" class="icon-btn icon-btn--success"><i class="fa-solid fa-trash-arrow-up"></i></button></form><form action="{{ route('discount-settings.forceDelete', $setting->id) }}" method="POST" class="inline-form" data-confirm-form><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE"><button type="submit" class="icon-btn icon-btn--danger"><i class="fa-solid fa-trash-can"></i></button></form></div></td></tr>@empty<tr><td colspan="7"><div class="empty-state"><strong>Recycle bin masih kosong.</strong></div></td></tr>@endforelse</tbody></table></div></div>
</section>
@endsection
@section('js')<script src="{{ asset('assets/js/layout.js') }}"></script>@endsection
