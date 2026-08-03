@extends('template-admin.layout')

@section('title', 'Edit Kategori')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
<section class="page-card glass-card category-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">CATEGORIES</p>
            <h2>Edit Kategori</h2>
            <p>Ubah data kategori dengan alur bertahap sebelum disimpan kembali.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('categories.index') }}" class="btn btn--secondary"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Kembali</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="form-alert form-alert--danger">
            <strong>Periksa kembali data yang diisi.</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('categories.update', $category->id) }}" method="POST" class="wizard-form page-form" data-step-form data-draft-key="categories:edit:{{ $category->id }}" data-confirm-form data-confirm-title="Simpan perubahan?" data-confirm-message="Data kategori yang diubah akan disimpan ke database. Lanjutkan?" data-confirm-variant="warn" data-confirm-icon="fa-solid fa-floppy-disk">
        @csrf
        @method('PUT')
        <input type="hidden" name="id" value="{{ $category->id }}">

        <div class="stepper"><span class="step active" data-step-indicator="1">1</span><span class="step" data-step-indicator="2">2</span><span class="step" data-step-indicator="3">3</span></div>

        <div class="wizard-body">
            <section class="wizard-step active" data-step="1">
                <div class="wizard-step__head"><h4>Basic Info</h4><p>Ubah identity kategori dengan aman.</p></div>
                <div class="wizard-form-grid">
                    <label class="form-field"><span>Name</span><input type="text" name="name" value="{{ old('name', $category->name) }}" placeholder="Bahan Pokok" required autocomplete="off" data-autoslug-source="name" data-autoslug-target="slug"></label>
                    <label class="form-field"><span>SKU</span><input type="text" name="sku" value="{{ old('sku', $category->sku) }}" placeholder="BHP" required autocomplete="off"><small>Kode dasar kategori, misalnya BHP.</small></label>
                    <label class="form-field"><span>Slug</span><input type="text" name="slug" value="{{ old('slug', $category->slug) }}" placeholder="bahan-pokok" required autocomplete="off"></label>
                </div>
            </section>

            <section class="wizard-step" data-step="2">
                <div class="wizard-step__head"><h4>Description & Status</h4><p>Sesuaikan deskripsi dan status aktif kategori.</p></div>
                <div class="wizard-form-grid">
                    <label class="form-field form-field--full"><span>Description</span><textarea name="description" rows="4" placeholder="Description...">{{ old('description', $category->description) }}</textarea></label>
                    <label class="form-field"><span>Status</span><select name="is_active" required><option value="1" {{ old('is_active', $category->is_active ? 1 : 0) == 1 ? 'selected' : '' }}>Active</option><option value="0" {{ old('is_active', $category->is_active ? 1 : 0) == 0 ? 'selected' : '' }}>Inactive</option></select></label>
                </div>
            </section>

            <section class="wizard-step" data-step="3">
                <div class="wizard-step__head"><h4>Review & Submit</h4><p>Pastikan hasil perubahan sudah benar sebelum disimpan.</p></div>
                <div class="review-grid">
                    <div class="review-item"><span>Nama Kategori</span><strong data-review-field="name">{{ old('name', $category->name) }}</strong></div>
                    <div class="review-item"><span>SKU</span><strong data-review-field="sku">{{ old('sku', $category->sku) }}</strong></div>
                    <div class="review-item"><span>Slug</span><strong data-review-field="slug">{{ old('slug', $category->slug) }}</strong></div>
                    <div class="review-item review-item--full"><span>Description</span><p data-review-field="description">{{ old('description', $category->description) ?: '-' }}</p></div>
                    <div class="review-item"><span>Status</span><strong data-review-field="status">{{ old('is_active', $category->is_active ? 1 : 0) == 1 ? 'Active' : 'Inactive' }}</strong></div>
                </div>
            </section>
        </div>

        <div class="wizard-actions">
            <button class="btn btn--secondary" type="button" data-step-action="prev">Back</button>
            <div class="wizard-actions__right">
                <button class="btn btn--ghost" type="button" data-step-action="skip">Skip</button>
                <button class="btn btn--primary" type="button" data-step-action="next">Next</button>
                <button class="btn btn--primary" type="submit" data-step-submit hidden><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Update Kategori</button>
            </div>
        </div>
    </form>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection
