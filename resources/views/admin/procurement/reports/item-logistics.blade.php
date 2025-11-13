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
$iconBelum = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <rect x="4" y="5" width="16" height="16" rx="3"></rect>
    <path d="M8 3v4M16 3v4"></path>
    <path d="M4 10h16"></path>
    <path d="M10 15h4M8 19h8"></path>
</svg>
SVG;
$iconDijalan = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M3 7h11v9H3z"></path>
    <path d="M14 10h4l3 3v3h-7z"></path>
    <circle cx="7" cy="18" r="2"></circle>
    <circle cx="17" cy="18" r="2"></circle>
</svg>
SVG;
$iconSudah = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M5 13l4 4L19 7"></path>
    <path d="M4 12V6l8-4 8 4v6"></path>
    <path d="M10 22v-5a2 2 0 012-2h0a2 2 0 012 2v5"></path>
</svg>
SVG;
            $cards = [
                [
                    'key' => 'belum',
                    'title' => 'Belum Diproses Pengiriman',
                    'subtitle' => 'Qty dari PO yang belum dijadwalkan/berangkat',
                    'badge' => 'badge-light-primary',
                    'icon_bg' => 'bg-light-primary',
                    'icon' => $iconBelum,
                ],
                [
                    'key' => 'dijalan',
                    'title' => 'Sedang Dijalan',
                    'subtitle' => 'Qty di shipment yang sudah berangkat / transit',
                    'badge' => 'badge-light-warning',
                    'icon_bg' => 'bg-light-warning',
                    'icon' => $iconDijalan,
                ],
                [
                    'key' => 'sudah',
                    'title' => 'Sudah Diterima',
                    'subtitle' => 'Qty yang sudah mendarat di gudang',
                    'badge' => 'badge-light-success',
                    'icon_bg' => 'bg-light-success',
                    'icon' => $iconSudah,
                ],
            ];
        @endphp
        <div class="row g-5">
            @foreach($cards as $card)
            <div class="col-xl-4">
                <div class="status-card status-card--{{ $card['key'] }} h-100 border-0 shadow-sm">
                    <div class="status-card__hero d-flex justify-content-between align-items-start">
                        <div>
                            <span class="status-card__eyebrow text-white-75 text-uppercase fw-semibold fs-8">Progress</span>
                            <h3 class="text-white fw-bold fs-3 mb-1">{{ $card['title'] }}</h3>
                            <p class="text-white-75 mb-0 fs-7">{{ $card['subtitle'] }}</p>
                        </div>
                        <div class="status-card__icon {{ $card['icon_bg'] }}">
                            {!! $card['icon'] !!}
                        </div>
                    </div>
                    <div class="status-card__counter px-6 py-4 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-gray-500 fs-8 text-uppercase fw-semibold">Total Item</span>
                            <div class="text-gray-900 fw-bold fs-2" id="count_{{ $card['key'] }}">0</div>
                        </div>
                        <div class="status-card__chip {{ $card['badge'] }}">
                            <span class="fw-semibold text-gray-700 fs-8">Terupdate otomatis</span>
                        </div>
                    </div>
                    <div class="status-card__list px-6 pb-5">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 status-card__table">
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
                        <div class="status-card__meta d-flex align-items-center mt-4">
                            <div class="symbol symbol-35px me-3">
                                <div class="symbol-label status-card__meta-icon">
                                    <i class="ki-outline ki-chart fs-3 text-primary"></i>
                                </div>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="text-gray-700 fw-bold">Detail per Item</span>
                                <span class="text-muted fs-8">Daftar otomatis dari data realtime</span>
                            </div>
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
    .status-card {
        border-radius: 1.5rem;
        overflow: hidden;
        background: #fff;
        position: relative;
    }
    .status-card__hero {
        padding: 1.75rem;
        background: linear-gradient(135deg, #EEF2FF, #D1DCFF);
    }
    .status-card__icon {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        background: rgba(255,255,255,.25);
        color: #1f2937;
    }
    .status-card__icon svg {
        color: inherit;
        width: 28px;
        height: 28px;
    }
    .status-card__counter {
        border-bottom: 1px dashed #edf1f5;
    }
    .status-card__chip {
        border-radius: 999px;
        padding: .3rem .9rem;
    }
    .status-card__list {
        background: #fff;
    }
    .status-card__table thead tr th {
        border-bottom-width: 0;
    }
    .status-card__table tbody tr td {
        border-bottom: 0;
        padding: .65rem 0;
    }
    .status-card__table tbody tr + tr td {
        border-top: 1px dashed #eff2f5;
    }
    .status-card__table tbody tr td:first-child span {
        color: #152036;
    }
    .status-card__table .badge-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }
    .status-card__meta-icon {
        background: #eef2ff;
        border-radius: 12px;
    }
    .status-card--belum .status-card__hero {
        background: linear-gradient(135deg, #5B61FF, #89A8FF);
    }
    .status-card--dijalan .status-card__hero {
        background: linear-gradient(135deg, #FFB347, #FFCC70);
    }
    .status-card--sudah .status-card__hero {
        background: linear-gradient(135deg, #38D39F, #55E7C4);
    }
    .status-card--belum .status-card__icon {
        background: rgba(255,255,255,.3);
        color: #1d3cff;
    }
    .status-card--dijalan .status-card__icon {
        background: rgba(255,255,255,.3);
        color: #c2410c;
    }
    .status-card--sudah .status-card__icon {
        background: rgba(255,255,255,.3);
        color: #0f9156;
    }
    .status-card__hero .status-card__eyebrow {
        letter-spacing: .2em;
    }
    @media (max-width: 1200px) {
        .status-card__hero, .status-card__list, .status-card__counter { padding-left: 1.5rem; padding-right: 1.5rem; }
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
