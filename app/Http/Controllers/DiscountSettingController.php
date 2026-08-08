<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiscountSettingStoreRequest;
use App\Http\Requests\DiscountSettingUpdateRequest;
use App\Http\Services\DiscountSettingService;
use App\Models\DiscountSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DiscountSettingController extends Controller
{
    public function __construct(protected DiscountSettingService $discountSettingService) {}

    public function index(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('discount-settings', 'index', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.discount-settings.index', array_merge($this->discountSettingService->indexData(), ['menu' => 'discount-settings']));
    }

    public function recycle(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('discount-settings', 'recycle', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.discount-settings.recycle', array_merge($this->discountSettingService->indexData(), ['menu' => 'discount-settings']));
    }

    public function create(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('discount-settings', 'create', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.discount-settings.create', ['menu' => 'discount-settings']);
    }

    public function store(DiscountSettingStoreRequest $request): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('discount-settings', 'store', $request->validated(), Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $setting = $this->discountSettingService->store($guard['payload']);

        return redirect()->route('discount-settings.index')
            ->with('success', 'Diskon berhasil ditambahkan.')
            ->with('success_detail', 'Kode diskon: ' . $setting->code);
    }

    public function show(DiscountSetting $discountSetting): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('discount-settings', 'show', ['id' => $discountSetting->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.discount-settings.show', ['menu' => 'discount-settings', 'discountSetting' => $discountSetting]);
    }

    public function edit(DiscountSetting $discountSetting): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('discount-settings', 'edit', ['id' => $discountSetting->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.discount-settings.edit', ['menu' => 'discount-settings', 'discountSetting' => $discountSetting]);
    }

    public function update(DiscountSettingUpdateRequest $request, DiscountSetting $discountSetting): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('discount-settings', 'update', $request->validated(), Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->discountSettingService->update($discountSetting, $guard['payload']);

        return redirect()->route('discount-settings.show', $discountSetting->id)
            ->with('success', 'Diskon berhasil diperbarui.');
    }

    public function destroy(DiscountSetting $discountSetting): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('discount-settings', 'destroy', ['id' => $discountSetting->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->discountSettingService->trash($discountSetting);
        return back()->with('success', 'Diskon dipindahkan ke recycle bin.');
    }

    public function restore(int $discount_setting): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('discount-settings', 'restore', ['id' => $discount_setting], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->discountSettingService->restore($discount_setting);
        return back()->with('success', 'Diskon berhasil dipulihkan.');
    }

    public function forceDelete(int $discount_setting): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('discount-settings', 'forceDelete', ['id' => $discount_setting], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->discountSettingService->forceDelete($discount_setting);
        return back()->with('success', 'Diskon berhasil dihapus permanen.');
    }
}
