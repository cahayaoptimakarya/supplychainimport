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
        @php
            $cards = [
                [
                    'key' => 'belum',
                    'title' => 'Belum Diproses Pengiriman',
                    'subtitle' => 'Qty dari PO yang belum dijadwalkan/berangkat',
                    'badge' => 'badge-light-primary',
                    'icon' => 'ki-outline ki-calendar',
                    'icon_bg' => 'bg-light-primary',
                ],
                [
                    'key' => 'dijalan',
                    'title' => 'Sedang Dijalan',
                    'subtitle' => 'Qty di shipment yang sudah berangkat / transit',
                    'badge' => 'badge-light-warning',
                    'icon' => 'ki-outline ki-truck',
                    'icon_bg' => 'bg-light-warning',
                ],
                [
                    'key' => 'sudah',
                    'title' => 'Sudah Diterima',
                    'subtitle' => 'Qty yang sudah mendarat di gudang',
                    'badge' => 'badge-light-success',
                    'icon' => 'ki-outline ki-home',
                    'icon_bg' => 'bg-light-success',
                ],
            ];
        @endphp
        <div class="row g-5">
            @foreach($cards as $card)
            <div class="col-xl-4">
                <div class="card h-100 status-card status-card--{{ $card['key'] }}">
                    <div class="card-header border-0 pt-5 pb-0 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-45px me-3">
                                <div class="symbol-label {{ $card['icon_bg'] }} rounded-circle">
                                    <i class="{{ $card['icon'] }} fs-2 text-dark"></i>
                                </div>
                            </div>
                            <div>
                                <h3 class="card-title align-items-start flex-column mb-0">
                                    <span class="card-label fw-bold text-dark">{{ $card['title'] }}</span>
                                </h3>
                                <span class="text-muted fs-7">{{ $card['subtitle'] }}</span>
                            </div>
                        </div>
                        <span id="count_{{ $card['key'] }}" class="badge {{ $card['badge'] }} fs-7">0</span>
                    </div>
                    <div class="card-body pt-4 pb-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-row-bordered table-row-gray-100 align-middle gy-2 mb-0">
                                <thead>
                                    <tr class="text-gray-500 fw-semibold fs-8 text-uppercase">
                                        <th>Item</th>
                                        <th class="text-end">Qty</th>
                                    </tr>
                                </thead>
                                <tbody id="body_{{ $card['key'] }}">
                                    <tr><td colspan="2" class="text-muted text-center py-6">Menunggu data...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
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
    .status-card .symbol-label {
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .status-card table tbody tr td:first-child span {
        color: #152036;
    }
    .status-card table tbody tr td:first-child .badge-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }
</style>
@endpush

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
                const colorMap = {
                    belum: 'badge-light-primary',
                    dijalan: 'badge-light-warning',
                    sudah: 'badge-light-success',
                };
                meta.body.innerHTML = list.map(row => {
                    const name = row.name ? `${row.sku || ''} - ${row.name}`.replace(/^ - /, '').trim() : (row.sku || '-');
                    return `<tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="badge-dot ${colorMap[section] || 'bg-secondary'}"></span>
                                <div class="d-flex flex-column">
                                    <span class="fw-semibold">${name}</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-end cell-number fw-bold">${nf.format(row.qty || 0)}</td>
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
