<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierReturnStoreRequest;
use App\Http\Services\SupplierReturnService;
use App\Models\Returns;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupplierReturnController extends Controller
{
    public function __construct(
        protected SupplierReturnService $supplierReturnService
    ) {
    }

    public function index(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('supplier-returns', 'index', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.supplier-returns.index', array_merge($this->supplierReturnService->indexData(), [
            'menu' => 'supplier-returns',
        ]));
    }

    public function recycle(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('supplier-returns', 'recycle', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.supplier-returns.recycle', array_merge($this->supplierReturnService->indexData(), [
            'menu' => 'supplier-returns',
        ]));
    }

    public function create(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('supplier-returns', 'create', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.supplier-returns.create', array_merge($this->supplierReturnService->referenceData(), [
            'menu' => 'supplier-returns',
        ]));
    }

    public function store(SupplierReturnStoreRequest $request): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('supplier-returns', 'store', $request->validated(), $request->user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $return = $this->supplierReturnService->store($guard['payload'], Auth::user());

        return redirect()
            ->route('supplier-returns.show', $return->id)
            ->with('success', 'Supplier return berhasil diproses. Batch aktif sudah diarsipkan dan stok product diperbarui.');
    }

    public function show(Returns $supplier_return): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('supplier-returns', 'show', ['id' => $supplier_return->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.supplier-returns.show', array_merge($this->supplierReturnService->showData($supplier_return), [
            'menu' => 'supplier-returns',
        ]));
    }

    public function destroy(Returns $supplier_return): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('supplier-returns', 'destroy', ['id' => $supplier_return->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->supplierReturnService->trash($supplier_return, Auth::user());

        return back()->with('success', 'Supplier return dipindahkan ke recycle bin. Batch yang diarsipkan sudah dipulihkan kembali.');
    }

    public function restore(int $supplier_return): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('supplier-returns', 'restore', ['id' => $supplier_return], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->supplierReturnService->restore($supplier_return, Auth::user());

        return back()->with('success', 'Supplier return berhasil dipulihkan. Batch aktif diarsipkan kembali dan stok product disesuaikan.');
    }

    public function forceDelete(int $supplier_return): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('supplier-returns', 'forceDelete', ['id' => $supplier_return], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->supplierReturnService->forceDelete($supplier_return);

        return back()->with('success', 'Supplier return berhasil dihapus permanen.');
    }
}
