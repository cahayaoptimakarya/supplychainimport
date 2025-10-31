@extends('layouts.admin')

@section('title', 'Edit Shipment')

@section('page_title', 'Edit Shipment')

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Procurement</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Edit Shipment #{{ $shipment->id }}</span>
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="card">
            <div class="card-body py-6">
                <form method="POST" action="{{ route('admin.procurement.shipments.update', $shipment->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-5 mb-8">
                        <div class="col-md-4">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">- pilih -</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}" @selected(old('supplier_id', $shipment->supplier_id)==$s->id)>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Container No</label>
                            <input type="text" name="container_no" value="{{ old('container_no', $shipment->container_no) }}" class="form-control" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">PL No</label>
                            <input type="text" name="pl_no" value="{{ old('pl_no', $shipment->pl_no) }}" class="form-control" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ETD</label>
                            <input type="date" name="etd" value="{{ old('etd', optional($shipment->etd)->format('Y-m-d')) }}" class="form-control" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ETA</label>
                            <input type="date" name="eta" value="{{ old('eta', optional($shipment->eta)->format('Y-m-d')) }}" class="form-control" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                @foreach(['planned','ready_at_port','on_board','arrived','under_bc','released','delivered_to_main_wh','received'] as $st)
                                    <option value="{{ $st }}" @selected(old('status', $shipment->status)==$st)>{{ $st }}</option>
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
                                    <th width="160">Qty Expected</th>
                                    <th width="140">Koli Expected</th>
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
            <input type="hidden" name="items[__i__][id]" value="" />
            <select name="items[__i__][item_id]" class="form-select" required>
                <option value="">- pilih item -</option>
                @foreach($items as $it)
                    <option value="{{ $it->id }}">{{ $it->sku }} - {{ $it->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" step="0.0001" min="0.0001" name="items[__i__][qty_expected]" class="form-control" required />
        </td>
        <td>
            <input type="number" step="0.0001" min="0" name="items[__i__][koli_expected]" class="form-control" />
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
    function addRow(data){
        let html = tpl.replaceAll('__i__', idx++);
        const tr = document.createElement('tr');
        tr.innerHTML = html;
        tbody.appendChild(tr);
        if (data) {
            tr.querySelector('input[type=hidden]').value = data.id || '';
            tr.querySelector('select').value = data.item_id || '';
            tr.querySelector('input[name$="[qty_expected]"]').value = data.qty_expected || '';
            const ke = tr.querySelector('input[name$="[koli_expected]"]');
            if (ke) ke.value = data.koli_expected || '';
        }
        tr.querySelector('.btn-del-item').addEventListener('click', ()=> tr.remove());
    }
    document.getElementById('btn_add_item').addEventListener('click', ()=> addRow());
    const preset = @json($shipment->items->map(fn($l)=> ['id'=>$l->id,'item_id'=>$l->item_id,'qty_expected'=>$l->qty_expected]));
    if (preset.length) preset.forEach(addRow); else addRow();
});
</script>
@endsection
