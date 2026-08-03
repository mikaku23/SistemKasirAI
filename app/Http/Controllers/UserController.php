<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Services\UserService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {
    }

    public function index(): View
    {
        return view('admin.users.index', array_merge($this->userService->indexData(), [
            'menu' => 'users',
        ]));
    }

    public function recycle(): View
    {
        return view('admin.users.recycle', array_merge($this->userService->indexData(), [
            'menu' => 'users',
        ]));
    }

    public function create(): View
    {
        return view('admin.users.create', array_merge($this->userService->formData(), [
            'menu' => 'users',
        ]));
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $this->userService->store($request->validated(), $request->file('avatar'));

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function show(User $user): View
    {
        return view('admin.users.show', array_merge($this->userService->formData(), [
            'menu' => 'users',
            'user' => $this->userService->payload($user),
        ]));
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', array_merge($this->userService->formData(), [
            'menu' => 'users',
            'user' => $this->userService->payload($user),
        ]));
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $this->userService->update($user, $request->validated(), $request->file('avatar'));

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->userService->trash($user);

        return back()->with('success', 'User dipindahkan ke recycle bin.');
    }

    public function restore(int $user): RedirectResponse
    {
        $this->userService->restore($user);

        return back()->with('success', 'User berhasil dipulihkan.');
    }

    public function forceDelete(int $user): RedirectResponse
    {
        $this->userService->forceDelete($user);

        return back()->with('success', 'User berhasil dihapus permanen.');
    }
}
