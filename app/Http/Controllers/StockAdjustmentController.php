<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockAdjustmentStoreRequest;
use App\Http\Services\StockAdjustmentService;
use App\Models\StockAdjustment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function __construct(
        protected StockAdjustmentService $stockAdjustmentService
    ) {
    }

    public function index(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('stock-adjustments', 'index', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.stock-adjustments.index', array_merge($this->stockAdjustmentService->indexData(), [
            'menu' => 'stock-adjustments',
        ]));
    }

    public function create(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('stock-adjustments', 'create', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.stock-adjustments.create', array_merge($this->stockAdjustmentService->referenceData(), [
            'menu' => 'stock-adjustments',
        ]));
    }

    public function store(StockAdjustmentStoreRequest $request): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('stock-adjustments', 'store', $request->validated(), $request->user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $adjustment = $this->stockAdjustmentService->store($guard['payload'], Auth::user());

        $difference = (int) $adjustment->difference_qty;
        $message = $difference === 0
            ? 'Pengecekan stok tersimpan. Hasilnya sudah cocok dengan sistem.'
            : 'Pengecekan stok tersimpan. Selisih ' . number_format(abs($difference), 0, ',', '.') . ' pcs menunggu verifikasi.';

        $this->auditSystem(
            $difference === 0 ? 'info' : 'warning',
            'stock-adjustments',
            $difference === 0 ? 'Stok fisik cocok dengan sistem' : 'Stok fisik berbeda dengan sistem',
            [
                'action' => 'stock_adjustment_created',
                'user_id' => Auth::id(),
                'metadata' => [
                    'stock_adjustment_id' => $adjustment->id,
                    'difference_qty' => $difference,
                    'review_status' => $adjustment->review_status,
                ],
            ]
        );

        return redirect()
            ->route('stock-adjustments.index')
            ->with('success', $message)
            ->with('success_detail', $adjustment->review_status_label);
    }

    public function show(StockAdjustment $stock_adjustment): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('stock-adjustments', 'show', ['id' => $stock_adjustment->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.stock-adjustments.show', array_merge($this->stockAdjustmentService->referenceData(), [
            'menu' => 'stock-adjustments',
            'stockAdjustment' => $stock_adjustment->load(['product.category', 'product.unit', 'stockBatch', 'location', 'user']),
            'payload' => $this->stockAdjustmentService->payload($stock_adjustment),
        ]));
    }

    public function confirmSystemCorrect(StockAdjustment $stock_adjustment): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('stock-adjustments', 'confirmSystemCorrect', ['id' => $stock_adjustment->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->stockAdjustmentService->confirmSystemCorrect($stock_adjustment, Auth::user());

        $this->auditSystem('info', 'stock-adjustments', 'Stok sistem dipertahankan', [
            'action' => 'confirm_system_correct',
            'target_type' => StockAdjustment::class,
            'target_id' => $stock_adjustment->id,
            'metadata' => [
                'review_status' => $stock_adjustment->review_status,
            ],
        ]);

        return redirect()
            ->route('stock-adjustments.index')
            ->with('success', 'Pengecekan ditandai sebagai input yang tidak sesuai, stok sistem dipertahankan.');
    }

    public function applyCorrection(StockAdjustment $stock_adjustment): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('stock-adjustments', 'applyCorrection', ['id' => $stock_adjustment->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->stockAdjustmentService->applyCorrection($stock_adjustment, Auth::user());

        $this->auditSystem('info', 'stock-adjustments', 'Koreksi stok sistem diterapkan', [
            'action' => 'apply_correction',
            'target_type' => StockAdjustment::class,
            'target_id' => $stock_adjustment->id,
            'metadata' => [
                'review_status' => $stock_adjustment->review_status,
            ],
        ]);

        return redirect()
            ->route('stock-adjustments.index')
            ->with('success', 'Stok sistem sudah diperbarui mengikuti data fisik.');
    }
}
