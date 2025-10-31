@extends('layouts.admin')

@section('title', 'Warehouse Receipts')

@section('page_title', 'Warehouse Receipts')

@section('page_actions')
@php use App\Support\Permission as Perm; @endphp
@php $canCreate = Perm::can(auth()->user(), 'admin.procurement.receipts.index', 'create'); @endphp
@if($canCreate)
<a href="{{ route('admin.procurement.receipts.create') }}" class="btn btn-primary">Create Receipt</a>
@endif
@endsection

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Procurement</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Receipts</span>
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
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="rcp_table">
                        <thead>
                        <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                            <th>ID</th>
                            <th>Shipment</th>
                            <th>Warehouse</th>
                            <th>Received At</th>
                            <th>Status</th>
                            <th>Qty Total</th>
                            <th>Koli Received</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dataUrl = '{{ route('admin.procurement.receipts.data') }}';
    $('#rcp_table').DataTable({
        processing: true,
        serverSide: false,
        ajax: { url: dataUrl, dataSrc: 'data' },
        columns: [
            { data: 'id' },
            { data: 'shipment' },
            { data: 'warehouse' },
            { data: 'received_at' },
            { data: 'status' },
            { data: 'qty_total' },
            { data: 'koli_total', defaultContent: 0 },
        ]
    });
});
</script>
@endsection
