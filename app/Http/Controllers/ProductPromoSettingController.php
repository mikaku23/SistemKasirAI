<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductPromoSettingStoreRequest;
use App\Http\Requests\ProductPromoSettingUpdateRequest;
use App\Http\Services\ProductPromoSettingService;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductPromoSettingController extends Controller
{
    public function __construct(
        protected ProductPromoSettingService $promoService
    ) {
    }

    public function index(): View
    {
        return view('admin.promo-settings.index', array_merge($this->promoService->indexData(), [
            'menu' => 'promo-settings',
        ]));
    }

    public function create(): View
    {
        return view('admin.promo-settings.create', [
            'menu' => 'promo-settings',
            'products' => Product::query()->with(['category', 'unit'])->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(ProductPromoSettingStoreRequest $request): RedirectResponse
    {
        $this->promoService->store($request->validated());

        return redirect()
            ->route('promo-settings.index')
            ->with('success', 'Promo produk berhasil disimpan.');
    }

    public function show(Product $product): View
    {
        return view('admin.promo-settings.show', [
            'menu' => 'promo-settings',
            'product' => $product->load(['category', 'unit']),
        ]);
    }

    public function edit(Product $product): View
    {
        return view('admin.promo-settings.edit', [
            'menu' => 'promo-settings',
            'product' => $product->load(['category', 'unit']),
        ]);
    }

    public function update(ProductPromoSettingUpdateRequest $request, Product $product): RedirectResponse
    {
        $this->promoService->update($product, $request->validated());

        return redirect()
            ->route('promo-settings.index')
            ->with('success', 'Promo produk berhasil diperbarui.');
    }
}
