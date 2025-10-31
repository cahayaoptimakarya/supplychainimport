@extends('layouts.admin')

@section('title', 'Masterdata - Roles')
@section('page_title', 'Roles')

@section('page_actions')
@php use App\Support\Permission as Perm; @endphp
@if(Perm::can(auth()->user(), 'admin.masterdata.roles.index', 'create'))
<a href="{{ route('admin.masterdata.roles.create') }}" class="btn btn-primary">Create</a>
@endif
@endsection

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Masterdata</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Roles</span>
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
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="roles_table">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Slug</th>
                                <th>Jumlah User</th>
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
    const dataUrl   = '{{ route('admin.masterdata.roles.data') }}';
    const editTpl   = '{{ route('admin.masterdata.roles.edit', ':id') }}';
    const delTpl    = '{{ route('admin.masterdata.roles.destroy', ':id') }}';
    const canUpdate = {{ \App\Support\Permission::can(auth()->user(), 'admin.masterdata.roles.index', 'update') ? 'true' : 'false' }};
    const canDelete = {{ \App\Support\Permission::can(auth()->user(), 'admin.masterdata.roles.index', 'delete') ? 'true' : 'false' }};

    document.addEventListener('DOMContentLoaded', function() {
        $('#roles_table').DataTable({
            processing: true, serverSide: false, dom: 'lrtip',
            ajax: { url: dataUrl, dataSrc: 'data' },
            columns: [
                { data: 'id' },
                { data: 'name' },
                { data: 'slug' },
                { data: 'users_count' },
                { data: 'id', orderable:false, searchable:false, className:'text-end', render: (data)=>{
                    const editUrl = editTpl.replace(':id', data);
                    const delUrl  = delTpl.replace(':id', data);
                    const permUrl = `{{ route('admin.masterdata.permissions.edit', ':id') }}`.replace(':id', data);
                    let html = '';
                    if (canUpdate) html += `<a href="${editUrl}" class="btn btn-light-primary btn-sm me-2">Edit</a>`;
                    html += `<a href="${permUrl}" class="btn btn-light-info btn-sm me-2">Permission</a>`;
                    if (canDelete) html += `<button type="button" data-url="${delUrl}" data-id="${data}" class="btn btn-light-danger btn-sm btn-delete">Hapus</button>`;
                    return html || '-';
                }}
            ]
        });

        $('#roles_table').on('click', '.btn-delete', function() {
            const url = this.getAttribute('data-url');
            if (!confirm('Yakin ingin menghapus Role ini?')) return;
            fetch(url, { method:'POST', headers:{ 'X-CSRF-TOKEN': csrfToken }, body: new URLSearchParams({ _method:'DELETE' }) })
                .then(res => { if (res.ok) $('#roles_table').DataTable().ajax.reload(null, false); else alert('Gagal menghapus role'); })
                .catch(()=> alert('Gagal menghapus role'));
        });
    });
</script>
@endpush
