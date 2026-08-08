<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Services\UserService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {
    }

    public function index(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('users', 'index', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.users.index', array_merge($this->userService->indexData(), [
            'menu' => 'users',
        ]));
    }

    public function recycle(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('users', 'recycle', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.users.recycle', array_merge($this->userService->indexData(), [
            'menu' => 'users',
        ]));
    }

    public function create(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('users', 'create', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.users.create', array_merge($this->userService->formData(), [
            'menu' => 'users',
        ]));
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('users', 'store', $request->validated(), $request->user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->userService->store($guard['payload'], $request->file('avatar'));

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function show(User $user): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('users', 'show', ['id' => $user->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.users.show', array_merge($this->userService->formData(), [
            'menu' => 'users',
            'user' => $this->userService->payload($user),
        ]));
    }

    public function edit(User $user): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('users', 'edit', ['id' => $user->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.users.edit', array_merge($this->userService->formData(), [
            'menu' => 'users',
            'user' => $this->userService->payload($user),
        ]));
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('users', 'update', $request->validated(), $request->user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->userService->update($user, $guard['payload'], $request->file('avatar'));

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('users', 'destroy', ['id' => $user->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->userService->trash($user);

        return back()->with('success', 'User dipindahkan ke recycle bin.');
    }

    public function restore(int $user): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('users', 'restore', ['id' => $user], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->userService->restore($user);

        return back()->with('success', 'User berhasil dipulihkan.');
    }

    public function forceDelete(int $user): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('users', 'forceDelete', ['id' => $user], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->userService->forceDelete($user);

        return back()->with('success', 'User berhasil dihapus permanen.');
    }
}
