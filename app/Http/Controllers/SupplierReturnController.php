<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierReturnStoreRequest;
use App\Http\Services\SupplierReturnService;
use App\Models\Returns;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SupplierReturnController extends Controller
{
    public function __construct(
        protected SupplierReturnService $supplierReturnService
    ) {
    }

    public function index(): View
    {
        return view('admin.supplier-returns.index', array_merge($this->supplierReturnService->indexData(), [
            'menu' => 'supplier-returns',
        ]));
    }

    public function recycle(): View
    {
        return view('admin.supplier-returns.recycle', array_merge($this->supplierReturnService->indexData(), [
            'menu' => 'supplier-returns',
        ]));
    }

    public function create(): View
    {
        return view('admin.supplier-returns.create', array_merge($this->supplierReturnService->referenceData(), [
            'menu' => 'supplier-returns',
        ]));
    }

    public function store(SupplierReturnStoreRequest $request): RedirectResponse
    {
        $return = $this->supplierReturnService->store($request->validated(), auth()->user());

        return redirect()
            ->route('supplier-returns.show', $return->id)
            ->with('success', 'Supplier return berhasil diproses. Batch aktif sudah diarsipkan dan stok product diperbarui.');
    }

    public function show(Returns $supplier_return): View
    {
        return view('admin.supplier-returns.show', array_merge($this->supplierReturnService->showData($supplier_return), [
            'menu' => 'supplier-returns',
        ]));
    }

    public function destroy(Returns $supplier_return): RedirectResponse
    {
        $this->supplierReturnService->trash($supplier_return, auth()->user());

        return back()->with('success', 'Supplier return dipindahkan ke recycle bin. Batch yang diarsipkan sudah dipulihkan kembali.');
    }

    public function restore(int $supplier_return): RedirectResponse
    {
        $this->supplierReturnService->restore($supplier_return, auth()->user());

        return back()->with('success', 'Supplier return berhasil dipulihkan. Batch aktif diarsipkan kembali dan stok product disesuaikan.');
    }

    public function forceDelete(int $supplier_return): RedirectResponse
    {
        $this->supplierReturnService->forceDelete($supplier_return);

        return back()->with('success', 'Supplier return berhasil dihapus permanen.');
    }
}
