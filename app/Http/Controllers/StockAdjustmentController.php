<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockAdjustmentStoreRequest;
use App\Http\Services\StockAdjustmentService;
use App\Models\StockAdjustment;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function __construct(
        protected StockAdjustmentService $stockAdjustmentService
    ) {
    }

    public function index(): View
    {
        return view('admin.stock-adjustments.index', array_merge($this->stockAdjustmentService->indexData(), [
            'menu' => 'stock-adjustments',
        ]));
    }

    public function create(): View
    {
        return view('admin.stock-adjustments.create', array_merge($this->stockAdjustmentService->referenceData(), [
            'menu' => 'stock-adjustments',
        ]));
    }

    public function store(StockAdjustmentStoreRequest $request): RedirectResponse
    {
        $adjustment = $this->stockAdjustmentService->store($request->validated(), auth()->user());

        $difference = (int) $adjustment->difference_qty;
        $message = $difference === 0
            ? 'Pengecekan stok tersimpan. Hasilnya sudah cocok dengan sistem.'
            : 'Pengecekan stok tersimpan. Selisih ' . number_format(abs($difference), 0, ',', '.') . ' pcs menunggu verifikasi.';

        return redirect()
            ->route('stock-adjustments.index')
            ->with('success', $message)
            ->with('success_detail', $adjustment->review_status_label);
    }

    public function show(StockAdjustment $stock_adjustment): View
    {
        return view('admin.stock-adjustments.show', array_merge($this->stockAdjustmentService->referenceData(), [
            'menu' => 'stock-adjustments',
            'stockAdjustment' => $stock_adjustment->load(['product.category', 'product.unit', 'stockBatch', 'location', 'user']),
            'payload' => $this->stockAdjustmentService->payload($stock_adjustment),
        ]));
    }

    public function confirmSystemCorrect(StockAdjustment $stock_adjustment): RedirectResponse
    {
        $this->stockAdjustmentService->confirmSystemCorrect($stock_adjustment, auth()->user());

        return redirect()
            ->route('stock-adjustments.index')
            ->with('success', 'Pengecekan ditandai sebagai input yang tidak sesuai, stok sistem dipertahankan.');
    }

    public function applyCorrection(StockAdjustment $stock_adjustment): RedirectResponse
    {
        $this->stockAdjustmentService->applyCorrection($stock_adjustment, auth()->user());

        return redirect()
            ->route('stock-adjustments.index')
            ->with('success', 'Stok sistem sudah diperbarui mengikuti data fisik.');
    }
}
