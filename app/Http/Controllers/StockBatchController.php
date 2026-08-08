<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockBatchStoreRequest;
use App\Http\Requests\StockBatchUpdateRequest;
use App\Http\Services\StockBatchService;
use App\Models\StockBatches;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StockBatchController extends Controller
{
    public function __construct(
        protected StockBatchService $stockBatchService
    ) {
    }

    public function index(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('stock-batches', 'index', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.stock-batches.index', array_merge($this->stockBatchService->indexData(), [
            'menu' => 'stock-batches',
        ]));
    }

    public function recycle(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('stock-batches', 'recycle', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.stock-batches.recycle', array_merge($this->stockBatchService->indexData(), [
            'menu' => 'stock-batches',
        ]));
    }

    public function create(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('stock-batches', 'create', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.stock-batches.create', array_merge($this->stockBatchService->referenceData(), [
            'menu' => 'stock-batches',
        ]));
    }

    public function store(StockBatchStoreRequest $request): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('stock-batches', 'store', $request->validated(), $request->user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->stockBatchService->store($guard['payload']);

        return redirect()
            ->route('stock-batches.index')
            ->with('success', 'Batch stok berhasil ditambahkan.');
    }

    public function show(StockBatches $stock_batch): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('stock-batches', 'show', ['id' => $stock_batch->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.stock-batches.show', array_merge($this->stockBatchService->referenceData(), [
            'menu' => 'stock-batches',
            'stockBatch' => $stock_batch->load(['product.category', 'product.unit', 'supplier', 'location', 'receiver']),
        ]));
    }

    public function edit(StockBatches $stock_batch): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('stock-batches', 'edit', ['id' => $stock_batch->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.stock-batches.edit', array_merge($this->stockBatchService->referenceData(), [
            'menu' => 'stock-batches',
            'stockBatch' => $stock_batch->load(['product.category', 'product.unit', 'supplier', 'location', 'receiver']),
        ]));
    }

    public function update(StockBatchUpdateRequest $request, StockBatches $stock_batch): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('stock-batches', 'update', $request->validated(), $request->user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->stockBatchService->update($stock_batch, $guard['payload']);

        return redirect()
            ->route('stock-batches.index')
            ->with('success', 'Batch stok berhasil diperbarui.');
    }

    public function destroy(StockBatches $stock_batch): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('stock-batches', 'destroy', ['id' => $stock_batch->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->stockBatchService->trash($stock_batch);

        return back()->with('success', 'Batch stok dipindahkan ke recycle bin.');
    }

    public function restore(int $stock_batch): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('stock-batches', 'restore', ['id' => $stock_batch], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->stockBatchService->restore($stock_batch);

        return back()->with('success', 'Batch stok berhasil dipulihkan.');
    }

    public function forceDelete(int $stock_batch): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('stock-batches', 'forceDelete', ['id' => $stock_batch], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->stockBatchService->forceDelete($stock_batch);

        return back()->with('success', 'Batch stok berhasil dihapus permanen.');
    }
}
