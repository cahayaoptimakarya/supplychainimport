@extends('layouts.admin')

@section('title', 'Edit UOM')

@section('page_title', 'Edit UOM')

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Masterdata</span>
    <span class="mx-2">-</span>
    <span class="text-muted">UOM</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Edit</span>
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="card">
            <div class="card-body py-6">
                <form method="POST" action="{{ route('admin.masterdata.uom.update', $uom->id) }}" class="form">
                    @csrf
                    @method('PUT')
                    <div class="mb-10">
                        <label class="form-label required">Nama UOM</label>
                        <input type="text" name="name" value="{{ old('name', $uom->name) }}" class="form-control @error('name') is-invalid @enderror" placeholder="cth: PCS, KG" required />
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-10">
                        <label class="form-label">Simbol</label>
                        <input type="text" name="symbol" value="{{ old('symbol', $uom->symbol) }}" maxlength="20" class="form-control @error('symbol') is-invalid @enderror" placeholder="cth: pcs, kg" />
                        @error('symbol')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-10">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control @error('keterangan') is-invalid @enderror" rows="3" placeholder="Keterangan tambahan">{{ old('keterangan', $uom->keterangan) }}</textarea>
                        @error('keterangan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('admin.masterdata.uom.index') }}" class="btn btn-light me-3">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
