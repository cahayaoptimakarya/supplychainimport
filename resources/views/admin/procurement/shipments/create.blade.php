@extends('layouts.admin')

@section('title', 'Create Shipment')

@section('page_title', 'Create Shipment')

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Procurement</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Create Shipment</span>
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="card">
            <div class="card-body py-6">
                <form method="POST" action="{{ route('admin.procurement.shipments.store') }}">
                    @csrf
                    <div class="row g-5 mb-8">
                        <div class="col-md-3">
                            <label class="form-label">Code</label>
                            <input type="text" class="form-control form-control-white border-0" value="{{ $code }}" disabled readonly />
                            <input type="hidden" name="code" value="{{ $code }}" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Container No</label>
                            <input type="text" name="container_no" value="{{ old('container_no') }}" class="form-control form-control-solid" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">PL No</label>
                            <input type="text" name="pl_no" value="{{ old('pl_no') }}" class="form-control form-control-solid" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ETD</label>
                            <input type="text" name="etd" value="{{ old('etd') }}" class="form-control js-fp-date form-control-solid" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ETA</label>
                            <input type="text" name="eta" value="{{ old('eta') }}" class="form-control js-fp-date form-control-solid" />
                            </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select form-select-solid">
                                @foreach(['planned','ready_at_port','on_board','arrived','under_bc','released','delivered_to_main_wh','received'] as $st)
                                    <option value="{{ $st }}" @selected(old('status','planned')==$st)>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-5 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Items</h5>
                        <button type="button" class="btn btn-light-primary btn-sm" id="btn_add_item">Tambah Baris</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="items_table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th width="150">Qty Expected</th>
                                    <th width="180">PCS / CNT</th>
                                    <th width="150">Cnt Expected</th>
                                    <th width="200">Description</th>
                                    <th width="60"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('admin.procurement.shipments.index') }}" class="btn btn-light me-3">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<template id="tpl_item_row">
    <tr>
        <td>
            <select name="items[__i__][item_id]" class="form-select form-select-solid" required>
                <option value="">- pilih item -</option>
                @foreach($items as $it)
                    <option value="{{ $it->id }}">{{ $it->sku }} - {{ $it->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" step="1" min="1" name="items[__i__][qty_expected]" class="form-control form-control-solid text-end" required />
        </td>
        <td>
            <input type="text" name="items[__i__][pcs_cnt]" class="form-control form-control-solid" placeholder="mis. 10 pcs / 1 cnt" />
        </td>
        <td>
            <input type="number" step="0.0001" min="0" name="items[__i__][cnt_expected]" class="form-control form-control-solid text-end" />
        </td>
        <td>
            <input type="text" name="items[__i__][description]" class="form-control form-control-solid" placeholder="Deskripsi item (opsional)" />
        </td>
        <td class="text-end">
            <button type="button" class="btn btn-light-danger btn-sm btn-del-item">Hapus</button>
        </td>
    </tr>
    </template>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const tbody = document.querySelector('#items_table tbody');
    const tpl = document.getElementById('tpl_item_row').innerHTML;
    let idx = 0;
    function attachIntegerGuard(input){
        const guard = function(){
            const val = (input.value || '').toString();
            if (val === '') return;
            if (!/^\d+$/.test(val)){
                AppSwal.error('Qty harus bilangan bulat.', { text: 'Tidak boleh menggunakan desimal.' });
                input.value = (val.split(/[\.,]/)[0] || '').replace(/\D/g,'');
                input.focus();
            }
        };
        input.addEventListener('input', guard);
        input.addEventListener('blur', guard);
    }
    function addRow(data){
        let html = tpl.replaceAll('__i__', idx++);
        const tr = document.createElement('tr');
        tr.innerHTML = html;
        tbody.appendChild(tr);
        attachIntegerGuard(tr.querySelector('input[name$="[qty_expected]"]'));
        if (data) {
            tr.querySelector('select').value = data.item_id || '';
            const qi = tr.querySelector('input[name$="[qty_expected]"]');
            qi.value = (data.qty_expected !== undefined && data.qty_expected !== null && data.qty_expected !== '')
                ? String(parseInt(data.qty_expected, 10)) : '';
            const pcsInput = tr.querySelector('input[name$="[pcs_cnt]"]');
            if (pcsInput) pcsInput.value = data.pcs_cnt || '';
            const cntInput = tr.querySelector('input[name$="[cnt_expected]"]');
            if (cntInput) cntInput.value = data.cnt_expected || '';
            const descInput = tr.querySelector('input[name$="[description]"]');
            if (descInput) descInput.value = data.description || '';
        }
        tr.querySelector('.btn-del-item').addEventListener('click', ()=> tr.remove());
    }
    document.getElementById('btn_add_item').addEventListener('click', ()=> addRow());
    addRow();
});
</script>
@push('styles')
<style>
    #items_table {
        table-layout: fixed;
        width: 100%;
    }
    #items_table th:first-child,
    #items_table td:first-child {
        width: 260px;
        min-width: 260px;
    }
    #items_table th:nth-child(2),
    #items_table td:nth-child(2) {
        width: 140px;
        min-width: 140px;
    }
    #items_table th:nth-child(3),
    #items_table td:nth-child(3) {
        width: 180px;
        min-width: 180px;
    }
    #items_table th:nth-child(4),
    #items_table td:nth-child(4) {
        width: 140px;
        min-width: 140px;
    }
    #items_table th:nth-child(5),
    #items_table td:nth-child(5) {
        width: 220px;
        min-width: 220px;
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
@endsection
