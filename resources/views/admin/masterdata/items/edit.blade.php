@extends('layouts.admin')

@section('title', 'Edit Item')

@section('page_title', 'Edit Item')

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Masterdata</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Items</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Edit</span>
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <h1 class="fw-bold fs-3">Edit Item</h1>
                </div>
            </div>
            <div class="card-body py-6">
                <form method="POST" action="{{ route('admin.masterdata.items.update', $item->id) }}" class="form">
                    @csrf
                    @method('PUT')

                    <div class="mb-10">
                        <label class="form-label required">SKU</label>
                        <input type="text" name="sku" value="{{ old('sku', $item->sku) }}" class="form-control @error('sku') is-invalid @enderror" placeholder="SKU item" required />
                        @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-10">
                        <label class="form-label required">Nama</label>
                        <input type="text" name="name" value="{{ old('name', $item->name) }}" class="form-control @error('name') is-invalid @enderror" placeholder="Nama item" required />
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-10">
                        <label class="form-label required">CNT</label>
                        <input type="text" name="cnt" value="{{ old('cnt', $item->cnt) }}" class="form-control @error('cnt') is-invalid @enderror" placeholder="Informasi CNT" required />
                        @error('cnt')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-10">
                        <label class="form-label required">Kategori</label>
                        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required data-control="select2" data-placeholder="Pilih kategori">
                            <option value="">Pilih kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $item->category_id) == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-10">
                        <label class="form-label required">UOM</label>
                        <select name="uom_id" class="form-select @error('uom_id') is-invalid @enderror" required data-control="select2" data-placeholder="Pilih UOM">
                            <option value="">Pilih UOM</option>
                            @foreach($uoms as $uom)
                                <option value="{{ $uom->id }}" @selected(old('uom_id', $item->uom_id) == $uom->id)>{{ $uom->name }}</option>
                            @endforeach
                        </select>
                        @error('uom_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-10">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Deskripsi item (opsional)">{{ old('description', $item->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('admin.masterdata.items.index') }}" class="btn btn-light me-3">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
