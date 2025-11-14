@extends('layouts.admin')

@section('title', 'Laporan Shipment')

@section('page_title', 'Laporan Shipment')

@section('page_actions')
<div class="d-flex flex-wrap gap-2">
    <a href="{{ route('admin.procurement.shipments.index') }}" class="btn btn-light">Daftar Shipment</a>
    <a href="{{ route('admin.procurement.reports.receipts') }}" class="btn btn-light-info">Laporan Penerimaan</a>
</div>
@endsection

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Procurement</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Laporan Shipment</span>
@endsection

@php
    $shipmentStatusOptions = [
        'planned' => 'Direncanakan',
        'ready_at_port' => 'Siap di Pelabuhan',
        'on_board' => 'Berangkat',
        'arrived' => 'Tiba di Pelabuhan',
        'under_bc' => 'Under BC',
        'released' => 'Dirilis',
        'delivered_to_main_wh' => 'Tiba di WH Utama',
        'received' => 'Selesai',
    ];
    $shipmentStatusBadges = [
        'planned' => 'secondary',
        'ready_at_port' => 'info',
        'on_board' => 'primary',
        'arrived' => 'warning',
        'under_bc' => 'dark',
        'released' => 'success',
        'delivered_to_main_wh' => 'success',
        'received' => 'success',
    ];
