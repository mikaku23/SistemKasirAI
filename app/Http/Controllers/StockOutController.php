<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockOutStoreRequest;
use App\Http\Services\StockOutService;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StockOutController extends Controller
{
    public function __construct(
        protected StockOutService $stockOutService
    ) {
    }

    public function index(): View
    {
        $this->auditActivity(__FUNCTION__);

        return view('admin.stock-outs.index', array_merge($this->stockOutService->indexData(), [
            'menu' => 'stock-outs',
        ]));
    }

    public function recycle(): View
    {
        $this->auditActivity(__FUNCTION__);

        return view('admin.stock-outs.recycle', array_merge($this->stockOutService->indexData(), [
            'menu' => 'stock-outs',
        ]));
    }

    public function create(): View
    {
        $this->auditActivity(__FUNCTION__);

        return view('admin.stock-outs.create', array_merge($this->stockOutService->referenceData(), [
            'menu' => 'stock-outs',
        ]));
    }

    public function store(StockOutStoreRequest $request): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $this->stockOutService->store($request->validated(), auth()->user());

        return redirect()
            ->route('stock-outs.index')
            ->with('success', 'Penjualan berhasil disimpan dan stok batch telah dikurangi sesuai FEFO.');
    }

    public function show(Sale $stock_out): View
    {
        $this->auditActivity(__FUNCTION__);

        return view('admin.stock-outs.show', [
            'menu' => 'stock-outs',
            'sale' => $stock_out->load(['location', 'cashier', 'items.product', 'items.stockBatch']),
        ]);
    }

    public function destroy(Sale $stock_out): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $this->stockOutService->trash($stock_out);

        return back()->with('success', 'Penjualan dipindahkan ke recycle bin dan stok sudah dipulihkan.');
    }

    public function restore(int $sale): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $this->stockOutService->restore($sale);

        return back()->with('success', 'Penjualan berhasil dipulihkan dan stok dikurangi kembali.');
    }

    public function forceDelete(int $sale): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $this->stockOutService->forceDelete($sale);

        return back()->with('success', 'Data penjualan berhasil dihapus permanen.');
    }
}
