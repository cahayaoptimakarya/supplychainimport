@extends('layouts.admin')

@section('title', 'Procurement - Shipments')

@section('page_title', 'Shipments')

@section('page_actions')
@php use App\Support\Permission as Perm; @endphp
@php $canCreate = Perm::can(auth()->user(), 'admin.procurement.shipments.index', 'create'); @endphp
@if($canCreate)
<a href="{{ route('admin.procurement.shipments.create') }}" class="btn btn-primary">Create Shipment</a>
@endif
<a href="{{ route('admin.procurement.reports.shipments') }}" class="btn btn-light-info">Laporan Shipment</a>
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
        <div class="card">
            <div class="card-body py-6">
                <div class="row g-3 mb-4 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select id="filter_status" class="form-select form-select-solid">
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
                        <input type="text" id="filter_from" class="form-control js-fp-date form-control-solid" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ETD To</label>
                        <input type="text" id="filter_to" class="form-control js-fp-date form-control-solid" />
                    </div>
                    <div class="col-md-3">
                        <button id="btn_reset_filters" class="btn btn-light">Reset Filters</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-gray-100 table-hover align-middle gy-3 fs-6" id="ship_table">
                        <thead>
                        <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                            <th class="text-end">ID</th>
                            <th>Code</th>
                            <th>Container</th>
                            <th>PL</th>
                            <th>ETD</th>
                            <th>ETA</th>
                            <th>Status</th>
                            <th class="text-end">Items</th>
                            <th class="text-end">Cnt Expected</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                        <tr class="fw-bold">
                            <th colspan="7" class="text-end">Totals:</th>
                            <th id="ft_items_count" class="text-end cell-number">0</th>
                            <th id="ft_cnt_expected" class="text-end cell-number">0</th>
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
    const csrfToken = '{{ csrf_token() }}';
    const dataUrl = '{{ route('admin.procurement.shipments.data') }}';
    const editTpl = '{{ route('admin.procurement.shipments.edit', ':id') }}';
    const delTpl  = '{{ route('admin.procurement.shipments.destroy', ':id') }}';
    const canUpdate = {{ $canUpdate ? 'true' : 'false' }};
    const canDelete = {{ $canDelete ? 'true' : 'false' }};
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
    const nf = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 });
    const formatNumeric = (value) => `<span class="cell-number d-block text-end fw-semibold">${nf.format(value ?? 0)}</span>`;
    const table = $('#ship_table').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 300,
        order: [[4, 'desc']],
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
                console.error('Shipments AJAX error:', xhr.responseText);
                AppSwal.error('Gagal memuat data Shipments');
            }
        },
        columns: [
            { data: 'id', className: 'cell-number', render: v => formatNumeric(v) },
            { data: 'code', render: function(val, t, row){
                const href = editTpl.replace(':id', row.id);
                const text = val || '-';
                return `<a href=\"${href}\" class=\"text-primary text-decoration-underline\">${text}</a>`;
            }},
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
            { data: 'items_count', className: 'cell-number', render: v => formatNumeric(v) },
            { data: 'cnt_expected_total', defaultContent: 0, className: 'cell-number', render: v => formatNumeric(v) },
            {
                data: 'id', className: 'text-end', orderable: false, searchable: false,
                render: function(id){
                    const menuItems = [];
                    if (canUpdate) menuItems.push(`<div class="menu-item px-3"><a href="${editTpl.replace(':id', id)}" class="menu-link px-3">Edit</a></div>`);
                    if (canDelete) menuItems.push(`<div class="menu-item px-3"><a href="#" data-url="${delTpl.replace(':id', id)}" data-id="${id}" class="menu-link px-3 text-danger btn-delete">Hapus</a></div>`);
                    return renderActionsDropdown(menuItems);
                }
            }
        ],
        footerCallback: function(row, data){
            let items = 0, cnt = 0;
            data.forEach(r => { items += parseFloat(r.items_count||0); cnt += parseFloat(r.cnt_expected_total||0); });
            document.getElementById('ft_items_count').textContent = nf.format(items);
            document.getElementById('ft_cnt_expected').textContent = nf.format(cnt);
        }
    });

    // Filters
    const statusSel = document.getElementById('filter_status');
    const fromInput = document.getElementById('filter_from');
    const toInput = document.getElementById('filter_to');
    const resetBtn = document.getElementById('btn_reset_filters');

    statusSel.addEventListener('change', function(){ table.ajax.reload(); });
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

    const refreshMenus = () => { if (window.KTMenu) KTMenu.createInstances(); };
    refreshMenus();
    table.on('draw', refreshMenus);

    $('#ship_table').on('click', '.btn-delete', async function(e){
        e.preventDefault();
        const url = this.getAttribute('data-url');
        if (!url) return;
        const confirmed = await AppSwal.confirm('Hapus shipment ini?', {
            confirmButtonText: 'Hapus'
        });
        if (!confirmed) return;
        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: new URLSearchParams({ _method: 'DELETE' })
        }).then(res => {
            if (res.ok) {
                table.ajax.reload(null, false);
            } else {
                AppSwal.error('Gagal menghapus shipment');
            }
        }).catch(() => AppSwal.error('Gagal menghapus shipment'));
    });

    // Hook topbar global search
    (function(){
        const topSearch = document.getElementById('global_search');
        if (!topSearch) return;
        let tmr; const run = (q)=> table.search(q).draw();
        topSearch.addEventListener('input', function(){
            clearTimeout(tmr); const q = this.value || '';
            tmr = setTimeout(()=> run(q), 200);
        });
    })();
});
</script>
@push('styles')
<style>
    .cell-number {
        text-align: right !important;
        font-variant-numeric: tabular-nums;
    }
</style>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    flatpickr('.js-fp-date', { dateFormat: 'Y-m-d' });
});
</script>
@endpush
@endpush
@endsection
