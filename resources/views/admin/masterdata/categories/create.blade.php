@extends('layouts.admin')

@section('title', 'Tambah Kategori')

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="d-flex flex-wrap flex-stack mb-5">
            <div class="page-title d-flex flex-column">
                <h1 class="d-flex text-dark fw-bold fs-3 mb-0">Tambah Kategori</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-1">
                    <li class="breadcrumb-item text-muted"><a href="{{ route('admin.masterdata.items.index') }}" class="text-muted text-hover-primary">Masterdata</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted"><a href="{{ route('admin.masterdata.categories.index') }}" class="text-muted text-hover-primary">Kategori</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-dark">Tambah</li>
                </ul>
            </div>
        </div>
        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <h1 class="fw-bold fs-3">Tambah Kategori</h1>
                </div>
            </div>
            <div class="card-body py-6">
                <form method="POST" action="{{ route('admin.masterdata.categories.store') }}" class="form">
                    @csrf

                    <div class="mb-10">
                        <label class="form-label required">Nama</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="Nama kategori" required />
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('admin.masterdata.categories.index') }}" class="btn btn-light me-3">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
