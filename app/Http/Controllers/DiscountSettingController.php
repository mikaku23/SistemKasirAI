<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiscountSettingStoreRequest;
use App\Http\Requests\DiscountSettingUpdateRequest;
use App\Http\Services\DiscountSettingService;
use App\Models\DiscountSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DiscountSettingController extends Controller
{
    public function __construct(protected DiscountSettingService $discountSettingService)
    {
    }

    public function index(): View
    {
        return view('admin.discount-settings.index', array_merge($this->discountSettingService->indexData(), ['menu' => 'discount-settings']));
    }

    public function recycle(): View
    {
        return view('admin.discount-settings.recycle', array_merge($this->discountSettingService->indexData(), ['menu' => 'discount-settings']));
    }

    public function create(): View
    {
        return view('admin.discount-settings.create', ['menu' => 'discount-settings']);
    }

    public function store(DiscountSettingStoreRequest $request): RedirectResponse
    {
        $setting = $this->discountSettingService->store($request->validated());

        return redirect()->route('discount-settings.index')
            ->with('success', 'Diskon berhasil ditambahkan.')
            ->with('success_detail', 'Kode diskon: ' . $setting->code);
    }

    public function show(DiscountSetting $discountSetting): View
    {
        return view('admin.discount-settings.show', ['menu' => 'discount-settings', 'discountSetting' => $discountSetting]);
    }

    public function edit(DiscountSetting $discountSetting): View
    {
        return view('admin.discount-settings.edit', ['menu' => 'discount-settings', 'discountSetting' => $discountSetting]);
    }

    public function update(DiscountSettingUpdateRequest $request, DiscountSetting $discountSetting): RedirectResponse
    {
        $this->discountSettingService->update($discountSetting, $request->validated());

        return redirect()->route('discount-settings.show', $discountSetting->id)
            ->with('success', 'Diskon berhasil diperbarui.');
    }

    public function destroy(DiscountSetting $discountSetting): RedirectResponse
    {
        $this->discountSettingService->trash($discountSetting);
        return back()->with('success', 'Diskon dipindahkan ke recycle bin.');
    }

    public function restore(int $discount_setting): RedirectResponse
    {
        $this->discountSettingService->restore($discount_setting);
        return back()->with('success', 'Diskon berhasil dipulihkan.');
    }

    public function forceDelete(int $discount_setting): RedirectResponse
    {
        $this->discountSettingService->forceDelete($discount_setting);
        return back()->with('success', 'Diskon berhasil dihapus permanen.');
    }
}
