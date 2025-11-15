@extends('layouts.admin')

@section('title', 'Procurement - Purchase Orders')

@section('page_title', 'Purchase Orders')

@section('page_actions')
    @php use App\Support\Permission as Perm; @endphp
    @php $canCreate = Perm::can(auth()->user(), 'admin.procurement.purchase-orders.index', 'create'); @endphp
    <div class="d-flex gap-2">
        <a href="{{ route('admin.procurement.purchase-orders.report') }}" class="btn btn-light-info">Laporan Pemenuhan</a>
        @if ($canCreate)
            <a href="{{ route('admin.procurement.purchase-orders.create') }}" class="btn btn-primary">Create PO</a>
        @endif
    </div>
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
                        <table class="table table-row-bordered table-row-gray-100 table-hover align-middle gy-3 fs-6"
                            id="po_table">
                            <thead>
                                <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                    <th class="text-end">ID</th>
                                    <th>Code</th>
                                    <th>Ref</th>
                                    <th>Tgl PO</th>
                                    <th class="text-end">Lines</th>
                                    <th class="text-end">Qty Ordered</th>
                                    <th class="text-end">Cnt Ordered</th>
                                    <th class="text-end">Qty Fulfilled</th>
                                    <th class="text-end">Qty Open</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <th colspan="5" class="text-end">Totals:</th>
                                    <th id="ft_qty_ordered" class="text-end cell-number">0</th>
                                    <th id="ft_cnt_ordered" class="text-end cell-number">0</th>
                                    <th id="ft_qty_fulfilled" class="text-end cell-number">0</th>
                                    <th id="ft_qty_open" class="text-end cell-number">0</th>
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
                const csrfToken = '{{ csrf_token() }}';
                const dataUrl = '{{ route('admin.procurement.purchase-orders.data') }}';
                const editTpl = '{{ route('admin.procurement.purchase-orders.edit', ':id') }}';
                const viewTpl = '{{ route('admin.procurement.purchase-orders.show', ':id') }}';
                const delTpl = '{{ route('admin.procurement.purchase-orders.destroy', ':id') }}';
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
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-bold fs-7 w-200px py-3" data-kt-menu="true">
                    ${items.join('')}
                </div>
            </div>
        `.trim();
                };
                const canUpdate = {{ $canUpdate ? 'true' : 'false' }};
                const canDelete = {{ $canDelete ? 'true' : 'false' }};
                const canView = {{ $canView ? 'true' : 'false' }};
                const nf = new Intl.NumberFormat('id-ID', {
                    maximumFractionDigits: 4
                });
                const formatNumeric = (value) =>
                    `<div class="text-end cell-number fw-semibold">${nf.format(value ?? 0)}</div>`;
                const table = $('#po_table').DataTable({
                    processing: true,
                    serverSide: true,
                    searchDelay: 300,
                    order: [
                        [3, 'desc']
                    ],
                    ajax: {
                        url: dataUrl,
                        type: 'GET',
                        data: function(d) {
                            d.status = document.getElementById('filter_status').value;
                            d.date_from = document.getElementById('filter_from').value;
                            d.date_to = document.getElementById('filter_to').value;
                        },
                        dataSrc: 'data',
                        error: function(xhr) {
                            console.error('PO AJAX error:', xhr.responseText);
                            AppSwal.error('Gagal memuat data Purchase Orders');
                        }
                    },
                    columns: [{
                            data: 'id',
                            className: 'cell-number',
                            render: v => formatNumeric(v)
                        },
                        {
                            data: 'code',
                            render: function(val, t, row) {
                                const href = editTpl.replace(':id', row.id);
                                const text = val || '-';
                                return `<a href="${href}" class="text-primary text-decoration-underline">${text}</a>`;
                            }
                        },
                        {
                            data: 'ref_no',
                            defaultContent: '-'
                        },
                        {
                            data: 'order_date'
                        },
                        {
                            data: 'lines_count',
                            defaultContent: 0,
                            className: 'cell-number',
                            render: v => formatNumeric(v)
                        },
                        {
                            data: 'qty_ordered',
                            className: 'cell-number',
                            render: v => formatNumeric(v)
                        },
                        {
                            data: 'cnt_ordered',
                            defaultContent: 0,
                            className: 'cell-number',
                            render: v => formatNumeric(v)
                        },
                        {
                            data: 'qty_fulfilled',
                            className: 'cell-number',
                            render: v => formatNumeric(v)
                        },
                        {
                            data: 'qty_open',
                            className: 'cell-number',
                            render: v => formatNumeric(v)
                        },
                        {
                            data: 'status',
                            render: function(val) {
                                const map = {
                                    open: 'warning',
                                    partial: 'info',
                                    fulfilled: 'success'
                                };
                                const cls = map[val] || 'secondary';
                                const label = (val || '-').toUpperCase();
                                return `<span class="badge badge-light-${cls}">${label}</span>`;
                            }
                        },
                        {
                            data: 'id',
                            className: 'text-end',
                            orderable: false,
                            searchable: false,
                            render: function(id) {
                                const menuItems = [];
                                if (canView) menuItems.push(
                                    `<div class="menu-item px-3"><a href="${viewTpl.replace(':id', id)}" class="menu-link px-3">View</a></div>`
                                    );
                                if (canUpdate) menuItems.push(
                                    `<div class="menu-item px-3"><a href="${editTpl.replace(':id', id)}" class="menu-link px-3">Edit</a></div>`
                                    );
                                if (canDelete) menuItems.push(
                                    `<div class="menu-item px-3"><a href="#" data-url="${delTpl.replace(':id', id)}" data-id="${id}" class="menu-link px-3 text-danger btn-delete">Hapus</a></div>`
                                    );
                                return renderActionsDropdown(menuItems);
                            }
                        }
                    ],
                    footerCallback: function(row, data) {
                        let qtyOrder = 0,
                            cntOrder = 0,
                            qtyFulfill = 0,
                            qtyOpen = 0;
                        data.forEach(r => {
                            qtyOrder += parseFloat(r.qty_ordered || 0);
                            cntOrder += parseFloat(r.cnt_ordered || 0);
                            qtyFulfill += parseFloat(r.qty_fulfilled || 0);
                            qtyOpen += parseFloat(r.qty_open || 0);
                        });
                        document.getElementById('ft_qty_ordered').textContent = nf.format(qtyOrder);
                        document.getElementById('ft_cnt_ordered').textContent = nf.format(cntOrder);
                        document.getElementById('ft_qty_fulfilled').textContent = nf.format(qtyFulfill);
                        document.getElementById('ft_qty_open').textContent = nf.format(qtyOpen);
                    }
                });
                const refreshMenus = () => {
                    if (window.KTMenu) KTMenu.createInstances();
                };
                refreshMenus();
                table.on('draw', refreshMenus);

                // Filters
                const statusSel = document.getElementById('filter_status');
                const fromInput = document.getElementById('filter_from');
                const toInput = document.getElementById('filter_to');
                const resetBtn = document.getElementById('btn_reset_filters');

                statusSel.addEventListener('change', function() {
                    table.ajax.reload();
                });

                function withinDate(d, from, to) {
                    if (!d) return true;
                    if (from && d < from) return false;
                    if (to && d > to) return false;
                    return true;
                }

                function applyDateFilter() {
                    table.ajax.reload();
                }
                fromInput.addEventListener('change', applyDateFilter);
                toInput.addEventListener('change', applyDateFilter);
                resetBtn.addEventListener('click', function() {
                    statusSel.value = '';
                    fromInput.value = '';
                    toInput.value = '';
                    const topSearch = document.getElementById('global_search');
                    if (topSearch) topSearch.value = '';
                    table.search('');
                    table.ajax.reload();
                });

                $('#po_table').on('click', '.btn-delete', async function(e) {
                    e.preventDefault();
                    const url = this.getAttribute('data-url');
                    if (!url) return;
                    const confirmed = await AppSwal.confirm('Hapus PO ini?', {
                        confirmButtonText: 'Hapus'
                    });
                    if (!confirmed) return;
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: new URLSearchParams({
                            _method: 'DELETE'
                        })
                    }).then(res => {
                        if (res.ok) {
                            table.ajax.reload(null, false);
                        } else {
                            AppSwal.error('Gagal menghapus PO');
                        }
                    }).catch(() => AppSwal.error('Gagal menghapus PO'));
                });

                // Hook topbar global search to this table (debounced)
                (function() {
                    const topSearch = document.getElementById('global_search');
                    if (!topSearch) return;
                    let tmr;
                    const run = (q) => table.search(q).draw();
                    topSearch.addEventListener('input', function() {
                        clearTimeout(tmr);
                        const q = this.value || '';
                        tmr = setTimeout(() => run(q), 200);
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
                $('#filter_from, #filter_to').flatpickr()
            </script>
        @endpush
    @endpush
@endsection
