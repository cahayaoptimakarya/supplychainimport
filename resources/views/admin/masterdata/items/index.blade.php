@extends('layouts.admin')

@section('title', 'Masterdata - Items')

@section('page_title', 'Items')

@section('page_actions')
@php use App\Support\Permission as Perm; @endphp
@php
    $canCreateItem = Perm::can(auth()->user(), 'admin.masterdata.items.index', 'create');
    $canCreateCat  = Perm::can(auth()->user(), 'admin.masterdata.categories.index', 'create');
@endphp
@if($canCreateItem)
<a id="btn_create_item" href="{{ route('admin.masterdata.items.create') }}" class="btn btn-primary">Create Item</a>
@endif
@if($canCreateCat)
<button id="btn_create_cat" type="button" class="btn btn-light-primary ms-2 d-none">Create Kategori</button>
@endif
<button id="btn_import_items" type="button" class="btn btn-success ms-2">Import CSV</button>
<button class="btn btn-light ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#import_guide" aria-expanded="false" aria-controls="import_guide">Panduan Kolom</button>
@endsection

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Masterdata</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Items</span>
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid" id="kt_content_container">
        @if(session('success'))
            <div class="alert alert-success my-5">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-header border-0 pt-6">
                <ul class="nav nav-tabs nav-line-tabs mb-0 fs-6">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab_items">Items</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab_categories">Kategori Item</a>
                    </li>
                </ul>
            </div>
            <div class="card-body py-6">
                <div class="collapse mb-6" id="import_guide">
                    <div class="card card-bordered p-5 bg-light">
                        <div class="fs-6 text-gray-800">
                            <strong>Panduan Import Items (CSV)</strong>
                            <div class="mt-3">Gunakan file CSV dengan header berikut (urutan bebas):</div>
                            <ul class="mt-2 mb-0">
                                <li><code>sku</code> — wajib, unik</li>
                                <li><code>name</code> — wajib</li>
                                <li><code>cnt</code> — wajib (contoh: koli, pcs, dsb.)</li>
                                <li><code>category</code> — wajib, isi nama kategori sesuai Masterdata Kategori</li>
                                <li><code>uom</code> — wajib, isi nama UOM sesuai Masterdata UOM</li>
                                <li><code>description</code> — opsional</li>
                            </ul>
                            <div class="mt-3">Header alternatif yang didukung:</div>
                            <ul class="mt-2 mb-0">
                                <li>category: <code>category</code> | <code>kategori</code> | <code>category_name</code></li>
                                <li>uom: <code>uom</code> | <code>uom_name</code></li>
                                <li>cnt: <code>cnt</code> | <code>koli</code></li>
                                <li>name: <code>name</code> | <code>nama</code></li>
                                <li>description: <code>description</code> | <code>deskripsi</code> | <code>keterangan</code></li>
                            </ul>
                            <div class="mt-3">Baris dengan kategori/UOM tidak ditemukan atau SKU duplikat akan dilewati dan dilaporkan.</div>
                        </div>
                    </div>
                </div>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab_items" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="items_table">
                                <thead>
                                    <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                        <th>ID</th>
                                        <th>Nama</th>
                                        <th>SKU</th>
                                        <th>Kategori</th>
                                        <th>UOM</th>
                                        <th>CNT</th>
                                        <th>Deskripsi</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab_categories" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="item_categories_table">
                                <thead>
                                    <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                        <th>ID</th>
                                        <th>Nama</th>
                                        <th>Slug</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
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
<link href="{{ asset('metronic/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
<script src="{{ asset('metronic/plugins/custom/datatables/datatables.bundle.js') }}"></script>
<script>
    const csrfToken = '{{ csrf_token() }}';
    // Items endpoints
    const itemsDataUrl   = '{{ route('admin.masterdata.items.data') }}';
    const itemsEditTpl   = '{{ route('admin.masterdata.items.edit', ':id') }}';
    const itemsDelTpl    = '{{ route('admin.masterdata.items.destroy', ':id') }}';
    const canItemUpdate  = {{ \App\Support\Permission::can(auth()->user(), 'admin.masterdata.items.index', 'update') ? 'true' : 'false' }};
    const canItemDelete  = {{ \App\Support\Permission::can(auth()->user(), 'admin.masterdata.items.index', 'delete') ? 'true' : 'false' }};
    // Categories endpoints
    const catsDataUrl    = '{{ route('admin.masterdata.categories.data') }}';
    const catsEditTpl    = '{{ route('admin.masterdata.categories.edit', ':id') }}';
    const catsDelTpl     = '{{ route('admin.masterdata.categories.destroy', ':id') }}';
    const canCatUpdate   = {{ \App\Support\Permission::can(auth()->user(), 'admin.masterdata.categories.index', 'update') ? 'true' : 'false' }};
    const canCatDelete   = {{ \App\Support\Permission::can(auth()->user(), 'admin.masterdata.categories.index', 'delete') ? 'true' : 'false' }};

    document.addEventListener('DOMContentLoaded', function() {
        const table = $('#items_table').DataTable({
            processing: true,
            serverSide: false,
            dom: 'lrtip',
            ajax: {
                url: itemsDataUrl,
                dataSrc: 'data',
                error: function(xhr){
                    console.error('Items AJAX error:', xhr.responseText);
                    alert('Gagal memuat data item');
                }
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'name', name: 'name' },
                { data: 'sku', name: 'sku' },
                { data: 'category', name: 'category', defaultContent: '-' },
                { data: 'uom', name: 'uom', defaultContent: '-' },
                { data: 'cnt', name: 'cnt', defaultContent: '-' },
                { data: 'description', name: 'description', defaultContent: '-' },
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    className: 'text-end',
                    render: function (data, type, row) {
                        const editUrl = itemsEditTpl.replace(':id', data);
                        const delUrl  = itemsDelTpl.replace(':id', data);
                        let html = '';
                        if (canItemUpdate) html += `<a href=\"${editUrl}\" class=\"btn btn-light-primary btn-sm me-2\">Edit</a>`;
                        if (canItemDelete) html += `<button type=\"button\" data-id=\"${data}\" data-url=\"${delUrl}\" class=\"btn btn-light-danger btn-sm btn-delete\">Hapus</button>`;
                        return html || '-';
                    }
                }
            ]
        });

        // Categories table
        const catTable = $('#item_categories_table').DataTable({
            processing: true,
            serverSide: false,
            dom: 'lrtip',
            ajax: {
                url: catsDataUrl,
                dataSrc: 'data',
                error: function(xhr){
                    console.error('Categories AJAX error:', xhr.responseText);
                    alert('Gagal memuat data kategori');
                }
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'name', name: 'name' },
                { data: 'slug', name: 'slug' },
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    className: 'text-end',
                    render: function (data, type, row) {
                        const editUrl = catsEditTpl.replace(':id', data);
                        const delUrl  = catsDelTpl.replace(':id', data);
                        let html = '';
                        // Edit opens modal
                        if (canCatUpdate) html += `<button type=\"button\" data-id=\"${data}\" data-name=\"${row.name || ''}\" class=\"btn btn-light-primary btn-sm me-2 btn-edit-cat\">Edit</button>`;
                        if (canCatDelete) html += `<button type=\"button\" data-id=\"${data}\" data-url=\"${delUrl}\" class=\"btn btn-light-danger btn-sm btn-delete-cat\">Hapus</button>`;
                        return html || '-';
                    }
                }
            ]
        });

        // Delete handlers
        $('#items_table').on('click', '.btn-delete', function() {
            const url = this.getAttribute('data-url');
            if (!confirm('Yakin ingin menghapus item ini?')) return;
            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: new URLSearchParams({ _method: 'DELETE' })
            }).then(res => {
                if (res.ok) table.ajax.reload(null, false); else alert('Gagal menghapus item');
            }).catch(() => alert('Gagal menghapus item'));
        });

        $('#item_categories_table').on('click', '.btn-delete-cat', function() {
            const url = this.getAttribute('data-url');
            if (!confirm('Yakin ingin menghapus kategori ini?')) return;
            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: new URLSearchParams({ _method: 'DELETE' })
            }).then(res => {
                if (res.ok) catTable.ajax.reload(null, false); else alert('Gagal menghapus kategori');
            }).catch(() => alert('Gagal menghapus kategori'));
        });

        // Global search (optional, if present)
        const globalInput = document.getElementById('global_search');
        if (globalInput) {
            globalInput.addEventListener('input', function() {
                const val = this.value;
                if (document.querySelector('#tab_items').classList.contains('active')) {
                    table.search(val).draw();
                } else {
                    catTable.search(val).draw();
                }
            });
        }

        // Toggle Create buttons per tab
        const btnCreateItem = document.getElementById('btn_create_item');
        const btnCreateCat  = document.getElementById('btn_create_cat');
        function toggleButtons(active) {
            if (btnCreateItem) btnCreateItem.classList.toggle('d-none', active !== 'items');
            if (btnCreateCat)  btnCreateCat.classList.toggle('d-none', active !== 'cats');
        }
        toggleButtons('items');
        const tabElList = [].slice.call(document.querySelectorAll('a[data-bs-toggle="tab"]'))
        tabElList.forEach(function (tabEl) {
            tabEl.addEventListener('shown.bs.tab', function (event) {
                const target = event.target.getAttribute('href');
                if (target === '#tab_items') { table.columns.adjust(); toggleButtons('items'); }
                if (target === '#tab_categories') { catTable.columns.adjust(); toggleButtons('cats'); }
                if (history && history.replaceState) {
                    history.replaceState(null, '', target);
                } else {
                    location.hash = target;
                }
            });
        });

        // Activate tab by hash on load
        if (location.hash === '#tab_categories') {
            const link = document.querySelector('a[href="#tab_categories"]');
            if (link) new bootstrap.Tab(link).show();
        }

        // Modal for Category create/edit
        const modalEl = document.getElementById('categoryModal');
        const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
        const form = document.getElementById('category_form');
        const nameInput = document.getElementById('category_name');
        const titleEl = document.getElementById('categoryModalLabel');
        const submitBtn = document.getElementById('category_submit');
        const idInput = document.getElementById('category_id');
        const errorEl = document.getElementById('category_error');

        function openCatModal(mode, data) {
            if (!modal) return;
            errorEl.classList.add('d-none');
            errorEl.textContent = '';
            if (mode === 'create') {
                titleEl.textContent = 'Tambah Kategori';
                form.setAttribute('data-mode', 'create');
                idInput.value = '';
                nameInput.value = '';
            } else {
                titleEl.textContent = 'Edit Kategori';
                form.setAttribute('data-mode', 'edit');
                idInput.value = data.id;
                nameInput.value = data.name || '';
            }
            modal.show();
        }

        const createBtn = document.getElementById('btn_create_cat');
        if (createBtn) {
            createBtn.addEventListener('click', function(){ openCatModal('create'); });
        }

        $('#item_categories_table').on('click', '.btn-edit-cat', function(){
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            openCatModal('edit', { id, name });
        });

        if (form) {
            form.addEventListener('submit', function(e){
                e.preventDefault();
                errorEl.classList.add('d-none');
                errorEl.textContent = '';
                const mode = form.getAttribute('data-mode');
                let url = '';
                const payload = new URLSearchParams();
                payload.append('name', nameInput.value.trim());
                if (mode === 'edit') {
                    url = catsEditTpl.replace(':id', idInput.value);
                    payload.append('_method', 'PUT');
                } else {
                    url = '{{ route('admin.masterdata.categories.store') }}';
                }
                submitBtn.disabled = true;
                fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: payload
                }).then(async (res) => {
                    submitBtn.disabled = false;
                    if (res.ok) {
                        modal.hide();
                        catTable.ajax.reload(null, false);
                    } else if (res.status === 422) {
                        const data = await res.json().catch(() => ({}));
                        const msg = data?.message || 'Validasi gagal';
                        errorEl.textContent = msg;
                        errorEl.classList.remove('d-none');
                    } else {
                        alert('Gagal menyimpan kategori');
                    }
                }).catch(() => { submitBtn.disabled = false; alert('Gagal menyimpan kategori'); });
            });
        }
    });
