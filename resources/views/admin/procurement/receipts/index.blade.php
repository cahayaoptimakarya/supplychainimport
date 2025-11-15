@extends('layouts.admin')

@section('title', 'Warehouse Receipts')

@section('page_title', 'Warehouse Receipts')

@section('page_actions')
@php use App\Support\Permission as Perm; @endphp
@php $canCreate = Perm::can(auth()->user(), 'admin.procurement.receipts.index', 'create'); @endphp
@if($canCreate)
<a href="{{ route('admin.procurement.receipts.create') }}" class="btn btn-primary">Create Receipt</a>
@endif
<a href="{{ route('admin.procurement.reports.receipts') }}" class="btn btn-light-info">Laporan Receipt</a>
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
        <div class="card">
            <div class="card-body py-6">
                <div class="row g-3 mb-4 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select id="filter_status" class="form-select form-select-solid">
                            <option value="">All</option>
                            <option value="draft">Draft</option>
                            <option value="posted">Posted</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Received From</label>
                        <input type="text" id="filter_from" class="form-control js-fp-date form-control-solid" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Received To</label>
                        <input type="text" id="filter_to" class="form-control js-fp-date form-control-solid" />
                    </div>
                    <div class="col-md-3">
                        <button id="btn_reset_filters" class="btn btn-light">Reset Filters</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-gray-100 table-hover align-middle gy-3 fs-6" id="rcp_table">
                        <thead>
                        <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                            <th class="text-end">ID</th>
                            <th>Code</th>
                            <th>Shipment</th>
                            <th>Warehouse</th>
                            <th>Received At</th>
                            <th>Status</th>
                            <th class="text-end">Qty Total</th>
                            <th class="text-end">Cnt Received</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                        <tr class="fw-bold">
                            <th colspan="6" class="text-end">Totals:</th>
                            <th id="ft_qty_total" class="text-end cell-number">0</th>
                            <th id="ft_cnt_total" class="text-end cell-number">0</th>
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
    const csrfToken = '{{ csrf_token() }}';
    const dataUrl = '{{ route('admin.procurement.receipts.data') }}';
    const nf = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 });
    const formatNumeric = (value) => `<span class="cell-number d-block text-end fw-semibold">${nf.format(value ?? 0)}</span>`;
    const editTpl = '{{ route('admin.procurement.receipts.edit', ':id') }}';
    const delTpl  = '{{ route('admin.procurement.receipts.destroy', ':id') }}';
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
    const table = $('#rcp_table').DataTable({
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
                console.error('Receipts AJAX error:', xhr.responseText);
                AppSwal.error('Gagal memuat data Receipts');
            }
        },
        columns: [
            { data: 'id', className: 'cell-number', render: v => formatNumeric(v) },
            { data: 'code' },
            { data: 'shipment' },
            { data: 'warehouse' },
            { data: 'received_at' },
            { data: 'status', render: function(val){
                const map = { draft: 'secondary', posted: 'success' };
                const cls = map[val] || 'secondary';
                return `<span class="badge badge-light-${cls}">${(val||'-').toUpperCase()}</span>`;
            } },
            { data: 'qty_total', className: 'cell-number', render: v => formatNumeric(v) },
            { data: 'cnt_total', defaultContent: 0, className: 'cell-number', render: v => formatNumeric(v) },
            {
                data: 'id', className: 'text-end', orderable: false, searchable: false,
                render: function(id){
                    const editUrl = editTpl.replace(':id', id);
                    const delUrl = delTpl.replace(':id', id);
                    const menuItems = [
                        `<div class="menu-item px-3"><a href="${editUrl}" class="menu-link px-3">Edit</a></div>`,
                        `<div class="menu-item px-3"><a href="#" data-url="${delUrl}" data-id="${id}" class="menu-link px-3 text-danger btn-delete">Hapus</a></div>`
                    ];
                    return renderActionsDropdown(menuItems);
                }
            }
        ],
        footerCallback: function(row, data){
            let qty=0, cnt=0; data.forEach(r=>{ qty += parseFloat(r.qty_total||0); cnt += parseFloat(r.cnt_total||0); });
            document.getElementById('ft_qty_total').textContent = nf.format(qty);
            document.getElementById('ft_cnt_total').textContent = nf.format(cnt);
        }
    });
    const refreshMenus = () => { if (window.KTMenu) KTMenu.createInstances(); };
    refreshMenus();
    table.on('draw', refreshMenus);

    // Filters
    const statusSel = document.getElementById('filter_status');
    const fromInput = document.getElementById('filter_from');
    const toInput = document.getElementById('filter_to');
    const resetBtn = document.getElementById('btn_reset_filters');

    statusSel.addEventListener('change', function(){ table.ajax.reload(); });
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

    $('#rcp_table').on('click', '.btn-delete', async function(e){
        e.preventDefault();
        const url = this.getAttribute('data-url');
        if (!url) return;
        const confirmed = await AppSwal.confirm('Hapus receipt ini?', {
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
                AppSwal.error('Gagal menghapus receipt');
            }
        }).catch(() => AppSwal.error('Gagal menghapus receipt'));
    });
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
