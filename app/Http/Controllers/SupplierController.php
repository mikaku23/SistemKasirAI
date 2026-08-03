<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierStoreRequest;
use App\Http\Requests\SupplierUpdateRequest;
use App\Http\Services\SupplierService;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService
    ) {
    }

    public function index(): View
    {
        return view('admin.suppliers.index', array_merge($this->supplierService->indexData(), [
            'menu' => 'suppliers',
        ]));
    }

    public function recycle(): View
    {
        return view('admin.suppliers.recycle', array_merge($this->supplierService->indexData(), [
            'menu' => 'suppliers',
        ]));
    }

    public function create(): View
    {
        return view('admin.suppliers.create', [
            'menu' => 'suppliers',
        ]);
    }

    public function store(SupplierStoreRequest $request): RedirectResponse
    {
        $this->supplierService->store($request->validated());

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function show(Supplier $supplier): View
    {
        return view('admin.suppliers.show', [
            'menu' => 'suppliers',
            'supplier' => $this->supplierService->payload($supplier),
        ]);
    }

    public function edit(Supplier $supplier): View
    {
        return view('admin.suppliers.edit', [
            'menu' => 'suppliers',
            'supplier' => $this->supplierService->payload($supplier),
        ]);
    }

    public function update(SupplierUpdateRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->supplierService->update($supplier, $request->validated());

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->supplierService->trash($supplier);

        return back()->with('success', 'Supplier dipindahkan ke recycle bin.');
    }

    public function restore(int $supplier): RedirectResponse
    {
        $this->supplierService->restore($supplier);

        return back()->with('success', 'Supplier berhasil dipulihkan.');
    }

    public function forceDelete(int $supplier): RedirectResponse
    {
        $this->supplierService->forceDelete($supplier);

        return back()->with('success', 'Supplier berhasil dihapus permanen.');
    }
}
