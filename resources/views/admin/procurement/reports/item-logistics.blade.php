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
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body d-flex flex-column flex-md-row align-items-md-center gap-4">
                <div>
                    <h5 class="fw-semibold mb-1">Cari SKU / Item</h5>
                    <div class="text-muted fs-8">Pencarian ini berlaku untuk seluruh status di bawah.</div>
                </div>
                <div class="flex-grow-1 w-100" style="max-width: 360px;">
                    <div class="input-group search-input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="ki-outline ki-magnifier fs-2 text-gray-500"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0" id="item_search" placeholder="Masukkan SKU atau nama item">
                    </div>
                </div>
            </div>
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
$iconPlanned = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M3 4h18"></path>
    <path d="M8 2v4M16 2v4"></path>
    <rect x="3" y="5" width="18" height="17" rx="2"></rect>
    <path d="M8 14h3M8 18h3M14 14h3"></path>
</svg>
SVG;
$iconPort = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M3 21h18"></path>
    <path d="M5 21v-6l7-3 7 3v6"></path>
    <path d="M9 21v-4a3 3 0 016 0v4"></path>
    <path d="M12 3v6"></path>
    <path d="M9 6h6"></path>
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
$iconDijalanLaut = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M3 19c1.5 1 2.5 1 4 0s2.5-1 4 0 2.5 1 4 0 2.5-1 4 0"></path>
    <path d="M4 15l4-8 5 5 4-4 3 7"></path>