@endphp

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="card mb-5">
            <div class="card-body py-5">
                <div class="row g-4 align-items-end">
                    <div class="col-lg-3 col-md-4">
                        <label class="form-label">Status</label>
                        <select id="filter_status" class="form-select form-select-solid">
                            <option value="">Semua Status</option>
                            @foreach($shipmentStatusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4">
                        <label class="form-label">ETD Dari</label>
                        <input type="date" id="filter_from" class="form-control form-control-solid" />
                    </div>
                    <div class="col-lg-3 col-md-4">
                        <label class="form-label">ETD Sampai</label>
                        <input type="date" id="filter_to" class="form-control form-control-solid" />
                    </div>
                    <div class="col-lg-3 col-md-4">
                        <label class="form-label">Pencarian</label>
                        <input type="text" id="filter_search" class="form-control form-control-solid" placeholder="Cari kode, container, atau PL" />
                    </div>
                    <div class="col-lg-3 col-md-4">
                        <button type="button" class="btn btn-primary w-100" id="btn_refresh">
                            <span class="indicator-label">Terapkan Filter</span>
                        </button>
                    </div>
                    <div class="col-lg-3 col-md-4 order-lg-last">
                        <button type="button" class="btn btn-light w-100" id="btn_reset">Reset</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="shipment_alert" class="alert alert-danger d-none mb-5"></div>

        <div class="row g-4 mb-6">
            <div class="col-xl-3">
                <div class="report-stat-card h-100">
                    <span class="report-stat-card__label text-muted">Total Shipment</span>
                    <div class="report-stat-card__value" id="stat_total_shipments">0</div>
                    <span class="text-muted fs-8">Mengikuti filter</span>
                </div>
            </div>
            <div class="col-xl-3">
                <div class="report-stat-card h-100">
                    <span class="report-stat-card__label text-muted">Total Item Lines</span>
                    <div class="report-stat-card__value" id="stat_total_lines">0</div>
                    <span class="text-muted fs-8">Jumlah baris item dalam shipment</span>
                </div>
            </div>
            <div class="col-xl-3">
                <div class="report-stat-card h-100">
                    <span class="report-stat-card__label text-muted">Qty Expected</span>
                    <div class="report-stat-card__value" id="stat_qty_expected">0</div>
                    <span class="text-muted fs-8">Total kuantitas yang direncanakan</span>
                </div>
            </div>
            <div class="col-xl-3">
                <div class="report-stat-card h-100">
                    <span class="report-stat-card__label text-muted">Cnt Expected</span>
                    <div class="report-stat-card__value" id="stat_cnt_expected">0</div>
                    <span class="text-muted fs-8">Total koli/kontainer di manifest</span>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-xxl-6">
                <div class="card h-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title fw-bold fs-4 mb-0">Status Shipment</h3>
                    </div>
                    <div class="card-body" id="shipment_status_breakdown">
                        <div class="text-muted text-center py-6">Menunggu data...</div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-6">
                <div class="card h-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title fw-bold fs-4 mb-0">ETA Lewat</h3>
                    </div>
                    <div class="card-body" id="shipment_late_breakdown">
                        <div class="text-muted text-center py-6">Menunggu data...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title fw-bold fs-3 mb-0">Daftar Shipment</h3>
            </div>
            <div class="card-body py-5">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed">
                        <thead>
                            <tr class="text-muted text-uppercase fw-bold fs-8">
                                <th>Kode</th>
                                <th>Status</th>
                                <th>Container / PL</th>
                                <th>ETD</th>
                                <th>ETA</th>
                                <th class="text-end">Item Lines</th>
                                <th class="text-end">Qty Expected</th>
                                <th class="text-end">Cnt Expected</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="shipment_table_body">
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
    const statusMeta = @json($shipmentStatusOptions);
    const statusBadges = @json($shipmentStatusBadges);
    const dataUrl = '{{ route('admin.procurement.reports.shipments-data') }}';
    const editTemplate = '{{ route('admin.procurement.shipments.edit', ['shipment' => '__SHIP_ID__']) }}';
    const nf = new Intl.NumberFormat('id-ID');
    const qtyFmt = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
    const filters = {
        status: document.getElementById('filter_status'),
        from: document.getElementById('filter_from'),
        to: document.getElementById('filter_to'),
        search: document.getElementById('filter_search'),
    };
    const btnRefresh = document.getElementById('btn_refresh');
    const btnReset = document.getElementById('btn_reset');
    const alertBox = document.getElementById('shipment_alert');
    const tableBody = document.getElementById('shipment_table_body');
    const statusList = document.getElementById('shipment_status_breakdown');
    const lateList = document.getElementById('shipment_late_breakdown');

    function buildParams(){
        const params = new URLSearchParams();
        if (filters.status.value) params.append('status', filters.status.value);
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
                throw new Error('Gagal memuat data shipment');
            }
            const payload = await response.json();
            updateSummary(payload.summary || {});
            renderTable(payload.rows || []);
            renderLate(payload.late_rows || payload.rows || []);
        }catch(err){
            console.error(err);
            showAlert(err.message || 'Terjadi kesalahan server');
            renderTable([]);
            updateSummary({});
        }finally{
            setLoading(false);
        }
    }

    function formatStatus(status){
        const label = statusMeta[status] || (status ? status.replace(/_/g, ' ') : '-');
        const badge = statusBadges[status] || 'secondary';
        return `<span class="badge badge-light-${badge}">${label}</span>`;
    }

    function updateSummary(summary){
        document.getElementById('stat_total_shipments').textContent = nf.format(summary.total_shipments || 0);
        document.getElementById('stat_total_lines').textContent = nf.format(summary.lines || 0);
        document.getElementById('stat_qty_expected').textContent = qtyFmt.format(summary.qty_expected || 0);
        document.getElementById('stat_cnt_expected').textContent = qtyFmt.format(summary.cnt_expected || 0);
        const statuses = Array.isArray(summary.status_breakdown) ? summary.status_breakdown : [];
        if (!statuses.length){
            statusList.innerHTML = '<div class="text-muted text-center py-6">Belum ada data</div>';
        }else{
            statusList.innerHTML = statuses.map(row => {
                const badge = statusBadges[row.status] || 'secondary';
                const label = row.label || row.status;
                return `<div class="d-flex justify-content-between align-items-center py-2 border-bottom border-gray-200">
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge badge-light-${badge}">${label}</span>
                    </div>
                    <span class="fw-bold">${nf.format(row.total || 0)}</span>
                </div>`;
            }).join('');
        }
    }

    function renderLate(rows){
        const lateRows = rows.filter(r => r.is_late).slice(0, 6);
        if (!lateRows.length){
            lateList.innerHTML = '<div class="text-muted text-center py-6">Tidak ada ETA yang terlewat</div>';
            return;
        }
        lateList.innerHTML = lateRows.map(row => {
            const href = editTemplate.replace('__SHIP_ID__', row.id);
            return `<div class="d-flex flex-column py-2 border-bottom border-gray-200">
                <div class="d-flex justify-content-between align-items-center">
                    <a href="${href}" class="fw-semibold text-primary">${row.code || '-'}</a>
                    <span class="badge badge-light-danger">Lewat ETA</span>
                </div>
                <div class="text-muted fs-8">ETA ${row.eta || '-'} &middot; Container ${row.container_no || '-'}</div>
            </div>`;
        }).join('');
    }

    function renderTable(rows){
        if (!rows.length){
            tableBody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-10">Tidak ada data sesuai filter.</td></tr>';
            renderLate([]);
            return;
        }
        tableBody.innerHTML = rows.map(row => {
            const href = editTemplate.replace('__SHIP_ID__', row.id);
            const etaInfo = row.eta
                ? `<div class="d-flex align-items-center gap-2">
                        <span>${row.eta}</span>
                        ${row.is_late ? '<span class="badge badge-light-danger">Lewat</span>' : ''}
                   </div>`
                : '-';
            return `<tr>
                <td>
                    <div class="d-flex flex-column">
                        <a href="${href}" class="fw-bold text-primary">${row.code || '-'}</a>
                        <span class="text-muted fs-8">${row.id ? 'ID #' + row.id : ''}</span>
                    </div>
                </td>
                <td>${formatStatus(row.status)}</td>
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-semibold">${row.container_no || '-'}</span>
                        <span class="text-muted fs-8">PL: ${row.pl_no || '-'}</span>
                    </div>
                </td>
                <td>${row.etd || '-'}</td>
                <td>${etaInfo}</td>
                <td class="text-end cell-number">${nf.format(row.items_count || 0)}</td>
                <td class="text-end cell-number">${qtyFmt.format(row.qty_expected || 0)}</td>
                <td class="text-end cell-number">${qtyFmt.format(row.cnt_expected || 0)}</td>
                <td class="text-end">
                    <a href="${href}" class="btn btn-sm btn-light-primary">Detail</a>
                </td>
            </tr>`;
        }).join('');
        renderLate(rows);
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
