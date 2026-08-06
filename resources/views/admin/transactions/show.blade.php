@extends('template-admin.layout')
@section('title', 'Detail Transaksi')
@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $transaction = $transaction ?? null;
    $financialSnapshot = is_array(data_get($transaction, 'metadata.financial_snapshot')) ? data_get($transaction, 'metadata.financial_snapshot') : [];
    $batchAllocations = is_array(data_get($transaction, 'metadata.batch_allocations')) ? data_get($transaction, 'metadata.batch_allocations') : [];
    $grossSubtotal = (int) data_get($financialSnapshot, 'gross_subtotal', $transaction->subtotal ?? 0);
    $netRevenue = (int) data_get($financialSnapshot, 'net_revenue_before_tax', max(0, (int) ($transaction->total_amount ?? 0) - (int) ($transaction->tax_amount ?? 0)));
    $cogsTotal = (int) data_get($financialSnapshot, 'cogs_total', 0);
    $grossProfit = (int) data_get($financialSnapshot, 'gross_profit_before_tax', $netRevenue - $cogsTotal);
    $marginPercent = (float) data_get($financialSnapshot, 'profit_margin_percent', $netRevenue > 0 ? round((($grossProfit) / $netRevenue) * 100, 2) : 0);
    $profitStatus = data_get($financialSnapshot, 'profit_status', $grossProfit >= 0 ? 'profit' : 'loss');
@endphp