</svg>
SVG;
$iconDijalanDarat = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M3 7h11v9H3z"></path>
    <path d="M14 10h4l3 3v3h-7z"></path>
    <circle cx="7" cy="18" r="2"></circle>
    <circle cx="17" cy="18" r="2"></circle>
    <path d="M5 14h3"></path>
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
                    'key' => 'planned',
                    'title' => 'Shipment Planned',
                    'subtitle' => 'Qty di shipment berstatus planned',
                    'badge' => 'badge-light-info',
                    'icon_bg' => 'bg-light-info',
                    'icon' => $iconPlanned,
                ],
                [
                    'key' => 'dijalan_laut',
                    'title' => 'Di Kapal / Perahu',
                    'subtitle' => 'Qty sedang menyeberang laut',
                    'badge' => 'badge-light-warning',
                    'icon_bg' => 'bg-light-warning',
                    'icon' => $iconDijalanLaut,
                ],
                [
                    'key' => 'pelabuhan',
                    'title' => 'Di Pelabuhan',
                    'subtitle' => 'Qty menunggu/di proses di pelabuhan',
                    'badge' => 'badge-light-secondary',
                    'icon_bg' => 'bg-light-secondary',
                    'icon' => $iconPort,
                ],
                [
                    'key' => 'dijalan_darat',
                    'title' => 'Menuju Gudang',
                    'subtitle' => 'Qty dikirim dari pelabuhan ke WH',
                    'badge' => 'badge-light-warning',
                    'icon_bg' => 'bg-light-warning',
                    'icon' => $iconDijalanDarat,
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
                        <div class="status-card__table-wrapper">
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
                        </div>
                        <div class="status-card__pagination d-flex flex-wrap flex-sm-nowrap gap-3 align-items-center justify-content-between mt-4">
                            <span class="text-muted fs-8" id="info_{{ $card['key'] }}">Menunggu data...</span>
                            <div class="btn-group" role="group" aria-label="Navigasi halaman">
                                <button class="btn btn-light btn-sm px-3 status-card__page-btn" data-pagination="prev" data-card="{{ $card['key'] }}" type="button" disabled>
                                    <span class="status-card__page-icon">&larr;</span>
                                </button>
                                <button class="btn btn-light btn-sm px-3 status-card__page-btn" data-pagination="next" data-card="{{ $card['key'] }}" type="button" disabled>
                                    <span class="status-card__page-icon">&rarr;</span>
                                </button>
                            </div>
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
    .status-card__table-wrapper {
        min-height: 320px;
        height: 320px;
        display: flex;
        align-items: stretch;
    }
    .status-card__table-wrapper .table-responsive {
        width: 100%;
        max-height: 320px;
        overflow-y: auto;
    }
    .status-card__table .badge-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }
    .status-card__pagination {
        border-top: 1px dashed #eff2f5;
        padding-top: 1rem;
        min-height: 60px;
    }
    .status-card__page-btn {
        min-width: 44px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px !important;
        border: 1px solid #0f172a !important;
        color: #fff !important;
        background-color: #0f172a !important;
        box-shadow: 0px 4px 8px rgba(15, 23, 42, 0.35);
        transition: all .2s ease;
    }
    .status-card__page-btn:hover:not(:disabled) {
        color: #fff !important;
        border-color: #1d4ed8 !important;
        background-color: #1d4ed8 !important;
        box-shadow: 0px 6px 14px rgba(37, 99, 235, 0.45);
    }
    .status-card__page-btn:disabled {
        opacity: .4;
        box-shadow: none;
    }
    .status-card__page-icon {
        color: inherit;
        font-weight: 700;
        font-size: 1rem;
        line-height: 1;
    }
    .status-card__meta-icon {
        background: #eef2ff;
        border-radius: 12px;
    }
    .status-card--belum .status-card__hero {
        background: linear-gradient(135deg, #5B61FF, #89A8FF);
    }
    .status-card--planned .status-card__hero {
        background: linear-gradient(135deg, #80d0c7, #13547a);
    }
    .status-card--pelabuhan .status-card__hero {
        background: linear-gradient(135deg, #a18cd1, #fbc2eb);
    }
    .status-card--dijalan_laut .status-card__hero,
    .status-card--dijalan_darat .status-card__hero {
        background: linear-gradient(135deg, #FFB347, #FFCC70);
    }
    .status-card--sudah .status-card__hero {
        background: linear-gradient(135deg, #38D39F, #55E7C4);
    }
    .status-card--belum .status-card__icon {
        background: rgba(255,255,255,.3);
        color: #1d3cff;
    }
    .status-card--planned .status-card__icon {
        background: rgba(255,255,255,.3);
        color: #0f766e;
    }
    .status-card--pelabuhan .status-card__icon {
        background: rgba(255,255,255,.3);
        color: #7c3aed;
    }
    .status-card--dijalan_laut .status-card__icon,
    .status-card--dijalan_darat .status-card__icon {
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
    const colorMap = {
        belum: 'badge-light-primary',
        planned: 'badge-light-info',
        pelabuhan: 'badge-light-secondary',
        dijalan_laut: 'badge-light-warning',
        dijalan_darat: 'badge-light-warning',
        sudah: 'badge-light-success',
    };
    const state = {
        data: {},
        pages: {},
        pageSize: 5,
        searchQuery: '',
    };
    let dataReady = false;
    const sectionMap = {
        belum: {
            key: 'belum_pengiriman',
            body: document.getElementById('body_belum'),
            count: document.getElementById('count_belum'),
            empty: 'Tidak ada item menunggu pengiriman',
            info: document.getElementById('info_belum'),
            prev: document.querySelector('[data-pagination="prev"][data-card="belum"]'),
            next: document.querySelector('[data-pagination="next"][data-card="belum"]'),
        },
        planned: {
            key: 'planned',
            body: document.getElementById('body_planned'),
            count: document.getElementById('count_planned'),
            empty: 'Belum ada shipment planned',
            info: document.getElementById('info_planned'),
            prev: document.querySelector('[data-pagination="prev"][data-card="planned"]'),
            next: document.querySelector('[data-pagination="next"][data-card="planned"]'),
        },
        pelabuhan: {
            key: 'di_pelabuhan',
            body: document.getElementById('body_pelabuhan'),
            count: document.getElementById('count_pelabuhan'),
            empty: 'Tidak ada item di pelabuhan',
            info: document.getElementById('info_pelabuhan'),
            prev: document.querySelector('[data-pagination="prev"][data-card="pelabuhan"]'),
            next: document.querySelector('[data-pagination="next"][data-card="pelabuhan"]'),
        },
        dijalan_laut: {
            key: 'dalam_perjalanan_laut',
            body: document.getElementById('body_dijalan_laut'),
            count: document.getElementById('count_dijalan_laut'),
            empty: 'Tidak ada item di kapal/perahu',
            info: document.getElementById('info_dijalan_laut'),
            prev: document.querySelector('[data-pagination="prev"][data-card="dijalan_laut"]'),
            next: document.querySelector('[data-pagination="next"][data-card="dijalan_laut"]'),
        },
        dijalan_darat: {
            key: 'dalam_perjalanan_darat',
            body: document.getElementById('body_dijalan_darat'),
            count: document.getElementById('count_dijalan_darat'),
            empty: 'Tidak ada item menuju warehouse',
            info: document.getElementById('info_dijalan_darat'),
            prev: document.querySelector('[data-pagination="prev"][data-card="dijalan_darat"]'),
            next: document.querySelector('[data-pagination="next"][data-card="dijalan_darat"]'),
        },
        sudah: {
            key: 'sudah_diterima',
            body: document.getElementById('body_sudah'),
            count: document.getElementById('count_sudah'),
            empty: 'Belum ada item diterima',
            info: document.getElementById('info_sudah'),
            prev: document.querySelector('[data-pagination="prev"][data-card="sudah"]'),
            next: document.querySelector('[data-pagination="next"][data-card="sudah"]'),
        },
    };

    let loading = false;

    const searchInput = document.getElementById('item_search');
    const reloadButton = document.getElementById('btn_reload');

    function filterList(list) {
        if (!Array.isArray(list)) {
            return [];
        }
        if (!state.searchQuery) {
            return list;
        }
        return list.filter(row => {
            const sku = (row.sku || '').toString().toLowerCase();
            const name = (row.name || '').toString().toLowerCase();
            return sku.includes(state.searchQuery) || name.includes(state.searchQuery);
        });
    }

    function setInfo(meta, message) {
        if (meta.info) {
            meta.info.textContent = message;
        }
    }

    function disablePagination(meta) {
        if (meta.prev) meta.prev.disabled = true;
        if (meta.next) meta.next.disabled = true;
    }

    function updatePagination(meta, currentPage, totalPages) {
        if (meta.prev) meta.prev.disabled = currentPage <= 1;
        if (meta.next) meta.next.disabled = currentPage >= totalPages;
    }

    function renderSection(section, meta) {
        if (!meta) return;
        const rawList = state.data[section] || [];
        const filteredList = filterList(rawList);
        const hasSearch = Boolean(state.searchQuery);
        const totalFiltered = filteredList.length;
        const totalPages = totalFiltered ? Math.ceil(totalFiltered / state.pageSize) : 1;

        meta.count.textContent = hasSearch && rawList.length ? `${totalFiltered} / ${rawList.length}` : rawList.length;

        if (!rawList.length) {
            meta.body.innerHTML = `<tr><td colspan="2" class="text-center text-muted py-5">${meta.empty}</td></tr>`;
            setInfo(meta, hasSearch ? 'Tidak ada data' : 'Belum ada data');
            disablePagination(meta);
            return;
        }

        if (!totalFiltered) {
            meta.body.innerHTML = `<tr><td colspan="2" class="text-center text-muted py-5">Tidak ada item sesuai pencarian.</td></tr>`;
            setInfo(meta, 'Tidak ada hasil untuk pencarian ini');
            disablePagination(meta);
            return;
        }

        let currentPage = state.pages[section] || 1;
        if (currentPage > totalPages) {
            currentPage = totalPages;
        }
        if (currentPage < 1) {
            currentPage = 1;
        }
        state.pages[section] = currentPage;

        const start = (currentPage - 1) * state.pageSize;
        const pageRows = filteredList.slice(start, start + state.pageSize);
        meta.body.innerHTML = pageRows.map(row => {
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

        const end = Math.min(start + state.pageSize, totalFiltered);
        setInfo(meta, `Menampilkan ${start + 1}-${end} dari ${totalFiltered} data`);
        updatePagination(meta, currentPage, totalPages);
    }

    function renderAllSections() {
        if (!dataReady) return;
        Object.entries(sectionMap).forEach(([section, meta]) => renderSection(section, meta));
    }

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
                const list = Array.isArray(payload[meta.key]) ? payload[meta.key] : [];
                state.data[section] = list;
                state.pages[section] = 1;
            });
            dataReady = true;
            renderAllSections();
        } catch (err) {
            console.error(err);
            dataReady = false;
            Object.values(sectionMap).forEach(meta => {
                meta.body.innerHTML = `<tr><td colspan="2" class="text-center text-danger py-5">Gagal memuat data</td></tr>`;
                meta.count.textContent = '-';
                setInfo(meta, 'Tidak dapat memuat data');
                disablePagination(meta);
            });
        } finally {
            setLoadingState(false);
            loading = false;
        }
    }

    function setLoadingState(isLoading) {
        if (!reloadButton) return;
        reloadButton.disabled = isLoading;
        reloadButton.innerHTML = isLoading ? '<span class="spinner-border spinner-border-sm me-2"></span>Memuat...' : 'Muat Ulang';
    }

    function handleSearchInput(value) {
        state.searchQuery = value.trim().toLowerCase();
        Object.keys(sectionMap).forEach(section => {
            state.pages[section] = 1;
        });
        renderAllSections();
    }

    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', event => {
            const value = event.target.value || '';
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => handleSearchInput(value), 200);
        });
    }

    document.querySelectorAll('[data-pagination]').forEach(btn => {
        btn.addEventListener('click', function () {
            const section = this.dataset.card;
            const action = this.dataset.pagination;
            if (!section || !action || this.disabled || !dataReady) {
                return;
            }
            const meta = sectionMap[section];
            if (!meta) return;
            const filtered = filterList(state.data[section] || []);
            if (!filtered.length) {
                disablePagination(meta);
                return;
            }
            const totalPages = Math.max(1, Math.ceil(filtered.length / state.pageSize));
            const currentPage = state.pages[section] || 1;
            if (action === 'prev' && currentPage > 1) {
                state.pages[section] = currentPage - 1;
                renderSection(section, meta);
            } else if (action === 'next' && currentPage < totalPages) {
                state.pages[section] = currentPage + 1;
                renderSection(section, meta);
            }
        });
    });

    if (reloadButton) {
        reloadButton.addEventListener('click', loadData);
    }

    loadData();
});
</script>
@endpush
