<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationStoreRequest;
use App\Http\Requests\LocationUpdateRequest;
use App\Http\Services\LocationService;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function __construct(
        protected LocationService $locationService
    ) {
    }

    public function index(): View
    {
        return view('admin.locations.index', array_merge($this->locationService->indexData(), [
            'menu' => 'locations',
        ]));
    }

    public function recycle(): View
    {
        return view('admin.locations.recycle', array_merge($this->locationService->indexData(), [
            'menu' => 'locations',
        ]));
    }

    public function create(): View
    {
        return view('admin.locations.create', [
            'menu' => 'locations',
        ]);
    }

    public function store(LocationStoreRequest $request): RedirectResponse
    {
        $this->locationService->store($request->validated());

        return redirect()
            ->route('locations.index')
            ->with('success', 'Location berhasil ditambahkan.');
    }

    public function show(Location $location): View
    {
        return view('admin.locations.show', array_merge($this->locationService->showData($location), [
            'menu' => 'locations',
            'location' => $location,
        ]));
    }

    public function edit(Location $location): View
    {
        return view('admin.locations.edit', array_merge($this->locationService->showData($location), [
            'menu' => 'locations',
            'location' => $location,
        ]));
    }

    public function update(LocationUpdateRequest $request, Location $location): RedirectResponse
    {
        $this->locationService->update($location, $request->validated());

        return redirect()
            ->route('locations.index')
            ->with('success', 'Location berhasil diperbarui.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $this->locationService->trash($location);

        return back()->with('success', 'Location dipindahkan ke recycle bin.');
    }

    public function restore(int $location): RedirectResponse
    {
        $this->locationService->restore($location);

        return back()->with('success', 'Location berhasil dipulihkan.');
    }

    public function forceDelete(int $location): RedirectResponse
    {
        $this->locationService->forceDelete($location);

        return back()->with('success', 'Location berhasil dihapus permanen.');
    }
}
