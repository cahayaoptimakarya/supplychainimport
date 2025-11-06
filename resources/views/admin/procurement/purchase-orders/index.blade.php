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
                <div class="row g-3 mb-4 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select id="filter_status" class="form-select form-select-solid">
                            <option value="">All</option>
                            <option value="open">Open</option>
                            <option value="partial">Partial</option>
                            <option value="fulfilled">Fulfilled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tgl PO From</label>
                        <input type="text" id="filter_from" class="form-control js-fp-date form-control-solid" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tgl PO To</label>
                        <input type="text" id="filter_to" class="form-control js-fp-date form-control-solid" />
                    </div>
                    <div class="col-md-3">
                        <button id="btn_reset_filters" class="btn btn-light">Reset Filters</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="po_table">
                        <thead>
                        <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                            <th>ID</th>
                            <th>Code</th>
                            <th>Ref</th>
                            <th>Supplier</th>
                            <th>Tgl PO</th>
                            <th>Lines</th>
                            <th>Qty Ordered</th>
                            <th>Koli Ordered</th>
                            <th>Qty Fulfilled</th>
                            <th>Qty Open</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                        <tr class="fw-bold">
                            <th colspan="6" class="text-end">Totals:</th>
                            <th id="ft_qty_ordered">0</th>
                            <th id="ft_koli_ordered">0</th>
                            <th id="ft_qty_fulfilled">0</th>
                            <th id="ft_qty_open">0</th>
                            <th colspan="2"></th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $canUpdate = Perm::can(auth()->user(), 'admin.procurement.purchase-orders.index', 'update');
    $canDelete = Perm::can(auth()->user(), 'admin.procurement.purchase-orders.index', 'delete');
    $canView = Perm::can(auth()->user(), 'admin.procurement.purchase-orders.index', 'view');
@endphp
@push('scripts')
<link href="{{ asset('metronic/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
<script src="{{ asset('metronic/plugins/custom/datatables/datatables.bundle.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dataUrl = '{{ route('admin.procurement.purchase-orders.data') }}';
    const editTpl = '{{ route('admin.procurement.purchase-orders.edit', ':id') }}';
    const viewTpl = '{{ route('admin.procurement.purchase-orders.show', ':id') }}';
    const delTpl  = '{{ route('admin.procurement.purchase-orders.destroy', ':id') }}';
    const canUpdate = {{ $canUpdate ? 'true' : 'false' }};
    const canDelete = {{ $canDelete ? 'true' : 'false' }};
    const canView = {{ $canView ? 'true' : 'false' }};
    const nf = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 });
    const table = $('#po_table').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 300,
        ajax: {
            url: dataUrl,
            type: 'GET',
            data: function(d){
                d.status = document.getElementById('filter_status').value;
                d.date_from = document.getElementById('filter_from').value;
                d.date_to = document.getElementById('filter_to').value;
            },
            dataSrc: 'data',
            error: function(xhr){
                console.error('PO AJAX error:', xhr.responseText);
                alert('Gagal memuat data Purchase Orders');
            }
        },
        columns: [
            { data: 'id' },
            { data: 'code', render: function(val, t, row){
                const href = editTpl.replace(':id', row.id);
                const text = val || '-';
                return `<a href="${href}" class="text-primary text-decoration-underline">${text}</a>`;
            }},
            { data: 'ref_no', defaultContent: '-' },
            { data: 'supplier', defaultContent: '-' },
            { data: 'order_date' },
            { data: 'lines_count', defaultContent: 0 },
            { data: 'qty_ordered', render: v => nf.format(v) },
            { data: 'koli_ordered', defaultContent: 0, render: v => nf.format(v) },
            { data: 'qty_fulfilled', render: v => nf.format(v) },
            { data: 'qty_open', render: v => nf.format(v) },
            { data: 'status', render: function(val){
                const map = { open: 'warning', partial: 'info', fulfilled: 'success' };
                const cls = map[val] || 'secondary';
                const label = (val||'-').toUpperCase();
                return `<span class="badge badge-light-${cls}">${label}</span>`;
            }},
            {
                data: 'id', className: 'text-end', orderable: false, searchable: false,
                render: function(id, type, row){
                    let html='';
                    if (canView) html += `<a href=\"${viewTpl.replace(':id', id)}\" class=\"btn btn-light-info btn-sm me-2\">View</a>`;
                    if (canUpdate) html += `<a href=\"${editTpl.replace(':id', id)}\" class=\"btn btn-light-primary btn-sm me-2\">Edit</a>`;
                    if (canDelete) html += `<form method=\"POST\" action=\"${delTpl.replace(':id', id)}\" style=\"display:inline\">@csrf @method('DELETE')<button class=\"btn btn-light-danger btn-sm\" onclick=\"return confirm('Hapus PO ini?')\">Hapus</button></form>`;
                    return html || '-';
                }
            }
        ],
        footerCallback: function(row, data){
            let qtyOrder = 0, koliOrder = 0, qtyFulfill = 0, qtyOpen = 0;
            data.forEach(r => {
                qtyOrder += parseFloat(r.qty_ordered || 0);
                koliOrder += parseFloat(r.koli_ordered || 0);
                qtyFulfill += parseFloat(r.qty_fulfilled || 0);
                qtyOpen += parseFloat(r.qty_open || 0);
            });
            document.getElementById('ft_qty_ordered').textContent = nf.format(qtyOrder);
            document.getElementById('ft_koli_ordered').textContent = nf.format(koliOrder);
            document.getElementById('ft_qty_fulfilled').textContent = nf.format(qtyFulfill);
            document.getElementById('ft_qty_open').textContent = nf.format(qtyOpen);
        }
    });

    // Filters
    const statusSel = document.getElementById('filter_status');
    const fromInput = document.getElementById('filter_from');
    const toInput = document.getElementById('filter_to');
    const resetBtn = document.getElementById('btn_reset_filters');

    statusSel.addEventListener('change', function(){
        table.ajax.reload();
    });
    function withinDate(d, from, to){
        if (!d) return true;
        if (from && d < from) return false;
        if (to && d > to) return false;
        return true;
    }
    function applyDateFilter(){ table.ajax.reload(); }
    fromInput.addEventListener('change', applyDateFilter);
    toInput.addEventListener('change', applyDateFilter);
    resetBtn.addEventListener('click', function(){
        statusSel.value=''; fromInput.value=''; toInput.value='';
        const topSearch = document.getElementById('global_search');
        if (topSearch) topSearch.value='';
        table.search('');
        table.ajax.reload();
    });

    // Hook topbar global search to this table (debounced)
    (function(){
        const topSearch = document.getElementById('global_search');
        if (!topSearch) return;
        let tmr;
        const run = (q)=> table.search(q).draw();
        topSearch.addEventListener('input', function(){
            clearTimeout(tmr);
            const q = this.value || '';
            tmr = setTimeout(()=> run(q), 200);
        });
    })();
});
</script>
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    flatpickr('.js-fp-date', { dateFormat: 'Y-m-d' });
});
</script>
@endpush
@endpush
@endsection
