<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductPromoSettingStoreRequest;
use App\Http\Requests\ProductPromoSettingUpdateRequest;
use App\Http\Services\ProductPromoSettingService;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductPromoSettingController extends Controller
{
    public function __construct(
        protected ProductPromoSettingService $promoService
    ) {
    }

    public function index(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('promo-settings', 'index', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.promo-settings.index', array_merge($this->promoService->indexData(), [
            'menu' => 'promo-settings',
        ]));
    }

    public function create(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('promo-settings', 'create', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.promo-settings.create', array_merge($this->promoService->referenceData(), [
            'menu' => 'promo-settings',
        ]));
    }

    public function store(ProductPromoSettingStoreRequest $request): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('promo-settings', 'store', $request->validated(), $request->user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $product = $this->promoService->store($guard['payload']);

        return redirect()
            ->route('promo-settings.index', ['product' => $product->id])
            ->with('success', 'Promo produk berhasil disimpan.');
    }

    public function show(Product $product): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('promo-settings', 'show', ['id' => $product->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        $product->load(['category', 'unit']);

        return view('admin.promo-settings.show', [
            'menu' => 'promo-settings',
            'product' => $product,
            'promo' => $this->promoService->payload($product),
        ]);
    }

    public function edit(Product $product): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('promo-settings', 'edit', ['id' => $product->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        $product->load(['category', 'unit']);

        return view('admin.promo-settings.edit', [
            'menu' => 'promo-settings',
            'product' => $product,
            'promo' => $this->promoService->payload($product),
        ]);
    }

    public function update(ProductPromoSettingUpdateRequest $request, Product $product): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('promo-settings', 'update', $request->validated(), $request->user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->promoService->update($product, $guard['payload']);

        return redirect()
            ->route('promo-settings.show', ['product' => $product->id])
            ->with('success', 'Promo produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('promo-settings', 'destroy', ['id' => $product->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->promoService->reset($product);

        return redirect()
            ->route('promo-settings.index')
            ->with('success', 'Promo produk berhasil dihapus permanen.');
    }
}