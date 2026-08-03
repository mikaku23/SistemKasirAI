<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Services\ProductService;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {
    }

    public function index(): View
    {
        return view('admin.products.index', array_merge($this->productService->indexData(), [
            'menu' => 'products',
        ]));
    }

    public function recycle(): View
    {
        return view('admin.products.recycle', array_merge($this->productService->indexData(), [
            'menu' => 'products',
        ]));
    }

    public function create(): View
    {
        return view('admin.products.create', array_merge($this->productService->referenceData(), [
            'menu' => 'products',
        ]));
    }

    public function store(ProductStoreRequest $request): RedirectResponse
    {
        $this->productService->store($request->validated(), $request->file('image'));

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product): View
    {
        return view('admin.products.show', array_merge($this->productService->referenceData(), [
            'menu' => 'products',
            'product' => $product->load(['category', 'unit', 'supplier', 'location']),
        ]));
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', array_merge($this->productService->referenceData(), [
            'menu' => 'products',
            'product' => $product->load(['category', 'unit', 'supplier', 'location']),
        ]));
    }

    public function update(ProductUpdateRequest $request, Product $product): RedirectResponse
    {
        $this->productService->update($product, $request->validated(), $request->file('image'));

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->productService->trash($product);

        return back()->with('success', 'Produk dipindahkan ke recycle bin.');
    }

    public function restore(int $product): RedirectResponse
    {
        $this->productService->restore($product);

        return back()->with('success', 'Produk berhasil dipulihkan.');
    }

    public function forceDelete(int $product): RedirectResponse
    {
        $this->productService->forceDelete($product);

        return back()->with('success', 'Produk berhasil dihapus permanen.');
    }
}
