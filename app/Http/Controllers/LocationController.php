<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationStoreRequest;
use App\Http\Requests\LocationUpdateRequest;
use App\Http\Services\LocationService;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function __construct(
        protected LocationService $locationService
    ) {
    }

    public function index(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('locations', 'index', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.locations.index', array_merge($this->locationService->indexData(), [
            'menu' => 'locations',
        ]));
    }

    public function recycle(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('locations', 'recycle', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.locations.recycle', array_merge($this->locationService->indexData(), [
            'menu' => 'locations',
        ]));
    }

    public function create(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('locations', 'create', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.locations.create', [
            'menu' => 'locations',
        ]);
    }

    public function store(LocationStoreRequest $request): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('locations', 'store', $request->validated(), $request->user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->locationService->store($guard['payload']);

        return redirect()
            ->route('locations.index')
            ->with('success', 'Location berhasil ditambahkan.');
    }

    public function show(Location $location): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('locations', 'show', ['id' => $location->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.locations.show', array_merge($this->locationService->showData($location), [
            'menu' => 'locations',
            'location' => $location,
        ]));
    }

    public function edit(Location $location): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('locations', 'edit', ['id' => $location->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.locations.edit', array_merge($this->locationService->showData($location), [
            'menu' => 'locations',
            'location' => $location,
        ]));
    }

    public function update(LocationUpdateRequest $request, Location $location): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('locations', 'update', $request->validated(), $request->user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->locationService->update($location, $guard['payload']);

        return redirect()
            ->route('locations.index')
            ->with('success', 'Location berhasil diperbarui.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('locations', 'destroy', ['id' => $location->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->locationService->trash($location);

        return back()->with('success', 'Location dipindahkan ke recycle bin.');
    }

    public function restore(int $location): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('locations', 'restore', ['id' => $location], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->locationService->restore($location);

        return back()->with('success', 'Location berhasil dipulihkan.');
    }

    public function forceDelete(int $location): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('locations', 'forceDelete', ['id' => $location], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->locationService->forceDelete($location);

        return back()->with('success', 'Location berhasil dihapus permanen.');
    }
}
