@extends('layouts.admin')

@section('title', 'Laporan Penerimaan Gudang')

@section('page_title', 'Laporan Penerimaan Gudang')

@section('page_actions')
<div class="d-flex flex-wrap gap-2">
    <a href="{{ route('admin.procurement.receipts.index') }}" class="btn btn-light">Daftar Penerimaan</a>
    <a href="{{ route('admin.procurement.reports.shipments') }}" class="btn btn-light-info">Laporan Shipment</a>
</div>
@endsection

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Procurement</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Laporan Penerimaan</span>
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="card mb-5">
            <div class="card-body py-5">
                <div class="row g-4 align-items-end">
                    <div class="col-lg-3 col-md-4">
                        <label class="form-label">Status</label>
                        <select id="rcp_filter_status" class="form-select form-select-solid">
                            <option value="">Semua Status</option>
                            <option value="draft">Draft</option>
                            <option value="posted">Sudah Diposting</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4">
                        <label class="form-label">Gudang</label>
                        <select id="rcp_filter_warehouse" class="form-select form-select-solid">
                            <option value="">Semua Gudang</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4">
                        <label class="form-label">Tanggal Dari</label>
                        <input type="date" id="rcp_filter_from" class="form-control form-control-solid" />
                    </div>
                    <div class="col-lg-3 col-md-4">
                        <label class="form-label">Tanggal Sampai</label>
                        <input type="date" id="rcp_filter_to" class="form-control form-control-solid" />
                    </div>
                    <div class="col-lg-3 col-md-4">
                        <label class="form-label">Pencarian</label>
                        <input type="text" id="rcp_filter_search" class="form-control form-control-solid" placeholder="Kode, gudang, container" />
                    </div>
                    <div class="col-lg-3 col-md-4">
                        <button type="button" class="btn btn-primary w-100" id="rcp_btn_refresh">
                            <span class="indicator-label">Terapkan Filter</span>
                        </button>
                    </div>
                    <div class="col-lg-3 col-md-4 order-lg-last">
                        <button type="button" class="btn btn-light w-100" id="rcp_btn_reset">Reset</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="rcp_alert" class="alert alert-danger d-none mb-5"></div>

        <div class="row g-4 mb-6">
            <div class="col-xl-3">
                <div class="report-stat-card h-100">
                    <span class="report-stat-card__label text-muted">Total Receipt</span>
                    <div class="report-stat-card__value" id="rcp_stat_total">0</div>
                    <span class="text-muted fs-8">Mengikuti filter</span>
                </div>
            </div>
            <div class="col-xl-3">
                <div class="report-stat-card h-100">
                    <span class="report-stat-card__label text-muted">Total Item Lines</span>
                    <div class="report-stat-card__value" id="rcp_stat_lines">0</div>
                    <span class="text-muted fs-8">Jumlah baris item diterima</span>
                </div>
            </div>
            <div class="col-xl-3">
                <div class="report-stat-card h-100">
                    <span class="report-stat-card__label text-muted">Qty Received</span>
                    <div class="report-stat-card__value" id="rcp_stat_qty">0</div>
                    <span class="text-muted fs-8">Total kuantitas yang masuk</span>
                </div>
            </div>
            <div class="col-xl-3">
                <div class="report-stat-card h-100">
                    <span class="report-stat-card__label text-muted">Cnt Received</span>
                    <div class="report-stat-card__value" id="rcp_stat_cnt">0</div>
                    <span class="text-muted fs-8">Total koli/kontainer diterima</span>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-xxl-6">
                <div class="card h-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title fw-bold fs-4 mb-0">Status Penerimaan</h3>
                    </div>
                    <div class="card-body" id="rcp_status_breakdown">
                        <div class="text-muted text-center py-6">Menunggu data...</div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-6">
                <div class="card h-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title fw-bold fs-4 mb-0">Gudang Teratas</h3>
                    </div>
                    <div class="card-body" id="rcp_warehouse_breakdown">
                        <div class="text-muted text-center py-6">Menunggu data...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title fw-bold fs-3 mb-0">Daftar Penerimaan</h3>
            </div>
            <div class="card-body py-5">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed">
                        <thead>
                            <tr class="text-muted text-uppercase fw-bold fs-8">
                                <th>Kode</th>
                                <th>Status</th>
                                <th>Gudang</th>
                                <th>Shipment / Container</th>
                                <th>Waktu Terima</th>
                                <th class="text-end">Item Lines</th>
                                <th class="text-end">Qty Received</th>
                                <th class="text-end">Cnt Received</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="rcp_table_body">
                            <tr>
                                <td colspan="9" class="text-center text-muted py-10">Menunggu data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .report-stat-card {
        border-radius: 1.25rem;
        border: 1px solid #eef2f7;
        padding: 1.5rem;
        background: #fff;
        box-shadow: 0 10px 30px rgba(15,23,42,.05);
    }
    .report-stat-card__label {
        font-size: .85rem;
        text-transform: uppercase;
        letter-spacing: .08em;
    }
    .report-stat-card__value {
        font-size: 2.4rem;
        font-weight: 700;
        margin: .35rem 0 0.5rem;
    }
    .cell-number {
        font-variant-numeric: tabular-nums;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const dataUrl = '{{ route('admin.procurement.reports.receipts-data') }}';
    const editTemplate = '{{ route('admin.procurement.receipts.edit', ['receipt' => '__RECEIPT_ID__']) }}';
    const nf = new Intl.NumberFormat('id-ID');
    const qtyFmt = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
    const filters = {
        status: document.getElementById('rcp_filter_status'),
        warehouse: document.getElementById('rcp_filter_warehouse'),
        from: document.getElementById('rcp_filter_from'),
        to: document.getElementById('rcp_filter_to'),
        search: document.getElementById('rcp_filter_search'),
    };
    const btnRefresh = document.getElementById('rcp_btn_refresh');
    const btnReset = document.getElementById('rcp_btn_reset');
    const alertBox = document.getElementById('rcp_alert');
    const tableBody = document.getElementById('rcp_table_body');
    const statusList = document.getElementById('rcp_status_breakdown');
    const warehouseList = document.getElementById('rcp_warehouse_breakdown');

    function buildParams(){
        const params = new URLSearchParams();
        if (filters.status.value) params.append('status', filters.status.value);
        if (filters.warehouse.value) params.append('warehouse_id', filters.warehouse.value);
        if (filters.from.value) params.append('date_from', filters.from.value);
        if (filters.to.value) params.append('date_to', filters.to.value);
        if (filters.search.value.trim() !== '') params.append('search', filters.search.value.trim());
        return params;
    }

    function setLoading(isLoading){
        btnRefresh.disabled = isLoading;
        btnRefresh.innerHTML = isLoading
            ? '<span class="spinner-border spinner-border-sm me-2"></span>Memuat'
            : '<span class="indicator-label">Terapkan Filter</span>';
    }

    function hideAlert(){
        alertBox.classList.add('d-none');
        alertBox.textContent = '';
    }

    function showAlert(message){
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    async function loadData(){
        setLoading(true);
        hideAlert();
        try{
            const response = await fetch(`${dataUrl}?${buildParams().toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok){
                throw new Error('Gagal memuat data penerimaan');
            }
            const payload = await response.json();
            updateSummary(payload.summary || {});
            renderRows(payload.rows || []);
        }catch(err){
            console.error(err);
            showAlert(err.message || 'Terjadi kesalahan server');
            renderRows([]);
            updateSummary({});
        }finally{
            setLoading(false);
        }
    }

    function updateSummary(summary){
        document.getElementById('rcp_stat_total').textContent = nf.format(summary.total_receipts || 0);
        document.getElementById('rcp_stat_lines').textContent = nf.format(summary.lines || 0);
        document.getElementById('rcp_stat_qty').textContent = qtyFmt.format(summary.qty_received || 0);
        document.getElementById('rcp_stat_cnt').textContent = qtyFmt.format(summary.cnt_received || 0);
        renderStatusBreakdown(Array.isArray(summary.status_breakdown) ? summary.status_breakdown : []);
        renderWarehouseBreakdown(Array.isArray(summary.warehouse_breakdown) ? summary.warehouse_breakdown : []);
    }

    function renderStatusBreakdown(list){
        if (!list.length){
            statusList.innerHTML = '<div class="text-muted text-center py-6">Belum ada data</div>';
            return;
        }
        statusList.innerHTML = list.map(row => {
            const badge = row.status === 'posted' ? 'success' : 'secondary';
            return `<div class="d-flex justify-content-between align-items-center py-2 border-bottom border-gray-200">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge badge-light-${badge}">${row.label}</span>
                </div>
                <span class="fw-bold">${nf.format(row.total || 0)}</span>
            </div>`;
        }).join('');
    }

    function renderWarehouseBreakdown(list){
        if (!list.length){
            warehouseList.innerHTML = '<div class="text-muted text-center py-6">Belum ada data</div>';
            return;
        }
        warehouseList.innerHTML = list.map(row => {
            return `<div class="d-flex justify-content-between align-items-center py-2 border-bottom border-gray-200">
                <span class="fw-semibold">${row.warehouse || '-'}</span>
                <span class="fw-bold">${nf.format(row.total || 0)}</span>
            </div>`;
        }).join('');
    }

    function renderRows(rows){
        if (!rows.length){
            tableBody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-10">Tidak ada data sesuai filter.</td></tr>';
            return;
        }
        tableBody.innerHTML = rows.map(row => {
            const href = editTemplate.replace('__RECEIPT_ID__', row.id);
            const badge = row.status === 'posted' ? 'success' : 'secondary';
            return `<tr>
                <td>
                    <div class="d-flex flex-column">
                        <a href="${href}" class="fw-bold text-primary">${row.code || '-'}</a>
                        <span class="text-muted fs-8">ID #${row.id || '-'}</span>
                    </div>
                </td>
                <td><span class="badge badge-light-${badge}">${row.status_label || row.status || '-'}</span></td>
                <td>${row.warehouse || '-'}</td>
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-semibold">${row.shipment || '-'}</span>
                    </div>
                </td>
                <td>${row.received_at || '-'}</td>
                <td class="text-end cell-number">${nf.format(row.lines || 0)}</td>
                <td class="text-end cell-number">${qtyFmt.format(row.qty_total || 0)}</td>
                <td class="text-end cell-number">${qtyFmt.format(row.cnt_total || 0)}</td>
                <td class="text-end">
                    <a href="${href}" class="btn btn-sm btn-light-primary">Detail</a>
                </td>
            </tr>`;
        }).join('');
    }

    btnRefresh.addEventListener('click', loadData);
    btnReset.addEventListener('click', function(){
        Object.values(filters).forEach(input => input.value = '');
        loadData();
    });
    Object.values(filters).forEach(input => {
        input.addEventListener('change', function(e){
            if (e.target === filters.search) return;
            loadData();
        });
    });
    filters.search.addEventListener('keydown', function(e){
        if (e.key === 'Enter'){
            e.preventDefault();
            loadData();
        }
    });

    loadData();
});
</script>
@endpush
