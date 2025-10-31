@extends('layouts.admin')

@section('title', 'Procurement - Shipments')

@section('page_title', 'Shipments')

@section('page_actions')
@php use App\Support\Permission as Perm; @endphp
@php $canCreate = Perm::can(auth()->user(), 'admin.procurement.shipments.index', 'create'); @endphp
@if($canCreate)
<a href="{{ route('admin.procurement.shipments.create') }}" class="btn btn-primary">Create Shipment</a>
@endif
@endsection

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Procurement</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Shipments</span>
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
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="ship_table">
                        <thead>
                        <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                            <th>ID</th>
                            <th>Supplier</th>
                            <th>Container</th>
                            <th>PL</th>
                            <th>ETD</th>
                            <th>ETA</th>
                            <th>Status</th>
                            <th>Items</th>
                            <th>Koli Expected</th>
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

@php
    $canUpdate = Perm::can(auth()->user(), 'admin.procurement.shipments.index', 'update');
    $canDelete = Perm::can(auth()->user(), 'admin.procurement.shipments.index', 'delete');
@endphp
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dataUrl = '{{ route('admin.procurement.shipments.data') }}';
    const editTpl = '{{ route('admin.procurement.shipments.edit', ':id') }}';
    const delTpl  = '{{ route('admin.procurement.shipments.destroy', ':id') }}';
    const canUpdate = {{ $canUpdate ? 'true' : 'false' }};
    const canDelete = {{ $canDelete ? 'true' : 'false' }};
    $('#ship_table').DataTable({
        processing: true,
        serverSide: false,
        ajax: { url: dataUrl, dataSrc: 'data' },
        columns: [
            { data: 'id' },
            { data: 'supplier', defaultContent: '-' },
            { data: 'container_no', defaultContent: '-' },
            { data: 'pl_no', defaultContent: '-' },
            { data: 'etd', defaultContent: '-' },
            { data: 'eta', defaultContent: '-' },
            { data: 'status' },
            { data: 'items_count' },
            { data: 'koli_expected_total', defaultContent: 0 },
            {
                data: 'id', className: 'text-end', orderable: false, searchable: false,
                render: function(id){
                    let html='';
                    if (canUpdate) html += `<a href=\"${editTpl.replace(':id', id)}\" class=\"btn btn-light-primary btn-sm me-2\">Edit</a>`;
                    if (canDelete) html += `<form method=\"POST\" action=\"${delTpl.replace(':id', id)}\" style=\"display:inline\">@csrf @method('DELETE')<button class=\"btn btn-light-danger btn-sm\" onclick=\"return confirm('Hapus shipment ini?')\">Hapus</button></form>`;
                    return html || '-';
                }
            }
        ]
    });
});
</script>
@endsection
