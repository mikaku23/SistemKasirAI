<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnitStoreRequest;
use App\Http\Requests\UnitUpdateRequest;
use App\Http\Services\UnitService;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function __construct(
        protected UnitService $unitService
    ) {
    }

    public function index(): View
    {
        return view('admin.units.index', array_merge($this->unitService->indexData(), [
            'menu' => 'units',
        ]));
    }

    public function recycle(): View
    {
        return view('admin.units.recycle', array_merge($this->unitService->indexData(), [
            'menu' => 'units',
        ]));
    }

    public function create(): View
    {
        return view('admin.units.create', [
            'menu' => 'units',
        ]);
    }

    public function store(UnitStoreRequest $request): RedirectResponse
    {
        $this->unitService->store($request->validated());

        return redirect()
            ->route('units.index')
            ->with('success', 'Unit berhasil ditambahkan.');
    }

    public function show(Unit $unit): View
    {
        return view('admin.units.show', [
            'menu' => 'units',
            'unit' => $unit,
        ]);
    }

    public function edit(Unit $unit): View
    {
        return view('admin.units.edit', [
            'menu' => 'units',
            'unit' => $unit,
        ]);
    }

    public function update(UnitUpdateRequest $request, Unit $unit): RedirectResponse
    {
        $this->unitService->update($unit, $request->validated());

        return redirect()
            ->route('units.index')
            ->with('success', 'Unit berhasil diperbarui.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->unitService->trash($unit);

        return back()->with('success', 'Unit dipindahkan ke recycle bin.');
    }

    public function restore(int $unit): RedirectResponse
    {
        $this->unitService->restore($unit);

        return back()->with('success', 'Unit berhasil dipulihkan.');
    }

    public function forceDelete(int $unit): RedirectResponse
    {
        $this->unitService->forceDelete($unit);

        return back()->with('success', 'Unit berhasil dihapus permanen.');
    }
}