<section class="page-card glass-card">
  <div class="page-card__head">
    <div class="page-card__title">
        <p class="eyebrow">TRANSACTIONS</p>
        <h2>Detail Transaksi</h2>
        <p>Rincian item, diskon promo, diskon transaksi otomatis, pajak, kembalian, dan snapshot laba/rugi.</p>
    </div>
    <div class="page-card__actions">
        <a href="{{ route('transactions.index') }}" class="btn btn--secondary">Kembali</a>
        <a href="{{ route('transactions.print', $transaction->id) }}" class="btn btn--primary">Print Struk</a>
    </div>
  </div>

  <div class="form-alert form-alert--info">
      <strong>Status:</strong> <span class="status-pill {{ $transaction->status_class }}">{{ $transaction->status_label }}</span>
  </div>

  <div class="stats-grid" style="margin-bottom: 16px;">
      <div class="stat-card glass-card">
          <span>Gross Subtotal</span>
          <strong>Rp {{ number_format($grossSubtotal, 0, ',', '.') }}</strong>
      </div>
      <div class="stat-card glass-card">
          <span>Net Revenue</span>
          <strong>Rp {{ number_format($netRevenue, 0, ',', '.') }}</strong>
      </div>
      <div class="stat-card glass-card">
          <span>COGS / Modal</span>
          <strong>Rp {{ number_format($cogsTotal, 0, ',', '.') }}</strong>
      </div>
      <div class="stat-card glass-card">
          <span>Gross Profit</span>
          <strong>Rp {{ number_format($grossProfit, 0, ',', '.') }}</strong>
      </div>
      <div class="stat-card glass-card">
          <span>Margin %</span>
          <strong>{{ number_format($marginPercent, 2, ',', '.') }}%</strong>
      </div>
      <div class="stat-card glass-card">
          <span>Status Laba</span>
          <strong>{{ ucfirst($profitStatus) }}</strong>
      </div>
  </div>

  <div class="detail-card glass-card" style="margin-bottom:16px;">
      <div class="wizard-form-grid">
        <label class="form-field"><span>Transaction Code</span><input type="text" value="{{ $transaction->transaction_code }}" disabled></label>
        <label class="form-field"><span>Location</span><input type="text" value="{{ optional($transaction->location)->name ?? '-' }}" disabled></label>
        <label class="form-field"><span>Cashier</span><input type="text" value="{{ optional($transaction->cashier)->name ?? '-' }}" disabled></label>
        <label class="form-field"><span>Tax Setting</span><input type="text" value="{{ optional($transaction->taxSetting)->name ?? '-' }}" disabled></label>
        <label class="form-field"><span>Discount Setting</span><input type="text" value="{{ optional($transaction->discountSetting)->name ?? '-' }}" disabled></label>
        <label class="form-field"><span>Shift</span><input type="text" value="{{ $transaction->shift_label }}" disabled></label>
        <label class="form-field"><span>Payment Method</span><input type="text" value="{{ $transaction->payment_method_label }}" disabled></label>
        <label class="form-field"><span>Transaction At</span><input type="text" value="{{ $transaction->transaction_at ? $transaction->transaction_at->format('d M Y H:i') : '-' }}" disabled></label>
        <label class="form-field"><span>Customer Name</span><input type="text" value="{{ $transaction->customer_name ?: '-' }}" disabled></label>
        <label class="form-field"><span>Customer Phone</span><input type="text" value="{{ $transaction->customer_phone ?: '-' }}" disabled></label>
      </div>
  </div>

  <div class="table-card glass-card" style="margin-bottom:16px;">
      <div class="table-responsive">
          <table class="data-table data-table--compact">
              <thead>
                  <tr>
                      <th>#</th>
                      <th>Product</th>
                      <th>Qty</th>
                      <th>Unit Price</th>
                      <th>Promo / Pcs</th>
                      <th>Item Discount</th>
                      <th>Subtotal</th>
                  </tr>
              </thead>
              <tbody>
                  @forelse($transaction->items as $item)
                      <tr>
                          <td>{{ $loop->iteration }}</td>
                          <td class="td-strong">{{ optional($item->product)->name ?? '-' }}</td>
                          <td>{{ $item->quantity }}</td>
                          <td>Rp {{ number_format((int) $item->unit_price, 0, ',', '.') }}</td>
                          <td>Rp {{ number_format((int) data_get(optional($item->product), 'effective_discount_amount', 0), 0, ',', '.') }}</td>
                          <td>Rp {{ number_format((int) $item->discount_amount, 0, ',', '.') }}</td>
                          <td>Rp {{ number_format((int) $item->subtotal, 0, ',', '.') }}</td>
                      </tr>
                  @empty
                      <tr>
                          <td colspan="7">
                              <div class="empty-state">
                                  <strong>Belum ada item transaksi.</strong>
                              </div>
                          </td>
                      </tr>
                  @endforelse
              </tbody>
          </table>
      </div>
  </div>

  <div class="detail-card glass-card" style="margin-bottom:16px;">
      <div class="wizard-form-grid">
        <label class="form-field"><span>Subtotal</span><input type="text" value="Rp {{ number_format((int) $transaction->subtotal, 0, ',', '.') }}" disabled></label>
        <label class="form-field"><span>Total Diskon Promo Item</span><input type="text" value="Rp {{ number_format((int) data_get($transaction->metadata, 'item_discount_total', 0), 0, ',', '.') }}" disabled></label>
        <label class="form-field"><span>Diskon Transaksi Otomatis</span><input type="text" value="Rp {{ number_format((int) $transaction->discount_amount, 0, ',', '.') }}" disabled></label>
        <label class="form-field"><span>Subtotal Setelah Diskon</span><input type="text" value="Rp {{ number_format(max(0, (int) $transaction->subtotal - (int) data_get($transaction->metadata, 'item_discount_total', 0) - (int) $transaction->discount_amount), 0, ',', '.') }}" disabled></label>
        <label class="form-field"><span>Total Pajak</span><input type="text" value="Rp {{ number_format((int) $transaction->tax_amount, 0, ',', '.') }}" disabled></label>
        <label class="form-field"><span>Total Tagihan</span><input type="text" value="Rp {{ number_format((int) $transaction->total_amount, 0, ',', '.') }}" disabled></label>
        <label class="form-field"><span>Uang Diterima</span><input type="text" value="Rp {{ number_format((int) $transaction->paid_amount, 0, ',', '.') }}" disabled></label>
        <label class="form-field"><span>Kembalian Uang Pelanggan</span><input type="text" value="Rp {{ number_format((int) $transaction->change_amount, 0, ',', '.') }}" disabled></label>
        <label class="form-field form-field--full"><span>Diskon Otomatis</span><textarea rows="3" disabled>{{ optional($transaction->discountSetting)->code ? optional($transaction->discountSetting)->code . ' — ' . optional($transaction->discountSetting)->name : 'Tidak ada diskon transaksi yang berlaku.' }}</textarea></label>
        <label class="form-field form-field--full"><span>Notes</span><textarea rows="3" disabled>{{ $transaction->notes ?: '-' }}</textarea></label>
      </div>
  </div>

  <div class="table-card glass-card" style="margin-bottom:16px;">
      <div class="table-card__head">
          <div>
              <p class="eyebrow">BATCH ALLOCATION</p>
              <h3>Alokasi batch yang dipakai</h3>
          </div>
      </div>
      <div class="table-responsive">
          <table class="data-table data-table--compact">
              <thead>
                  <tr>
                      <th>#</th>
                      <th>Product</th>
                      <th>Batch</th>
                      <th>Lot</th>
                      <th>Qty</th>
                      <th>Purchase Price</th>
                      <th>COGS</th>
                      <th>Revenue Share</th>
                      <th>Profit</th>
                  </tr>
              </thead>
              <tbody>
                  @forelse($batchAllocations as $allocation)
                      @php
                          $allocationBatches = data_get($allocation, 'batch_allocations', []);
                      @endphp
                      @forelse($allocationBatches as $batch)
                          <tr>
                              <td>{{ $loop->parent->iteration }}.{{ $loop->iteration }}</td>
                              <td>{{ data_get($allocation, 'product_name', '-') }}</td>
                              <td>{{ data_get($batch, 'batch_code', '-') }}</td>
                              <td>{{ data_get($batch, 'lot_number', '-') }}</td>
                              <td>{{ number_format((float) data_get($batch, 'quantity', 0), 0, ',', '.') }}</td>
                              <td>Rp {{ number_format((int) data_get($batch, 'purchase_price', 0), 0, ',', '.') }}</td>
                              <td>Rp {{ number_format((int) data_get($batch, 'cogs_amount', 0), 0, ',', '.') }}</td>
                              <td>Rp {{ number_format((int) data_get($batch, 'allocated_revenue', 0), 0, ',', '.') }}</td>
                              <td>Rp {{ number_format((int) data_get($batch, 'allocated_profit', 0), 0, ',', '.') }}</td>
                          </tr>
                      @empty
                          <tr>
                              <td colspan="9">
                                  <div class="empty-state">
                                      <strong>Belum ada alokasi batch.</strong>
                                  </div>
                              </td>
                          </tr>
                      @endforelse
                  @empty
                      <tr>
                          <td colspan="9">
                              <div class="empty-state">
                                  <strong>Belum ada data alokasi batch.</strong>
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
