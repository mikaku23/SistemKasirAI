<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaxSettingStoreRequest;
use App\Http\Requests\TaxSettingUpdateRequest;
use App\Http\Services\TaxSettingService;
use App\Models\TaxSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaxSettingController extends Controller
{
    public function __construct(
        protected TaxSettingService $taxSettingService
    ) {
    }

    public function index(): View
    {
        return view('admin.tax-settings.index', array_merge($this->taxSettingService->indexData(), [
            'menu' => 'tax-settings',
        ]));
    }

    public function recycle(): View
    {
        return view('admin.tax-settings.recycle', array_merge($this->taxSettingService->indexData(), [
            'menu' => 'tax-settings',
        ]));
    }

    public function create(): View
    {
        return view('admin.tax-settings.create', [
            'menu' => 'tax-settings',
        ]);
    }

    public function store(TaxSettingStoreRequest $request): RedirectResponse
    {
        $this->taxSettingService->store($request->validated());

        return redirect()
            ->route('tax-settings.index')
            ->with('success', 'Setting pajak berhasil ditambahkan.');
    }

    public function show(TaxSetting $tax_setting): View
    {
        return view('admin.tax-settings.show', [
            'menu' => 'tax-settings',
            'taxSetting' => $tax_setting,
        ]);
    }

    public function edit(TaxSetting $tax_setting): View
    {
        return view('admin.tax-settings.edit', [
            'menu' => 'tax-settings',
            'taxSetting' => $tax_setting,
        ]);
    }

    public function update(TaxSettingUpdateRequest $request, TaxSetting $tax_setting): RedirectResponse
    {
        $this->taxSettingService->update($tax_setting, $request->validated());

        return redirect()
            ->route('tax-settings.index')
            ->with('success', 'Setting pajak berhasil diperbarui.');
    }

    public function destroy(TaxSetting $tax_setting): RedirectResponse
    {
        $this->taxSettingService->trash($tax_setting);

        return back()->with('success', 'Setting pajak dipindahkan ke recycle bin.');
    }

    public function restore(int $tax_setting): RedirectResponse
    {
        $this->taxSettingService->restore($tax_setting);

        return back()->with('success', 'Setting pajak berhasil dipulihkan.');
    }

    public function forceDelete(int $tax_setting): RedirectResponse
    {
        $this->taxSettingService->forceDelete($tax_setting);

        return back()->with('success', 'Setting pajak berhasil dihapus permanen.');
    }
}
