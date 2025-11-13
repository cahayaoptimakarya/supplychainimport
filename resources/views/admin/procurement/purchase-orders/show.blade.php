@extends('layouts.admin')

@section('title', 'Purchase Order Detail')

@section('page_title', 'Purchase Order Detail')

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Procurement</span>
    <span class="mx-2">-</span>
    <span class="text-dark">PO {{ $po->code ?? ('#'.$po->id) }}</span>
@endsection

@push('styles')
<style>
    .cell-number {
        text-align: right !important;
        font-variant-numeric: tabular-nums;
    }
</style>
@endpush

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="card">
            <div class="card-body py-6">
                <div class="row mb-6">
                    <div class="col-md-3">
                        <div class="fw-bold text-gray-600">Code</div>
                        <div class="fs-6">{{ $po->code }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold text-gray-600">Ref No</div>
                        <div class="fs-6">{{ $po->ref_no ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold text-gray-600">Order Date</div>
                        <div class="fs-6">{{ optional($po->order_date)->format('Y-m-d') }}</div>
                    </div>
                </div>

                <div class="row mb-6">
                    <div class="col-md-3">
                        <div class="fw-bold text-gray-600">Status</div>
                        @php $map = ['open'=>'warning','partial'=>'info','fulfilled'=>'success']; $cls = $map[$totals['status']] ?? 'secondary'; @endphp
                        <div class="fs-6"><span class="badge badge-light-{{ $cls }}">{{ strtoupper($totals['status']) }}</span></div>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold text-gray-600">Qty Ordered</div>
                        <div class="fs-6">{{ number_format($totals['qty_ordered'], 0) }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold text-gray-600">Qty Fulfilled</div>
                        <div class="fs-6">{{ number_format($totals['qty_fulfilled'], 0) }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold text-gray-600">Qty Remaining</div>
                        <div class="fs-6">{{ number_format($totals['qty_remaining'], 0) }}</div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Lines</h5>
                    <div>
                        <a href="{{ route('admin.procurement.purchase-orders.edit', $po->id) }}" class="btn btn-light-primary btn-sm me-2">Edit</a>
                        <a href="{{ route('admin.procurement.purchase-orders.index') }}" class="btn btn-light btn-sm">Kembali</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-gray-100 table-hover align-middle gy-3">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th>#</th>
                                <th>Item</th>
                                <th class="text-end">Qty Ordered</th>
                                <th class="text-end">Cnt Ordered</th>
                                <th>PCS / CNT</th>
                                <th class="text-end">Qty Fulfilled</th>
                                <th class="text-end">Qty Remaining</th>
                                <th class="text-end">Fulfillment</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $statusBadgeMap = ['open' => 'warning', 'partial' => 'info', 'fulfilled' => 'success']; @endphp
                            @foreach($po->lines as $i => $l)
                                <tr>
                                    <td>{{ $i+1 }}</td>
                                    <td>{{ optional($l->item)->sku }} - {{ optional($l->item)->name }}</td>
                                    <td class="text-end">{{ number_format($l->qty_ordered, 0) }}</td>
                                    <td class="text-end">{{ $l->cnt_ordered === null ? '-' : number_format($l->cnt_ordered, 4) }}</td>
                                    <td>{{ $l->pcs_cnt ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($l->fulfilled_qty, 0) }}</td>
                                    <td class="text-end">{{ number_format($l->remaining_qty, 0) }}</td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end align-items-center gap-2">
                                            <span class="fw-semibold">{{ number_format($l->fulfillment_percent, 1) }}%</span>
                                            <div class="progress w-100" style="max-width: 120px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $l->fulfillment_percent }}%;" aria-valuenow="{{ $l->fulfillment_percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </td>
                                    @php $lineStatus = $l->status; @endphp
                                    <td>
                                        <span class="badge badge-light-{{ $statusBadgeMap[$lineStatus] ?? 'secondary' }}">{{ strtoupper($lineStatus) }}</span>
                                    </td>
                                    <td>{{ $l->notes ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
