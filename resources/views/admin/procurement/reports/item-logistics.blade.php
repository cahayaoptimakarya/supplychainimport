@extends('layouts.admin')

@section('title', 'Status Pengiriman Item')

@section('page_title', 'Status Pengiriman Item')

@section('page_actions')
<div class="d-flex flex-wrap gap-2">
    <a href="{{ route('admin.procurement.purchase-orders.index') }}" class="btn btn-light">Daftar PO</a>
    <a href="{{ route('admin.procurement.purchase-orders.report') }}" class="btn btn-light-info">Laporan Pemenuhan PO</a>
</div>
@endsection

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Procurement</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Status Item</span>
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <p class="text-muted mb-0">Laporan ini menampilkan ringkasan seluruh item berdasarkan progres pengirimannya.</p>
            <button id="btn_reload" class="btn btn-primary btn-sm">Muat Ulang</button>
        </div>
        <div class="row g-5">
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header border-0 pt-5 pb-0">
                        <div>
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-dark">Belum Diproses Pengiriman</span>
                                <span class="text-muted fs-7">Qty dari PO yang belum dijadwalkan/berangkat</span>
                            </h3>
                        </div>
                        <span id="count_belum" class="badge badge-light-primary fs-7">0</span>
                    </div>
                    <div class="card-body pt-4 pb-0">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr class="text-gray-500 fw-semibold fs-8 text-uppercase">
                                        <th>Item</th>
                                        <th class="text-end">Qty</th>
                                    </tr>
                                </thead>
                                <tbody id="body_belum">
                                    <tr><td colspan="2" class="text-muted text-center py-6">Menunggu data...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header border-0 pt-5 pb-0">
                        <div>
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-dark">Sedang Dijalan</span>
                                <span class="text-muted fs-7">Qty di shipment yang sudah berangkat / transit</span>
                            </h3>
                        </div>
                        <span id="count_dijalan" class="badge badge-light-warning fs-7">0</span>
                    </div>
                    <div class="card-body pt-4 pb-0">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr class="text-gray-500 fw-semibold fs-8 text-uppercase">
                                        <th>Item</th>
                                        <th class="text-end">Qty</th>
                                    </tr>
                                </thead>
                                <tbody id="body_dijalan">
                                    <tr><td colspan="2" class="text-muted text-center py-6">Menunggu data...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header border-0 pt-5 pb-0">
                        <div>
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-dark">Sudah Diterima</span>
                                <span class="text-muted fs-7">Qty yang sudah mendarat di gudang</span>
                            </h3>
                        </div>
                        <span id="count_sudah" class="badge badge-light-success fs-7">0</span>
                    </div>
                    <div class="card-body pt-4 pb-0">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr class="text-gray-500 fw-semibold fs-8 text-uppercase">
                                        <th>Item</th>
                                        <th class="text-end">Qty</th>
                                    </tr>
                                </thead>
                                <tbody id="body_sudah">
                                    <tr><td colspan="2" class="text-muted text-center py-6">Menunggu data...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dataUrl = '{{ route('admin.procurement.reports.item-logistics-data') }}';
    const nf = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
    const sectionMap = {
        belum: {
            key: 'belum_proses',
            body: document.getElementById('body_belum'),
            count: document.getElementById('count_belum'),
            empty: 'Tidak ada item menunggu pengiriman'
        },
        dijalan: {
            key: 'sedang_dijalan',
            body: document.getElementById('body_dijalan'),
            count: document.getElementById('count_dijalan'),
            empty: 'Tidak ada item di perjalanan'
        },
        sudah: {
            key: 'sudah_diterima',
            body: document.getElementById('body_sudah'),
            count: document.getElementById('count_sudah'),
            empty: 'Belum ada item diterima'
        },
    };

    let loading = false;

    async function loadData() {
        if (loading) return;
        loading = true;
        setLoadingState(true);
        try {
            const response = await fetch(dataUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) {
                throw new Error('Gagal memuat data');
            }
            const payload = await response.json();
            Object.entries(sectionMap).forEach(([section, meta]) => {
                const list = payload[meta.key] || [];
                meta.count.textContent = list.length;
                if (!list.length) {
                    meta.body.innerHTML = `<tr><td colspan="2" class="text-center text-muted py-5">${meta.empty}</td></tr>`;
                    return;
                }
                meta.body.innerHTML = list.map(row => {
                    const name = row.name ? `${row.sku || ''} - ${row.name}`.replace(/^ - /, '').trim() : (row.sku || '-');
                    return `<tr>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="fw-semibold">${name}</span>
                            </div>
                        </td>
                        <td class="text-end fw-bold">${nf.format(row.qty || 0)}</td>
                    </tr>`;
                }).join('');
            });
        } catch (err) {
            console.error(err);
            Object.values(sectionMap).forEach(meta => {
                meta.body.innerHTML = `<tr><td colspan="2" class="text-center text-danger py-5">Gagal memuat data</td></tr>`;
            });
        } finally {
            setLoadingState(false);
            loading = false;
        }
    }

    function setLoadingState(isLoading) {
        const btn = document.getElementById('btn_reload');
        if (!btn) return;
        btn.disabled = isLoading;
        btn.innerHTML = isLoading ? '<span class="spinner-border spinner-border-sm me-2"></span>Memuat...' : 'Muat Ulang';
    }

    document.getElementById('btn_reload').addEventListener('click', loadData);

    loadData();
});
</script>
@endpush
