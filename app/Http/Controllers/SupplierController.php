<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierStoreRequest;
use App\Http\Requests\SupplierUpdateRequest;
use App\Http\Services\SupplierService;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService
    ) {
    }

    public function index(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('suppliers', 'index', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.suppliers.index', array_merge($this->supplierService->indexData(), [
            'menu' => 'suppliers',
        ]));
    }

    public function recycle(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('suppliers', 'recycle', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.suppliers.recycle', array_merge($this->supplierService->indexData(), [
            'menu' => 'suppliers',
        ]));
    }

    public function create(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('suppliers', 'create', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.suppliers.create', [
            'menu' => 'suppliers',
        ]);
    }

    public function store(SupplierStoreRequest $request): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('suppliers', 'store', $request->validated(), $request->user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->supplierService->store($guard['payload']);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function show(Supplier $supplier): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('suppliers', 'show', ['id' => $supplier->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.suppliers.show', [
            'menu' => 'suppliers',
            'supplier' => $this->supplierService->payload($supplier),
        ]);
    }

    public function edit(Supplier $supplier): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('suppliers', 'edit', ['id' => $supplier->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.suppliers.edit', [
            'menu' => 'suppliers',
            'supplier' => $this->supplierService->payload($supplier),
        ]);
    }

    public function update(SupplierUpdateRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('suppliers', 'update', $request->validated(), $request->user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->supplierService->update($supplier, $guard['payload']);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('suppliers', 'destroy', ['id' => $supplier->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->supplierService->trash($supplier);

        return back()->with('success', 'Supplier dipindahkan ke recycle bin.');
    }

    public function restore(int $supplier): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('suppliers', 'restore', ['id' => $supplier], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->supplierService->restore($supplier);

        return back()->with('success', 'Supplier berhasil dipulihkan.');
    }

    public function forceDelete(int $supplier): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('suppliers', 'forceDelete', ['id' => $supplier], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->supplierService->forceDelete($supplier);

        return back()->with('success', 'Supplier berhasil dihapus permanen.');
    }
}
