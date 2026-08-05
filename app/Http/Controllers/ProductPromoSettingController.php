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
        return view('admin.promo-settings.create', array_merge($this->promoService->referenceData(), [
            'menu' => 'promo-settings',
        ]));
    }

    public function store(ProductPromoSettingStoreRequest $request): RedirectResponse
    {
        $product = $this->promoService->store($request->validated());

        return redirect()
            ->route('promo-settings.index', ['product' => $product->id])
            ->with('success', 'Promo produk berhasil disimpan.');
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'unit']);

        return view('admin.promo-settings.show', [
            'menu' => 'promo-settings',
            'product' => $product,
            'promo' => $this->promoService->payload($product),
        ]);
    }

    public function edit(Product $product): View
    {
        $product->load(['category', 'unit']);

        return view('admin.promo-settings.edit', [
            'menu' => 'promo-settings',
            'product' => $product,
            'promo' => $this->promoService->payload($product),
        ]);
    }

    public function update(ProductPromoSettingUpdateRequest $request, Product $product): RedirectResponse
    {
        $this->promoService->update($product, $request->validated());

        return redirect()
            ->route('promo-settings.show', ['product' => $product->id])
            ->with('success', 'Promo produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->promoService->reset($product);

        return redirect()
            ->route('promo-settings.index')
            ->with('success', 'Promo produk berhasil dihapus permanen.');
    }
}