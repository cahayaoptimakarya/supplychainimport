@extends('layouts.admin')

@section('title', 'Masterdata - Kategori')

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="d-flex flex-wrap flex-stack mb-5">
            <div class="page-title d-flex flex-column">
                <h1 class="d-flex text-dark fw-bold fs-3 mb-0">Kategori Item</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-1">
                    <li class="breadcrumb-item text-muted"><a href="{{ route('admin.masterdata.items.index') }}" class="text-muted text-hover-primary">Masterdata</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-dark">Kategori</li>
                </ul>
            </div>
        </div>
        @if(session('success'))
            <div class="alert alert-success my-5">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <h1 class="fw-bold fs-3">Daftar Kategori</h1>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('admin.masterdata.categories.create') }}" class="btn btn-primary">
                        <i class="ki-duotone ki-plus fs-2"></i> Tambah Kategori
                    </a>
                </div>
            </div>
            <div class="card-body py-6">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="categories_table">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Slug</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link href="{{ asset('metronic/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
<script src="{{ asset('metronic/plugins/custom/datatables/datatables.bundle.js') }}"></script>
<script>
    const csrfToken = '{{ csrf_token() }}';
    const dataUrl   = '{{ route('admin.masterdata.categories.data') }}';
    const editTpl   = '{{ route('admin.masterdata.categories.edit', ':id') }}';
    const delTpl    = '{{ route('admin.masterdata.categories.destroy', ':id') }}';

    document.addEventListener('DOMContentLoaded', function() {
        $('#categories_table').DataTable({
            processing: true,
            serverSide: false,
            dom: 'lrtip',
            ajax: {
                url: dataUrl,
                dataSrc: 'data',
                error: function(xhr){
                    console.error('Categories AJAX error:', xhr.responseText);
                    alert('Gagal memuat data kategori');
                }
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'name', name: 'name' },
                { data: 'slug', name: 'slug' },
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    className: 'text-end',
                    render: function (data, type, row) {
                        const editUrl = editTpl.replace(':id', data);
                        const delUrl  = delTpl.replace(':id', data);
                        return `
                            <a href="${editUrl}" class="btn btn-light-primary btn-sm me-2">Edit</a>
                            <button type="button" data-id="${data}" data-url="${delUrl}" class="btn btn-light-danger btn-sm btn-delete">Hapus</button>
                        `;
                    }
                }
            ]
        });

        $('#categories_table').on('click', '.btn-delete', function() {
            const url = this.getAttribute('data-url');
            if (!confirm('Yakin ingin menghapus kategori ini?')) return;
            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: new URLSearchParams({ _method: 'DELETE' })
            }).then(res => {
                if (res.ok) {
                    $('#categories_table').DataTable().ajax.reload(null, false);
                } else {
                    alert('Gagal menghapus kategori');
                }
            }).catch(() => alert('Gagal menghapus kategori'));
        });

        const globalInput = document.getElementById('global_search');
        if (globalInput) {
            const catTable = $('#categories_table').DataTable();
            globalInput.addEventListener('input', function() {
                catTable.search(this.value).draw();
            });
        }
    });
    </script>
@endpush
