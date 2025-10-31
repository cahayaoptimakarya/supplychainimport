@extends('layouts.admin')

@section('title', 'Masterdata - Warehouses')

@section('page_title', 'Warehouses')

@section('page_actions')
@php use App\Support\Permission as Perm; @endphp
@if(Perm::can(auth()->user(), 'admin.masterdata.warehouses.index', 'create'))
<a href="{{ route('admin.masterdata.warehouses.create') }}" class="btn btn-primary">Create</a>
@endif
@endsection

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Masterdata</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Warehouses</span>
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
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="warehouses_table">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Alamat</th>
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
    const dataUrl   = '{{ route('admin.masterdata.warehouses.data') }}';
    const editTpl   = '{{ route('admin.masterdata.warehouses.edit', ':id') }}';
    const delTpl    = '{{ route('admin.masterdata.warehouses.destroy', ':id') }}';
    const canUpdate = {{ \App\Support\Permission::can(auth()->user(), 'admin.masterdata.warehouses.index', 'update') ? 'true' : 'false' }};
    const canDelete = {{ \App\Support\Permission::can(auth()->user(), 'admin.masterdata.warehouses.index', 'delete') ? 'true' : 'false' }};

    document.addEventListener('DOMContentLoaded', function() {
        const table = $('#warehouses_table').DataTable({
            processing: true,
            serverSide: false,
            dom: 'lrtip',
            ajax: {
                url: dataUrl,
                dataSrc: 'data',
                error: function(xhr){
                    console.error('Warehouses AJAX error:', xhr.responseText);
                    alert('Gagal memuat data warehouse');
                }
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'name', name: 'name' },
                { data: 'address', name: 'address', defaultContent: '-' },
                { data: 'description', name: 'description', defaultContent: '-' },
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    className: 'text-end',
                    render: function (data, type, row) {
                        const editUrl = editTpl.replace(':id', data);
                        const delUrl  = delTpl.replace(':id', data);
                        let html = '';
                        if (canUpdate) html += `<a href=\"${editUrl}\" class=\"btn btn-light-primary btn-sm me-2\">Edit</a>`;
                        if (canDelete) html += `<button type=\"button\" data-id=\"${data}\" data-url=\"${delUrl}\" class=\"btn btn-light-danger btn-sm btn-delete\">Hapus</button>`;
                        return html || '-';
                    }
                }
            ]
        });

        $('#warehouses_table').on('click', '.btn-delete', function() {
            const url = this.getAttribute('data-url');
            if (!confirm('Yakin ingin menghapus warehouse ini?')) return;
            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: new URLSearchParams({ _method: 'DELETE' })
            }).then(res => {
                if (res.ok) {
                    $('#warehouses_table').DataTable().ajax.reload(null, false);
                } else {
                    alert('Gagal menghapus warehouse');
                }
            }).catch(() => alert('Gagal menghapus warehouse'));
        });

        const globalInput = document.getElementById('global_search');
        if (globalInput) {
            const dt = $('#warehouses_table').DataTable();
            globalInput.addEventListener('input', function() {
                dt.search(this.value).draw();
            });
        }
    });
</script>
@endpush

