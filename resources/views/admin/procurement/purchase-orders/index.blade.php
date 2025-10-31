@extends('layouts.admin')

@section('title', 'Procurement - Purchase Orders')

@section('page_title', 'Purchase Orders')

@section('page_actions')
@php use App\Support\Permission as Perm; @endphp
@php $canCreate = Perm::can(auth()->user(), 'admin.procurement.purchase-orders.index', 'create'); @endphp
@if($canCreate)
<a href="{{ route('admin.procurement.purchase-orders.create') }}" class="btn btn-primary">Create PO</a>
@endif
@endsection

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Procurement</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Purchase Orders</span>
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
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="po_table">
                        <thead>
                        <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                            <th>ID</th>
                            <th>Ref</th>
                            <th>Supplier</th>
                            <th>Tgl PO</th>
                            <th>Qty Ordered</th>
                            <th>Koli Ordered</th>
                            <th>Qty Fulfilled</th>
                            <th>Qty Open</th>
                            <th>Status</th>
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
    $canUpdate = Perm::can(auth()->user(), 'admin.procurement.purchase-orders.index', 'update');
    $canDelete = Perm::can(auth()->user(), 'admin.procurement.purchase-orders.index', 'delete');
@endphp
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dataUrl = '{{ route('admin.procurement.purchase-orders.data') }}';
    const editTpl = '{{ route('admin.procurement.purchase-orders.edit', ':id') }}';
    const delTpl  = '{{ route('admin.procurement.purchase-orders.destroy', ':id') }}';
    const canUpdate = {{ $canUpdate ? 'true' : 'false' }};
    const canDelete = {{ $canDelete ? 'true' : 'false' }};
    $('#po_table').DataTable({
        processing: true,
        serverSide: false,
        ajax: { url: dataUrl, dataSrc: 'data' },
        columns: [
            { data: 'id' },
            { data: 'ref_no', defaultContent: '-' },
            { data: 'supplier', defaultContent: '-' },
            { data: 'order_date' },
            { data: 'qty_ordered' },
            { data: 'koli_ordered', defaultContent: 0 },
            { data: 'qty_fulfilled' },
            { data: 'qty_open' },
            { data: 'status' },
            {
                data: 'id', className: 'text-end', orderable: false, searchable: false,
                render: function(id, type, row){
                    let html='';
                    if (canUpdate) html += `<a href=\"${editTpl.replace(':id', id)}\" class=\"btn btn-light-primary btn-sm me-2\">Edit</a>`;
                    if (canDelete) html += `<form method=\"POST\" action=\"${delTpl.replace(':id', id)}\" style=\"display:inline\">@csrf @method('DELETE')<button class=\"btn btn-light-danger btn-sm\" onclick=\"return confirm('Hapus PO ini?')\">Hapus</button></form>`;
                    return html || '-';
                }
            }
        ]
    });
});
</script>
@endsection
