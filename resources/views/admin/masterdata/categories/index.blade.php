@extends('layouts.admin')

@section('title', 'Masterdata - Kategori')

@section('page_title', 'Kategori Item')

@section('page_actions')
@php use App\Support\Permission as Perm; @endphp
@if(Perm::can(auth()->user(), 'admin.masterdata.categories.index', 'create'))
<a href="{{ route('admin.masterdata.categories.create') }}" class="btn btn-primary">Create</a>
@endif
@endsection

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Masterdata</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Kategori</span>
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="card">
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
    const canUpdate = {{ \App\Support\Permission::can(auth()->user(), 'admin.masterdata.categories.index', 'update') ? 'true' : 'false' }};
    const canDelete = {{ \App\Support\Permission::can(auth()->user(), 'admin.masterdata.categories.index', 'delete') ? 'true' : 'false' }};
    const renderActionsDropdown = (items) => {
        if (!items.length) return '-';
        return `
            <div class="text-end">
                <a href="#" class="btn btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                    Actions
                    <span class="svg-icon svg-icon-5 m-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M11.4343 12.7344L7.25 8.55005C6.83579 8.13583 6.16421 8.13584 5.75 8.55005C5.33579 8.96426 5.33579 9.63583 5.75 10.05L11.2929 15.5929C11.6834 15.9835 12.3166 15.9835 12.7071 15.5929L18.25 10.05C18.6642 9.63584 18.6642 8.96426 18.25 8.55005C17.8358 8.13584 17.1642 8.13584 16.75 8.55005L12.5657 12.7344C12.2533 13.0468 11.7467 13.0468 11.4343 12.7344Z" fill="black"></path>
                        </svg>
                    </span>
                </a>
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-bold fs-7 w-175px py-3" data-kt-menu="true">
                    ${items.join('')}
                </div>
            </div>
        `.trim();
    };

    document.addEventListener('DOMContentLoaded', function() {
        const refreshMenus = () => { if (window.KTMenu) KTMenu.createInstances(); };
        const dt = $('#categories_table').DataTable({
            processing: true,
            serverSide: false,
            dom: 'lrtip',
            order: [[0, 'desc']],
            ajax: {
                url: dataUrl,
                dataSrc: 'data',
                error: function(xhr){
                    console.error('Categories AJAX error:', xhr.responseText);
                    AppSwal.error('Gagal memuat data kategori');
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
                    render: function (data) {
                        const editUrl = editTpl.replace(':id', data);
                        const delUrl  = delTpl.replace(':id', data);
                        const menuItems = [];
                        if (canUpdate) menuItems.push(`<div class="menu-item px-3"><a href="${editUrl}" class="menu-link px-3">Edit</a></div>`);
                        if (canDelete) menuItems.push(`<div class="menu-item px-3"><a href="#" data-id="${data}" data-url="${delUrl}" class="menu-link px-3 text-danger btn-delete">Hapus</a></div>`);
                        return renderActionsDropdown(menuItems);
                    }
                }
            ]
        });
        refreshMenus();
        dt.on('draw', refreshMenus);

        $('#categories_table').on('click', '.btn-delete', async function(e) {
            e.preventDefault();
            const url = this.getAttribute('data-url');
            const confirmed = await AppSwal.confirm('Yakin ingin menghapus kategori ini?', {
                confirmButtonText: 'Hapus'
            });
            if (!confirmed) return;
            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: new URLSearchParams({ _method: 'DELETE' })
            }).then(res => {
                if (res.ok) {
                    dt.ajax.reload(null, false);
                } else {
                    AppSwal.error('Gagal menghapus kategori');
                }
            }).catch(() => AppSwal.error('Gagal menghapus kategori'));
        });

        const globalInput = document.getElementById('global_search');
        if (globalInput) {
            globalInput.addEventListener('input', function() {
                dt.search(this.value).draw();
            });
        }
    });
    </script>
@endpush
