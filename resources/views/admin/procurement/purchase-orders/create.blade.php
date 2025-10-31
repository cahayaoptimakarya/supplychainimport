@extends('layouts.admin')

@section('title', 'Create Purchase Order')

@section('page_title', 'Create Purchase Order')

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Procurement</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Create PO</span>
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        <div class="card">
            <div class="card-body py-6">
                <form method="POST" action="{{ route('admin.procurement.purchase-orders.store') }}">
                    @csrf
                    <div class="row mb-10">
                        <div class="col-md-4">
                            <label class="form-label required">Supplier</label>
                            <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                                <option value="">- pilih -</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}" @selected(old('supplier_id')==$s->id)>{{ $s->name }}</option>
                                @endforeach
                            </select>
                            @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">Tanggal</label>
                            <input type="date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}" class="form-control @error('order_date') is-invalid @enderror" required />
                            @error('order_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Ref No</label>
                            <input type="text" name="ref_no" value="{{ old('ref_no') }}" class="form-control @error('ref_no') is-invalid @enderror" />
                            @error('ref_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-5 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Lines</h5>
                        <button type="button" class="btn btn-light-primary btn-sm" id="btn_add_line">Tambah Baris</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="lines_table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th width="160">Qty</th>
                                    <th width="140">Koli</th>
                                    <th>Notes</th>
                                    <th width="60"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('admin.procurement.purchase-orders.index') }}" class="btn btn-light me-3">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<template id="tpl_line_row">
    <tr>
        <td>
            <select name="lines[__i__][item_id]" class="form-select" required>
                <option value="">- pilih item -</option>
                @foreach($items as $it)
                    <option value="{{ $it->id }}">{{ $it->sku }} - {{ $it->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" step="0.0001" min="0.0001" name="lines[__i__][qty_ordered]" class="form-control" required />
        </td>
        <td>
            <input type="number" step="0.0001" min="0" name="lines[__i__][koli_ordered]" class="form-control" />
        </td>
        <td>
            <input type="text" name="lines[__i__][notes]" class="form-control" />
        </td>
        <td class="text-end">
            <button type="button" class="btn btn-light-danger btn-sm btn-del-line">Hapus</button>
        </td>
    </tr>
    </template>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const tbody = document.querySelector('#lines_table tbody');
    const tpl = document.getElementById('tpl_line_row').innerHTML;
    let idx = 0;
    function addRow(data){
        let html = tpl.replaceAll('__i__', idx++);
        const tr = document.createElement('tr');
        tr.innerHTML = html;
        tbody.appendChild(tr);
        if (data) {
            tr.querySelector('select').value = data.item_id || '';
            tr.querySelector('input[name$="[qty_ordered]"]').value = data.qty_ordered || '';
            tr.querySelector('input[name$="[notes]"]').value = data.notes || '';
        }
        tr.querySelector('.btn-del-line').addEventListener('click', ()=> tr.remove());
    }
    document.getElementById('btn_add_line').addEventListener('click', ()=> addRow());
    addRow();
});
</script>
@endsection
