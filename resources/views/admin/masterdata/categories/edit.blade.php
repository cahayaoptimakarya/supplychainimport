@extends('layouts.admin')

@section('title', 'Edit Kategori')

@section('page_title', 'Edit Kategori')

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Masterdata</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Kategori</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Edit</span>
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <h1 class="fw-bold fs-3">Edit Kategori</h1>
                </div>
            </div>
            <div class="card-body py-6">
                <form method="POST" action="{{ route('admin.masterdata.categories.update', $category->id) }}" class="form">
                    @csrf
                    @method('PUT')

                    <div class="mb-10">
                        <label class="form-label required">Nama</label>
                        <input type="text" name="name" value="{{ old('name', $category->name) }}" class="form-control @error('name') is-invalid @enderror" placeholder="Nama kategori" required />
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
