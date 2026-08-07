<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Http\Services\CategoryService;
use App\Models\Categories;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {
    }

    public function index(): View
    {
        $this->auditActivity(__FUNCTION__);

        return view('admin.categories.index', array_merge($this->categoryService->indexData(), [
            'menu' => 'categories',
        ]));
    }

    public function recycle(): View
    {
        $this->auditActivity(__FUNCTION__);

        return view('admin.categories.recycle', array_merge($this->categoryService->indexData(), [
            'menu' => 'categories',
        ]));
    }

    public function create(): View
    {
        $this->auditActivity(__FUNCTION__);

        return view('admin.categories.create', [
            'menu' => 'categories',
        ]);
    }

    public function store(CategoryStoreRequest $request): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $this->categoryService->store($request->validated());

        return redirect()
            ->route('categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function show(Categories $category): View
    {
        $this->auditActivity(__FUNCTION__);

        return view('admin.categories.show', [
            'menu' => 'categories',
            'category' => $category,
        ]);
    }

    public function edit(Categories $category): View
    {
        $this->auditActivity(__FUNCTION__);

        return view('admin.categories.edit', [
            'menu' => 'categories',
            'category' => $category,
        ]);
    }

    public function update(CategoryUpdateRequest $request, Categories $category): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $this->categoryService->update($category, $request->validated());

        return redirect()
            ->route('categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Categories $category): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $this->categoryService->trash($category);

        return back()->with('success', 'Kategori dipindahkan ke recycle bin.');
    }

    public function restore(int $category): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $this->categoryService->restore($category);

        return back()->with('success', 'Kategori berhasil dipulihkan.');
    }

    public function forceDelete(int $category): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $this->categoryService->forceDelete($category);

        return back()->with('success', 'Kategori berhasil dihapus permanen.');
    }
}
