@extends('layouts.admin')

@section('title', 'Laporan Pemenuhan PO')

@section('page_title', 'Laporan Pemenuhan PO')

@section('page_actions')
<a href="{{ route('admin.procurement.purchase-orders.index') }}" class="btn btn-light">Kembali ke Daftar PO</a>
@endsection

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Procurement</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Laporan Pemenuhan PO</span>
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="card">
            <div class="card-body py-6">
                <div class="row g-3 mb-4 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Status PO</label>
                        <select id="filter_status" class="form-select form-select-solid">
                            <option value="">Semua</option>
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
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="po_report_table">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th>Code</th>
                                <th>Ref</th>
                                <th>Tgl PO</th>
                                <th class="text-end">Qty Ordered</th>
                                <th class="text-end">Qty Fulfilled</th>
                                <th class="text-end">Belum Dikirim</th>
                                <th class="text-end">Masih Dijalan</th>
                                <th class="text-end">Di Pelabuhan</th>
                                <th class="text-end">Diterima Gudang</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <th colspan="3" class="text-end">Totals:</th>
                                <th id="ft_qty_ordered" class="text-end">0</th>
                                <th id="ft_qty_fulfilled" class="text-end">0</th>
                                <th id="ft_belum" class="text-end">0</th>
                                <th id="ft_jalan" class="text-end">0</th>
                                <th id="ft_pelabuhan" class="text-end">0</th>
                                <th id="ft_gudang" class="text-end">0</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('metronic/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
@endpush

@push('scripts')
<script src="{{ asset('metronic/plugins/custom/datatables/datatables.bundle.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    flatpickr('.js-fp-date', { dateFormat: 'Y-m-d' });

    const dataUrl = '{{ route('admin.procurement.purchase-orders.report-data') }}';
    const showTpl = '{{ route('admin.procurement.purchase-orders.show', ':id') }}';
    const nf = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
    const table = $('#po_report_table').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        order: [[2, 'desc']],
        ajax: {
            url: dataUrl,
            data: function (d) {
                d.status = document.getElementById('filter_status').value;
                d.date_from = document.getElementById('filter_from').value;
                d.date_to = document.getElementById('filter_to').value;
            },
            dataSrc: 'data',
            error: function (xhr) {
                console.error('Report AJAX error:', xhr.responseText);
                alert('Gagal memuat data laporan.');
            }
        },
        columns: [
            { data: 'code', render: function (val, type, row) {
                const href = showTpl.replace(':id', row.id);
                const text = val || '-';
                return `<a href="${href}" class="text-primary fw-semibold">${text}</a>`;
            }},
            { data: 'ref_no', defaultContent: '-' },
            { data: 'order_date', defaultContent: '-' },
            { data: 'qty_ordered', className: 'text-end', render: v => nf.format(v || 0) },
            { data: 'qty_fulfilled', className: 'text-end', render: v => nf.format(v || 0) },
            { data: 'belum_dikirim', className: 'text-end', render: v => nf.format(v || 0) },
            { data: 'masih_dijalan', className: 'text-end', render: v => nf.format(v || 0) },
            { data: 'di_pelabuhan', className: 'text-end', render: v => nf.format(v || 0) },
            { data: 'diterima_gudang', className: 'text-end', render: v => nf.format(v || 0) },
            { data: 'status', render: function (val) {
                const map = { open: 'warning', partial: 'info', fulfilled: 'success' };
                const cls = map[val] || 'secondary';
                return `<span class="badge badge-light-${cls}">${(val || '').toUpperCase() || '-'}</span>`;
            }},
        ],
        footerCallback: function (row, data) {
            let qtyOrdered = 0, qtyFull = 0, qtyBelum = 0, qtyJalan = 0, qtyPel = 0, qtyGudang = 0;
            data.forEach(r => {
                qtyOrdered += parseFloat(r.qty_ordered || 0);
                qtyFull += parseFloat(r.qty_fulfilled || 0);
                qtyBelum += parseFloat(r.belum_dikirim || 0);
                qtyJalan += parseFloat(r.masih_dijalan || 0);
                qtyPel += parseFloat(r.di_pelabuhan || 0);
                qtyGudang += parseFloat(r.diterima_gudang || 0);
            });
            document.getElementById('ft_qty_ordered').textContent = nf.format(qtyOrdered);
            document.getElementById('ft_qty_fulfilled').textContent = nf.format(qtyFull);
            document.getElementById('ft_belum').textContent = nf.format(qtyBelum);
            document.getElementById('ft_jalan').textContent = nf.format(qtyJalan);
            document.getElementById('ft_pelabuhan').textContent = nf.format(qtyPel);
            document.getElementById('ft_gudang').textContent = nf.format(qtyGudang);
        }
    });

    const statusSel = document.getElementById('filter_status');
    const fromInput = document.getElementById('filter_from');
    const toInput = document.getElementById('filter_to');
    const resetBtn = document.getElementById('btn_reset_filters');

    const reload = () => table.ajax.reload();
    statusSel.addEventListener('change', reload);
    fromInput.addEventListener('change', reload);
    toInput.addEventListener('change', reload);

    resetBtn.addEventListener('click', function () {
        statusSel.value = '';
        fromInput.value = '';
        toInput.value = '';
        const topSearch = document.getElementById('global_search');
        if (topSearch) {
            topSearch.value = '';
        }
        table.search('');
        table.ajax.reload();
    });

    (function () {
        const topSearch = document.getElementById('global_search');
        if (!topSearch) return;
        let tmr;
        const run = (q) => table.search(q).draw();
        topSearch.addEventListener('input', function () {
            clearTimeout(tmr);
            const q = this.value || '';
            tmr = setTimeout(() => run(q), 200);
        });
    })();
});
</script>
@endpush
