<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Http\Services\RoleService;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService
    ) {
    }

    public function index(): View
    {
        return view('admin.roles.index', array_merge($this->roleService->indexData(), [
            'menu' => 'roles',
        ]));
    }

    public function recycle(): View
    {
        return view('admin.roles.recycle', array_merge($this->roleService->indexData(), [
            'menu' => 'roles',
        ]));
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'menu' => 'roles',
        ]);
    }

    public function store(RoleStoreRequest $request): RedirectResponse
    {
        $this->roleService->store($request->validated());

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role berhasil ditambahkan.');
    }

    public function show(Role $role): View
    {
        return view('admin.roles.show', [
            'menu' => 'roles',
            'role' => $role,
        ]);
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', [
            'menu' => 'roles',
            'role' => $role,
        ]);
    }

    public function update(RoleUpdateRequest $request, Role $role): RedirectResponse
    {
        $this->roleService->update($role, $request->validated());

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->roleService->trash($role);

        return back()->with('success', 'Role dipindahkan ke recycle bin.');
    }

    public function restore(int $role): RedirectResponse
    {
        $this->roleService->restore($role);

        return back()->with('success', 'Role berhasil dipulihkan.');
    }

    public function forceDelete(int $role): RedirectResponse
    {
        $this->roleService->forceDelete($role);

        return back()->with('success', 'Role berhasil dihapus permanen.');
    }
}
