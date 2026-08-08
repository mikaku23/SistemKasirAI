<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Http\Services\RoleService;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService
    ) {
    }

    public function index(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('roles', 'index', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.roles.index', array_merge($this->roleService->indexData(), [
            'menu' => 'roles',
        ]));
    }

    public function recycle(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('roles', 'recycle', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.roles.recycle', array_merge($this->roleService->indexData(), [
            'menu' => 'roles',
        ]));
    }

    public function create(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('roles', 'create', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.roles.create', [
            'menu' => 'roles',
        ]);
    }

    public function store(RoleStoreRequest $request): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('roles', 'store', $request->validated(), $request->user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->roleService->store($guard['payload']);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role berhasil ditambahkan.');
    }

    public function show(Role $role): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('roles', 'show', ['id' => $role->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.roles.show', [
            'menu' => 'roles',
            'role' => $role,
        ]);
    }

    public function edit(Role $role): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('roles', 'edit', ['id' => $role->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.roles.edit', [
            'menu' => 'roles',
            'role' => $role,
        ]);
    }

    public function update(RoleUpdateRequest $request, Role $role): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('roles', 'update', $request->validated(), $request->user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->roleService->update($role, $guard['payload']);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('roles', 'destroy', ['id' => $role->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->roleService->trash($role);

        return back()->with('success', 'Role dipindahkan ke recycle bin.');
    }

    public function restore(int $role): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('roles', 'restore', ['id' => $role], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->roleService->restore($role);

        return back()->with('success', 'Role berhasil dipulihkan.');
    }

    public function forceDelete(int $role): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('roles', 'forceDelete', ['id' => $role], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->roleService->forceDelete($role);

        return back()->with('success', 'Role berhasil dihapus permanen.');
    }
}
