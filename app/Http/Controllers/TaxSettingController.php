<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaxSettingStoreRequest;
use App\Http\Requests\TaxSettingUpdateRequest;
use App\Http\Services\TaxSettingService;
use App\Models\TaxSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TaxSettingController extends Controller
{
    public function __construct(
        protected TaxSettingService $taxSettingService
    ) {
    }

    public function index(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('tax-settings', 'index', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.tax-settings.index', array_merge($this->taxSettingService->indexData(), [
            'menu' => 'tax-settings',
        ]));
    }

    public function recycle(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('tax-settings', 'recycle', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.tax-settings.recycle', array_merge($this->taxSettingService->indexData(), [
            'menu' => 'tax-settings',
        ]));
    }

    public function create(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('tax-settings', 'create', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.tax-settings.create', [
            'menu' => 'tax-settings',
        ]);
    }

    public function store(TaxSettingStoreRequest $request): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('tax-settings', 'store', $request->validated(), $request->user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->taxSettingService->store($guard['payload']);

        return redirect()
            ->route('tax-settings.index')
            ->with('success', 'Setting pajak berhasil ditambahkan.');
    }

    public function show(TaxSetting $tax_setting): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('tax-settings', 'show', ['id' => $tax_setting->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.tax-settings.show', [
            'menu' => 'tax-settings',
            'taxSetting' => $tax_setting,
        ]);
    }

    public function edit(TaxSetting $tax_setting): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('tax-settings', 'edit', ['id' => $tax_setting->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.tax-settings.edit', [
            'menu' => 'tax-settings',
            'taxSetting' => $tax_setting,
        ]);
    }

    public function update(TaxSettingUpdateRequest $request, TaxSetting $tax_setting): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('tax-settings', 'update', $request->validated(), $request->user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->taxSettingService->update($tax_setting, $guard['payload']);

        return redirect()
            ->route('tax-settings.index')
            ->with('success', 'Setting pajak berhasil diperbarui.');
    }

    public function destroy(TaxSetting $tax_setting): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('tax-settings', 'destroy', ['id' => $tax_setting->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->taxSettingService->trash($tax_setting);

        return back()->with('success', 'Setting pajak dipindahkan ke recycle bin.');
    }

    public function restore(int $tax_setting): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('tax-settings', 'restore', ['id' => $tax_setting], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->taxSettingService->restore($tax_setting);

        return back()->with('success', 'Setting pajak berhasil dipulihkan.');
    }

    public function forceDelete(int $tax_setting): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('tax-settings', 'forceDelete', ['id' => $tax_setting], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->taxSettingService->forceDelete($tax_setting);

        return back()->with('success', 'Setting pajak berhasil dihapus permanen.');
    }
}
