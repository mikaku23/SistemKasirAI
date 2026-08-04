@extends('template-admin.layout')
@section('title', 'Recycle Transaksi')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">@endsection
@section('content')
<section class="page-card glass-card"><div class="page-card__head"><div class="page-card__title"><p class="eyebrow">TRANSACTIONS</p><h2>Recycle Bin</h2><p>Transaksi yang dihapus sementara.</p></div><div class="page-card__actions"><a href="{{ route('transactions.index') }}" class="btn btn--secondary">Kembali</a></div></div></section>
@endsection
@section('js')<script src="{{ asset('assets/js/layout.js') }}"></script>@endsection
