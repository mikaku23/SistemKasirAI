@extends('template-admin.layout')
@section('title', 'Detail Diskon')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">@endsection
@section('content')
<section class="page-card glass-card">
  <div class="page-card__head"><div class="page-card__title"><p class="eyebrow">DISCOUNT SETTINGS</p><h2>Detail Diskon</h2><p>Aturan diskon otomatis.</p></div><div class="page-card__actions"><a href="{{ route('discount-settings.index') }}" class="btn btn--secondary">Kembali</a><a href="{{ route('discount-settings.edit', $discountSetting->id) }}" class="btn btn--primary">Edit Diskon</a></div></div>
  <div class="detail-card glass-card"><div class="wizard-form-grid">
    <label class="form-field"><span>Name</span><input type="text" value="{{ $discountSetting->name }}" disabled></label>
    <label class="form-field"><span>Code</span><input type="text" value="{{ $discountSetting->code }}" disabled></label>
    <label class="form-field"><span>Type</span><input type="text" value="{{ $discountSetting->discount_type_label }}" disabled></label>
    <label class="form-field"><span>Value</span><input type="text" value="{{ $discountSetting->display_value }}" disabled></label>
    <label class="form-field"><span>Minimum Transaction</span><input type="text" value="Rp {{ number_format((int)$discountSetting->minimum_total_amount,0,',','.') }}" disabled></label>
    <label class="form-field"><span>Period</span><input type="text" value="{{ $discountSetting->starts_at ? $discountSetting->starts_at->format('d M Y H:i') : '-' }} - {{ $discountSetting->ends_at ? $discountSetting->ends_at->format('d M Y') : '-' }}" disabled></label>
    <label class="form-field"><span>Priority</span><input type="text" value="{{ $discountSetting->priority }}" disabled></label>
    <label class="form-field"><span>Default</span><input type="text" value="{{ $discountSetting->is_default ? 'Yes' : 'No' }}" disabled></label>
    <label class="form-field"><span>Status</span><input type="text" value="{{ $discountSetting->status_label }}" disabled></label>
    <label class="form-field form-field--full"><span>Description</span><textarea rows="4" disabled>{{ $discountSetting->description ?: '-' }}</textarea></label>
  </div></div>
</section>
@endsection
