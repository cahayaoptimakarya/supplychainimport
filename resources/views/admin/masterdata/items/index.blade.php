@extends('layouts.admin')

@section('title', 'Masterdata - Items')

@section('page_title', 'Items')

@section('page_actions')
<a href="{{ route('admin.masterdata.items.create') }}" class="btn btn-primary">Create</a>
@endsection

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Masterdata</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Items</span>
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        @if(session('success'))
            <div class="alert alert-success my-5">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-body py-6">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="items_table">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th>ID</th>
                                <th>Nama</th>
                                <th>SKU</th>
                                <th>Kategori</th>
                                <th>UOM</th>
                                <th>CNT</th>
                                <th>Deskripsi</th>
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
    const dataUrl   = '{{ route('admin.masterdata.items.data') }}';
    const indexUrl  = '{{ route('admin.masterdata.items.index') }}';
    const editTpl   = '{{ route('admin.masterdata.items.edit', ':id') }}';
    const delTpl    = '{{ route('admin.masterdata.items.destroy', ':id') }}';

    document.addEventListener('DOMContentLoaded', function() {
        const table = $('#items_table').DataTable({
            processing: true,
            serverSide: false,
            dom: 'lrtip',
            ajax: {
                url: dataUrl,
                dataSrc: 'data',
                error: function(xhr){
                    console.error('Items AJAX error:', xhr.responseText);
                    alert('Gagal memuat data item');
                }
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'name', name: 'name' },
                { data: 'sku', name: 'sku' },
                { data: 'category', name: 'category', defaultContent: '-' },
                { data: 'uom', name: 'uom', defaultContent: '-' },
                { data: 'cnt', name: 'cnt', defaultContent: '-' },
                { data: 'description', name: 'description', defaultContent: '-' },
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

        $('#items_table').on('click', '.btn-delete', function() {
            const id = this.getAttribute('data-id');
            const url = this.getAttribute('data-url');
            if (!confirm('Yakin ingin menghapus item ini?')) return;
            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: new URLSearchParams({ _method: 'DELETE' })
            }).then(res => {
                if (res.ok) {
                    $('#items_table').DataTable().ajax.reload(null, false);
                } else {
                    alert('Gagal menghapus item');
                }
            }).catch(() => alert('Gagal menghapus item'));
        });

        const globalInput = document.getElementById('global_search');
        if (globalInput) {
            globalInput.addEventListener('input', function() {
                table.search(this.value).draw();
            });
        }
    });
</script>
@endpush
