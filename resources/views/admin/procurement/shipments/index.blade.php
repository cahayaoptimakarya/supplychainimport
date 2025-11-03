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
                <div class="row g-3 mb-4 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select id="filter_status" class="form-select">
                            <option value="">All</option>
                            <option value="planned">Planned</option>
                            <option value="ready_at_port">Ready at Port</option>
                            <option value="on_board">On Board</option>
                            <option value="arrived">Arrived</option>
                            <option value="under_bc">Under BC</option>
                            <option value="released">Released</option>
                            <option value="delivered_to_main_wh">Delivered to Main WH</option>
                            <option value="received">Received</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ETD From</label>
                        <input type="date" id="filter_from" class="form-control" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ETD To</label>
                        <input type="date" id="filter_to" class="form-control" />
                    </div>
                    <div class="col-md-3">
                        <button id="btn_reset_filters" class="btn btn-light">Reset Filters</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="ship_table">
                        <thead>
                        <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                            <th>ID</th>
                            <th>Code</th>
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
                        <tfoot>
                        <tr class="fw-bold">
                            <th colspan="8" class="text-end">Totals:</th>
                            <th id="ft_items_count">0</th>
                            <th id="ft_koli_expected">0</th>
                            <th></th>
                        </tr>
                        </tfoot>
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
@push('scripts')
<link href="{{ asset('metronic/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
<script src="{{ asset('metronic/plugins/custom/datatables/datatables.bundle.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dataUrl = '{{ route('admin.procurement.shipments.data') }}';
    const editTpl = '{{ route('admin.procurement.shipments.edit', ':id') }}';
    const delTpl  = '{{ route('admin.procurement.shipments.destroy', ':id') }}';
    const canUpdate = {{ $canUpdate ? 'true' : 'false' }};
    const canDelete = {{ $canDelete ? 'true' : 'false' }};
    const nf = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 });
    const table = $('#ship_table').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: dataUrl,
            dataSrc: 'data',
            error: function(xhr){
                console.error('Shipments AJAX error:', xhr.responseText);
                alert('Gagal memuat data Shipments');
            }
        },
        columns: [
            { data: 'id' },
            { data: 'code', render: function(val, t, row){
                const href = editTpl.replace(':id', row.id);
                const text = val || '-';
                return `<a href=\"${href}\" class=\"text-primary text-decoration-underline\">${text}</a>`;
            }},
            { data: 'supplier', defaultContent: '-' },
            { data: 'container_no', defaultContent: '-' },
            { data: 'pl_no', defaultContent: '-' },
            { data: 'etd', defaultContent: '-' },
            { data: 'eta', defaultContent: '-' },
            { data: 'status', render: function(val){
                const map = { planned: 'secondary', ready_at_port: 'info', on_board: 'primary', arrived: 'warning', under_bc: 'dark', released: 'success', delivered_to_main_wh: 'success', received: 'success' };
                const cls = map[val] || 'secondary';
                const label = (val||'-').replaceAll('_',' ').toUpperCase();
                return `<span class="badge badge-light-${cls}">${label}</span>`;
            } },
            { data: 'items_count', render: v => nf.format(v) },
            { data: 'koli_expected_total', defaultContent: 0, render: v => nf.format(v) },
            {
                data: 'id', className: 'text-end', orderable: false, searchable: false,
                render: function(id){
                    let html='';
                    if (canUpdate) html += `<a href=\"${editTpl.replace(':id', id)}\" class=\"btn btn-light-primary btn-sm me-2\">Edit</a>`;
                    if (canDelete) html += `<form method=\"POST\" action=\"${delTpl.replace(':id', id)}\" style=\"display:inline\">@csrf @method('DELETE')<button class=\"btn btn-light-danger btn-sm\" onclick=\"return confirm('Hapus shipment ini?')\">Hapus</button></form>`;
                    return html || '-';
                }
            }
        ],
        footerCallback: function(row, data){
            let items = 0, koli = 0;
            data.forEach(r => { items += parseFloat(r.items_count||0); koli += parseFloat(r.koli_expected_total||0); });
            document.getElementById('ft_items_count').textContent = nf.format(items);
            document.getElementById('ft_koli_expected').textContent = nf.format(koli);
        }
    });

    // Filters
    const statusSel = document.getElementById('filter_status');
    const fromInput = document.getElementById('filter_from');
    const toInput = document.getElementById('filter_to');
    const resetBtn = document.getElementById('btn_reset_filters');

    statusSel.addEventListener('change', function(){
        table.column(7).search(this.value).draw(); // status column index after adding Code column
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
            const data = this.data(); const d = data.etd || '';
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
