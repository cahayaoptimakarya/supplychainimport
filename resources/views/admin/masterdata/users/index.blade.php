@extends('layouts.admin')

@section('title', 'Masterdata - User')

@section('page_title', 'Users')

@section('page_actions')
@php use App\Support\Permission as Perm; @endphp
@if(Perm::can(auth()->user(), 'admin.masterdata.users.index', 'create'))
<a href="{{ route('admin.masterdata.users.create') }}" class="btn btn-primary">Create</a>
@endif
@endsection

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Masterdata</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Users</span>
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="card">
            <div class="card-body py-6">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="users_table">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Roles</th>
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
    const dataUrl   = '{{ route('admin.masterdata.users.data') }}';
    const editTpl   = '{{ route('admin.masterdata.users.edit', ':id') }}';
    const delTpl    = '{{ route('admin.masterdata.users.destroy', ':id') }}';
    document.addEventListener('DOMContentLoaded', function() {
        $('#users_table').DataTable({
            processing: true, serverSide: false, dom: 'lrtip',
            ajax: { url: dataUrl, dataSrc: 'data' },
            columns: [
                { data: 'id' },
                { data: 'name' },
                { data: 'email' },
                { data: 'roles' },
                { data: 'id', orderable:false, searchable:false, className:'text-end', render: (data)=>{
                    const editUrl = editTpl.replace(':id', data);
                    const delUrl  = delTpl.replace(':id', data);
                    const canUpdate = {{ \App\Support\Permission::can(auth()->user(), 'admin.masterdata.users.index', 'update') ? 'true' : 'false' }};
                    const canDelete = {{ \App\Support\Permission::can(auth()->user(), 'admin.masterdata.users.index', 'delete') ? 'true' : 'false' }};
                    let html = '';
                    if (canUpdate) html += `<a href=\"${editUrl}\" class=\"btn btn-light-primary btn-sm me-2\">Edit</a>`;
                    if (canDelete) html += `<button type=\"button\" data-url=\"${delUrl}\" data-id=\"${data}\" class=\"btn btn-light-danger btn-sm btn-delete\">Hapus</button>`;
                    return html || '-';
                }}
            ]
        });

        $('#users_table').on('click', '.btn-delete', function() {
            const url = this.getAttribute('data-url');
            if (!confirm('Yakin ingin menghapus User ini?')) return;
            fetch(url, { method:'POST', headers:{ 'X-CSRF-TOKEN': csrfToken }, body: new URLSearchParams({ _method:'DELETE' }) })
                .then(res => { if (res.ok) $('#users_table').DataTable().ajax.reload(null, false); else alert('Gagal menghapus user'); })
                .catch(()=> alert('Gagal menghapus user'));
        });
    });
</script>
@endpush
