@extends('layouts.admin')

@section('title', 'Edit Warehouse')

@section('page_title', 'Edit Warehouse')

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Masterdata</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Warehouses</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Edit</span>
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <h1 class="fw-bold fs-3">Edit Warehouse</h1>
                </div>
            </div>
            <div class="card-body py-6">
                <form method="POST" action="{{ route('admin.masterdata.warehouses.update', $warehouse->id) }}" class="form">
                    @csrf
                    @method('PUT')

                    <div class="mb-10">
                        <label class="form-label required">Nama</label>
                        <input type="text" name="name" value="{{ old('name', $warehouse->name) }}" class="form-control @error('name') is-invalid @enderror" placeholder="Nama warehouse" required />
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-10">
                        <label class="form-label">Alamat</label>
                        <input type="text" name="address" value="{{ old('address', $warehouse->address) }}" class="form-control @error('address') is-invalid @enderror" placeholder="Alamat (opsional)" />
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-10">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Deskripsi (opsional)">{{ old('description', $warehouse->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('admin.masterdata.warehouses.index') }}" class="btn btn-light me-3">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

