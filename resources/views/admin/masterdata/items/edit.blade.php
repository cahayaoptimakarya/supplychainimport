@extends('layouts.admin')

@section('title', 'Edit Item')

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="d-flex flex-wrap flex-stack mb-5">
            <div class="page-title d-flex flex-column">
                <h1 class="d-flex text-dark fw-bold fs-3 mb-0">Edit Item</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-1">
                    <li class="breadcrumb-item text-muted"><a href="{{ route('admin.masterdata.items.index') }}" class="text-muted text-hover-primary">Masterdata</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted"><a href="{{ route('admin.masterdata.items.index') }}" class="text-muted text-hover-primary">Items</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-dark">Edit</li>
                </ul>
            </div>
        </div>
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
