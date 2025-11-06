@extends('layouts.admin')

@section('title', 'Edit Receipt (GRN)')

@section('page_title', 'Edit Receipt (GRN)')

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Procurement</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Edit Receipt #{{ $receipt->id }}</span>
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="card">
            <div class="card-body py-6">
                <form method="POST" action="{{ route('admin.procurement.receipts.update', $receipt->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-5 mb-8">
                        <div class="col-md-3">
                            <label class="form-label">Code</label>
                            <input type="text" class="form-control form-control-white border-0" value="{{ $receipt->code }}" disabled readonly />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Shipment</label>
                            <select id="shipment_id" name="shipment_id" class="form-select @error('shipment_id') is-invalid @enderror form-select-solid" required>
                                <option value="">- pilih shipment -</option>
                                @foreach($shipments as $s)
                                    <option value="{{ $s->id }}" @selected(old('shipment_id', $receipt->shipment_id)==$s->id)>#{{ $s->id }} {{ $s->container_no ? '(' . $s->container_no . ')' : '' }}</option>
                                @endforeach
                            </select>
                            @error('shipment_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">Warehouse</label>
                            <select name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror form-select-solid" required>
                                <option value="">- pilih -</option>
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}" @selected(old('warehouse_id', $receipt->warehouse_id)==$w->id)>{{ $w->name }}</option>
                                @endforeach
                            </select>
                            @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">Received At</label>
                            <input type="text" name="received_at" value="{{ old('received_at', optional($receipt->received_at)->format('Y-m-d H:i')) }}" class="form-control js-fp-dt @error('received_at') is-invalid @enderror form-control-solid" required />
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
                                    <th width="140">Koli Received</th>
                                    <th width="60"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="text-muted small mb-4">Saat disimpan, sistem akan me-reset alokasi dan mengalokasikan ulang ke PO secara FIFO per SKU.</div>
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('admin.procurement.receipts.index') }}" class="btn btn-light me-3">Batal</a>
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
            <input type="hidden" name="items[__i__][id]" value="" />
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
            <input type="number" step="0.0001" min="0" name="items[__i__][koli_received]" class="form-control form-control-solid" />
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
                if (window.Swal) { Swal.fire({icon:'error', title:'Qty harus bilangan bulat', text:'Tidak boleh menggunakan desimal.'}); }
                else { alert('Qty harus bilangan bulat.'); }
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
            tr.querySelector('input[type=hidden]').value = data.id || '';
            tr.querySelector('select').value = data.item_id || '';
            const qi = tr.querySelector('input[name$="[qty_received]"]');
            qi.value = (data.qty_received !== undefined && data.qty_received !== null && data.qty_received !== '')
                ? String(parseInt(data.qty_received, 10)) : '';
            const kr = tr.querySelector('input[name$="[koli_received]"]');
            if (kr) kr.value = data.koli_received || '';
        }
        tr.querySelector('.btn-del-item').addEventListener('click', ()=> tr.remove());
    }
    document.getElementById('btn_add_item').addEventListener('click', ()=> addRow());
    @php
        $preset = $receipt->items->map(function($l){
            return [
                'id' => $l->id,
                'item_id' => $l->item_id,
                'qty_received' => (int) $l->qty_received,
                'koli_received' => $l->koli_received,
            ];
        })->values();
    @endphp
    const preset = @json($preset);
    if (preset.length) preset.forEach(addRow); else addRow();
});
</script>
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function(){
        flatpickr('.js-fp-dt', { enableTime: true, dateFormat: 'Y-m-d H:i' });
    });
</script>
@endpush
@endsection
