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
                <div class="row g-3 mb-4 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select id="filter_status" class="form-select">
                            <option value="">All</option>
                            <option value="draft">Draft</option>
                            <option value="posted">Posted</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Received From</label>
                        <input type="date" id="filter_from" class="form-control" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Received To</label>
                        <input type="date" id="filter_to" class="form-control" />
                    </div>
                    <div class="col-md-3">
                        <button id="btn_reset_filters" class="btn btn-light">Reset Filters</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="rcp_table">
                        <thead>
                        <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                            <th>ID</th>
                            <th>Code</th>
                            <th>Shipment</th>
                            <th>Warehouse</th>
                            <th>Received At</th>
                            <th>Status</th>
                            <th>Qty Total</th>
                            <th>Koli Received</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                        <tr class="fw-bold">
                            <th colspan="6" class="text-end">Totals:</th>
                            <th id="ft_qty_total">0</th>
                            <th id="ft_koli_total">0</th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<link href="{{ asset('metronic/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
<script src="{{ asset('metronic/plugins/custom/datatables/datatables.bundle.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dataUrl = '{{ route('admin.procurement.receipts.data') }}';
    const nf = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 });
    const editTpl = '{{ route('admin.procurement.receipts.edit', ':id') }}';
    const delTpl  = '{{ route('admin.procurement.receipts.destroy', ':id') }}';
    const table = $('#rcp_table').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: dataUrl,
            dataSrc: 'data',
            error: function(xhr){
                console.error('Receipts AJAX error:', xhr.responseText);
                alert('Gagal memuat data Receipts');
            }
        },
        columns: [
            { data: 'id' },
            { data: 'code' },
            { data: 'shipment' },
            { data: 'warehouse' },
            { data: 'received_at' },
            { data: 'status', render: function(val){
                const map = { draft: 'secondary', posted: 'success' };
                const cls = map[val] || 'secondary';
                return `<span class="badge badge-light-${cls}">${(val||'-').toUpperCase()}</span>`;
            } },
            { data: 'qty_total', render: v => nf.format(v) },
            { data: 'koli_total', defaultContent: 0, render: v => nf.format(v) },
            {
                data: 'id', className: 'text-end', orderable: false, searchable: false,
                render: function(id){
                    const editUrl = editTpl.replace(':id', id);
                    const delUrl = delTpl.replace(':id', id);
                    return `
                        <a href="${editUrl}" class="btn btn-light-primary btn-sm me-2">Edit</a>
                        <form method="POST" action="${delUrl}" style="display:inline">@csrf @method('DELETE')
                            <button class="btn btn-light-danger btn-sm" onclick="return confirm('Hapus receipt ini?')">Hapus</button>
                        </form>`;
                }
            }
        ],
        footerCallback: function(row, data){
            let qty=0, koli=0; data.forEach(r=>{ qty += parseFloat(r.qty_total||0); koli += parseFloat(r.koli_total||0); });
            document.getElementById('ft_qty_total').textContent = nf.format(qty);
            document.getElementById('ft_koli_total').textContent = nf.format(koli);
        }
    });
    // Filters
    const statusSel = document.getElementById('filter_status');
    const fromInput = document.getElementById('filter_from');
    const toInput = document.getElementById('filter_to');
    const resetBtn = document.getElementById('btn_reset_filters');

    statusSel.addEventListener('change', function(){
        table.column(5).search(this.value).draw(); // status column index after adding Code column
    });
    function withinDate(d, from, to){
        if (!d) return true;
        if (from && d < from) return false;
        if (to && d > to) return false;
        return true;
    }
    function applyDateFilter(){
        const from = fromInput.value; const to = toInput.value;
        table.rows().every(function(){
            const data = this.data(); const d = (data.received_at||'').substring(0,10);
            const show = withinDate(d, from, to);
            $(this.node()).toggle(show);
        });
    }
    fromInput.addEventListener('change', applyDateFilter);
    toInput.addEventListener('change', applyDateFilter);
    resetBtn.addEventListener('click', function(){
        statusSel.value=''; fromInput.value=''; toInput.value='';
        table.search('').columns().search('');
        table.ajax.reload();
    });
});
</script>
@endpush
@endsection
