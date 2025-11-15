@extends('layouts.admin')

@section('title', 'Create Receipt (GRN)')

@section('page_title', 'Create Receipt (GRN)')

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Procurement</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Create Receipt</span>
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="card">
            <div class="card-body py-6">
                <form method="POST" action="{{ route('admin.procurement.receipts.store') }}">
                    @csrf
                    <div class="row g-5 mb-8">
                        <div class="col-md-3">
                            <label class="form-label">Code</label>
                            <input type="text" class="form-control form-control-white border-0" value="{{ $code }}" disabled readonly />
                            <input type="hidden" name="code" value="{{ $code }}" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Shipment</label>
                            <select id="shipment_id" name="shipment_id" class="form-select @error('shipment_id') is-invalid @enderror form-select-solid" required>
                                <option value="">- pilih shipment -</option>
                                @foreach($shipments as $s)
                                    @php
                                        $itemsJson = $s->items->map(function($it){
                                            return [
                                                'item_id' => $it->item_id,
                                                'qty_expected' => $it->qty_expected,
                                                'cnt_expected' => $it->cnt_expected,
                                                'pcs_cnt' => $it->pcs_cnt,
                                            ];
                                        })->values()->toJson();
                                    @endphp
                                    <option value="{{ $s->id }}" data-items='{{ $itemsJson }}'>#{{ $s->id }} {{ $s->container_no ? '(' . $s->container_no . ')' : '' }}</option>
                                @endforeach
                            </select>
                            @error('shipment_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">Warehouse</label>
                            <select name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror form-select-solid" required>
                                <option value="">- pilih -</option>
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}" @selected(old('warehouse_id')==$w->id)>{{ $w->name }}</option>
                                @endforeach
                            </select>
                            @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">Received At</label>
                            <input type="text" name="received_at" value="{{ old('received_at', now()->format('Y-m-d H:i')) }}" class="form-control js-fp-dt @error('received_at') is-invalid @enderror form-control-solid" required />
                            @error('received_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-5 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Receipt Items</h5>
                        <button type="button" class="btn btn-light-primary btn-sm" id="btn_add_item">Tambah Baris</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="items_table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th width="160">Qty Received</th>
                                    <th width="160">PCS / CNT</th>
                                    <th width="140">Cnt Received</th>
                                    <th width="200">Description</th>
                                    <th width="60"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="text-muted small mb-4">Saat disimpan, sistem akan mengalokasikan qty ke PO terkait secara FIFO per SKU.</div>
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('admin.procurement.receipts.index') }}" class="btn btn-light me-3">Batal</a>
                        <button type="submit" class="btn btn-primary">Post & Allocate</button>
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
                @foreach(\App\Models\Item::orderBy('name')->get() as $it)
                    <option value="{{ $it->id }}">{{ $it->sku }} - {{ $it->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" step="1" min="0" name="items[__i__][qty_received]" class="form-control form-control-solid" required />
        </td>
        <td>
            <input type="text" name="items[__i__][pcs_cnt]" class="form-control form-control-solid" placeholder="mis. 10 pcs / 1 cnt" />
        </td>
        <td>
            <input type="number" step="0.0001" min="0" name="items[__i__][cnt_received]" class="form-control form-control-solid" />
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
        attachIntegerGuard(tr.querySelector('input[name$="[qty_received]"]'));
        if (data) {
            tr.querySelector('select').value = data.item_id || '';
            const qi = tr.querySelector('input[name$="[qty_received]"]');
            qi.value = (data.qty_received !== undefined && data.qty_received !== null && data.qty_received !== '')
                ? String(parseInt(data.qty_received, 10)) : '';
            const pcsInput = tr.querySelector('input[name$="[pcs_cnt]"]');
            if (pcsInput) pcsInput.value = data.pcs_cnt || '';
            const cntInput = tr.querySelector('input[name$="[cnt_received]"]');
            if (cntInput) cntInput.value = data.cnt_received || '';
            const descInput = tr.querySelector('input[name$="[description]"]');
            if (descInput) descInput.value = data.description || '';
        }
        tr.querySelector('.btn-del-item').addEventListener('click', ()=> tr.remove());
    }
    document.getElementById('btn_add_item').addEventListener('click', ()=> addRow());
    function populateFromShipment(){
        tbody.innerHTML=''; idx=0;
        const sel = document.getElementById('shipment_id');
        const opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.dataset.items) { addRow(); return; }
        try {
            const items = JSON.parse(opt.dataset.items);
            if (Array.isArray(items) && items.length) {
                items.forEach(it => addRow({
                    item_id: it.item_id,
                    qty_received: (it.qty_expected ?? 0),
                    cnt_received: it.cnt_expected || 0,
                    pcs_cnt: it.pcs_cnt || ''
                }));
            } else { addRow(); }
        } catch(e) { addRow(); }
    }
    document.getElementById('shipment_id').addEventListener('change', populateFromShipment);
    populateFromShipment();
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
    #items_table td:nth-child(2),
    #items_table th:nth-child(3),
    #items_table td:nth-child(3),
    #items_table th:nth-child(4),
    #items_table td:nth-child(4) {
        width: 150px;
        min-width: 150px;
    }
</style>
@endpush
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function(){
        flatpickr('.js-fp-dt', { enableTime: true, dateFormat: 'Y-m-d H:i' });
    });
</script>
@endpush
@endsection
