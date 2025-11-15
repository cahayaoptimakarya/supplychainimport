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
                    <table class="table table-row-bordered table-row-gray-100 table-hover align-middle gy-3 fs-6" id="po_report_table">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th>SKU</th>
                                <th>Item</th>
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
                                <th colspan="2" class="text-end">Totals:</th>
                                <th id="ft_qty_ordered" class="text-end cell-number">0</th>
                                <th id="ft_qty_fulfilled" class="text-end cell-number">0</th>
                                <th id="ft_belum" class="text-end cell-number">0</th>
                                <th id="ft_jalan" class="text-end cell-number">0</th>
                                <th id="ft_pelabuhan" class="text-end cell-number">0</th>
                                <th id="ft_gudang" class="text-end cell-number">0</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="itemDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemDetailTitle">Detail Item</h5>
                <button type="button" class="btn btn-sm btn-icon" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-outline ki-cross fs-2"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="itemDetailTable">
                        <thead>
                            <tr class="text-uppercase text-gray-500 fw-bold fs-8">
                                <th>PO Code</th>
                                <th>Tgl PO</th>
                                <th class="text-end">Qty Ordered</th>
                                <th class="text-end">Qty Fulfilled</th>
                                <th class="text-end">Qty Remaining</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="6" class="text-center py-6">...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <div class="text-muted small" id="itemDetailMeta"></div>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .cell-number {
        text-align: right !important;
        font-variant-numeric: tabular-nums;
    }
</style>
<link href="{{ asset('metronic/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@push('scripts')
<script src="{{ asset('metronic/plugins/custom/datatables/datatables.bundle.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    flatpickr('.js-fp-date', { dateFormat: 'Y-m-d' });

    const dataUrl = '{{ route('admin.procurement.purchase-orders.report-data') }}';
    const nf = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
    const detailUrlTpl = '{{ route('admin.procurement.purchase-orders.report-item-detail', ':item') }}';
    const detailModalEl = document.getElementById('itemDetailModal');
    const detailModal = detailModalEl ? new bootstrap.Modal(detailModalEl) : null;
    const detailBody = detailModalEl ? detailModalEl.querySelector('#itemDetailTable tbody') : null;
    const detailMeta = detailModalEl ? document.getElementById('itemDetailMeta') : null;
    const detailTitle = detailModalEl ? document.getElementById('itemDetailTitle') : null;

    const formatNumeric = (value) => `<span class="cell-number d-block text-end fw-semibold">${nf.format(value ?? 0)}</span>`;
    const table = $('#po_report_table').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        order: [[0, 'asc']],
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
                AppSwal.error('Gagal memuat data laporan.');
            }
        },
        columns: [
            { data: 'sku', render: function(val){
                return `<span class="fw-bold">${val || '-'}</span>`;
            }},
            { data: 'item_name', render: function(val, type, row){
                const name = val || '-';
                if (!row.item_id) {
                    return name;
                }
                return `<button type="button" class="btn btn-link p-0 js-item-detail" data-item="${row.item_id}" data-sku="${row.sku || ''}" data-name="${name}">${name}</button>`;
            }},
            { data: 'qty_ordered', className: 'cell-number', render: v => formatNumeric(v) },
            { data: 'qty_fulfilled', className: 'cell-number', render: v => formatNumeric(v) },
            { data: 'belum_dikirim', className: 'cell-number', render: v => formatNumeric(v) },
            { data: 'masih_dijalan', className: 'cell-number', render: v => formatNumeric(v) },
            { data: 'di_pelabuhan', className: 'cell-number', render: v => formatNumeric(v) },
            { data: 'diterima_gudang', className: 'cell-number', render: v => formatNumeric(v) },
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

    $('#po_report_table').on('click', '.js-item-detail', function () {
        if (!detailModal || !detailBody) return;
        const itemId = this.dataset.item;
        const sku = this.dataset.sku || '-';
        const name = this.dataset.name || '-';
        detailTitle.textContent = `${sku} — ${name}`;
        detailMeta.textContent = 'Memuat data...';
        detailBody.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-muted">Memuat data...</td></tr>';
        detailModal.show();

        fetch(detailUrlTpl.replace(':item', itemId))
            .then(response => {
                if (!response.ok) throw new Error('Gagal memuat data');
                return response.json();
            })
            .then(payload => {
                const lines = payload.lines || [];
                if (!lines.length) {
                    detailBody.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-muted">Tidak ada PO dengan sisa qty.</td></tr>';
                    detailMeta.textContent = 'Semua PO untuk item ini sudah terpenuhi.';
                    return;
                }
                detailBody.innerHTML = lines.map(line => {
                    return `<tr>
                        <td>${line.po_code || '-'}</td>
                        <td>${line.order_date || '-'}</td>
                        <td class="text-end">${nf.format(line.qty_ordered || 0)}</td>
                        <td class="text-end">${nf.format(line.qty_fulfilled || 0)}</td>
                        <td class="text-end fw-bold">${nf.format(line.qty_remaining || 0)}</td>
                        <td><span class="badge badge-light-${line.status === 'fulfilled' ? 'success' : (line.status === 'partial' ? 'info' : 'warning')}">${(line.status || '').toUpperCase()}</span></td>
                    </tr>`;
                }).join('');
                detailMeta.textContent = `${lines.length} PO masih memiliki sisa qty untuk item ini.`;
            })
            .catch(() => {
                detailBody.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-danger">Gagal memuat detail.</td></tr>';
                detailMeta.textContent = 'Terjadi kesalahan saat memuat data.';
            });
    });
});
</script>
@endpush