</script>
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="categoryModalLabel">Kategori</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="category_error" class="alert alert-danger d-none"></div>
        <form id="category_form" class="form" data-mode="create">
            <input type="hidden" id="category_id" />
            <div class="mb-10">
                <label class="form-label required">Nama</label>
                <input type="text" id="category_name" class="form-control" placeholder="Nama kategori" required />
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" form="category_form" id="category_submit" class="btn btn-primary">Simpan</button>
      </div>
    </div>
  </div>
</div>
@endpush

@push('scripts')
<script>
    const importUrl = '{{ route('admin.masterdata.items.import') }}';
    document.addEventListener('DOMContentLoaded', function() {
        const importModalEl = document.getElementById('importItemsModal');
        const importModal = importModalEl ? new bootstrap.Modal(importModalEl) : null;
        const importBtn = document.getElementById('btn_import_items');
        const importForm = document.getElementById('import_form');
        const importFile = document.getElementById('import_file');
        const importError = document.getElementById('import_error');
        const importSubmit = document.getElementById('import_submit');
        const importResult = document.getElementById('import_result');

        if (importBtn && importModal) {
            importBtn.addEventListener('click', function(){
                importError.classList.add('d-none');
                importError.textContent = '';
                importResult.classList.add('d-none');
                importResult.innerHTML = '';
                importFile.value = '';
                importModal.show();
            });
        }

        if (importForm) {
            importForm.addEventListener('submit', function(e){
                e.preventDefault();
                importError.classList.add('d-none');
                importResult.classList.add('d-none');
                importError.textContent = '';
                importSubmit.disabled = true;
                const fd = new FormData(importForm);
                fetch(importUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: fd
                }).then(async (res)=>{
                    importSubmit.disabled = false;
                    if (res.ok) {
                        const data = await res.json().catch(()=>({}));
                        const inserted = data.inserted ?? 0;
                        const errors = data.errors ?? [];
                        let html = `<div class=\"alert alert-success\">${inserted} baris berhasil diimport</div>`;
                        if (errors.length) {
                            html += '<div class=\"alert alert-warning\"><div class=\"fw-bold mb-2\">Beberapa baris dilewati:</div><ul class=\"mb-0\">' + errors.slice(0,10).map(e=>`<li>Baris ${e.row}: ${e.error}</li>`).join('') + (errors.length>10 ? `<li>dan ${errors.length-10} lainnya...</li>` : '') + '</ul></div>';
                        }
                        importResult.innerHTML = html;
                        importResult.classList.remove('d-none');
                        $('#items_table').DataTable().ajax.reload(null, false);
                    } else if (res.status === 422) {
                        const data = await res.json().catch(()=>({}));
                        importError.textContent = data.message || 'Validasi gagal';
                        importError.classList.remove('d-none');
                    } else {
                        importError.textContent = 'Gagal mengimport file';
                        importError.classList.remove('d-none');
                    }
                }).catch(()=>{
                    importSubmit.disabled = false;
                    importError.textContent = 'Gagal mengimport file';
                    importError.classList.remove('d-none');
                });
            });
        }
    });
</script>

<div class="modal fade" id="importItemsModal" tabindex="-1" aria-labelledby="importItemsModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="importItemsModalLabel">Import Items dari CSV</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="import_error" class="alert alert-danger d-none"></div>
        <form id="import_form" class="form" enctype="multipart/form-data">
            @csrf
            <div class="mb-10">
                <label class="form-label required">File CSV</label>
                <input type="file" id="import_file" name="file" accept=".csv,text/csv" class="form-control" required />
                <div class="form-text">Maks 20 MB. Pastikan header sesuai panduan.</div>
            </div>
        </form>
        <div id="import_result" class="d-none"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
        <button type="submit" form="import_form" id="import_submit" class="btn btn-success">Import</button>
      </div>
    </div>
  </div>
</div>
@endpush
