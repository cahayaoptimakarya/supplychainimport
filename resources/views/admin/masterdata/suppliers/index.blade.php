@extends('layouts.admin')

@section('title', 'Masterdata - Supplier')

@section('page_title', 'Supplier')

@section('page_actions')
@php use App\Support\Permission as Perm; @endphp
@php
    $canCreateSupp = Perm::can(auth()->user(), 'admin.masterdata.suppliers.index', 'create');
    $canCreateCat  = Perm::can(auth()->user(), 'admin.masterdata.supplier-categories.index', 'create');
@endphp
@if($canCreateSupp)
<a id="btn_create_supplier" href="{{ route('admin.masterdata.suppliers.create') }}" class="btn btn-primary">Create Supplier</a>
@endif
@if($canCreateCat)
<button id="btn_create_suppcat" type="button" class="btn btn-light-primary ms-2 d-none">Create Kategori</button>
@endif
@endsection

@section('page_breadcrumbs')
    <span class="text-muted">Home</span>
    <span class="mx-2">-</span>
    <span class="text-muted">Masterdata</span>
    <span class="mx-2">-</span>
    <span class="text-dark">Supplier</span>
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
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab_suppliers">Suppliers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab_suppcats">Kategori Supplier</a>
                    </li>
                </ul>
            </div>
            <div class="card-body py-6">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab_suppliers" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="suppliers_table">
                                <thead>
                                    <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                        <th>ID</th>
                                        <th>Nama</th>
                                        <th>Kategori</th>
                                        <th>Email</th>
                                        <th>Telepon</th>
                                        <th>Alamat</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab_suppcats" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="supplier_categories_table">
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
    // Suppliers endpoints
    const suppDataUrl   = '{{ route('admin.masterdata.suppliers.data') }}';
    const suppEditTpl   = '{{ route('admin.masterdata.suppliers.edit', ':id') }}';
    const suppDelTpl    = '{{ route('admin.masterdata.suppliers.destroy', ':id') }}';
    const canSuppUpdate = {{ \App\Support\Permission::can(auth()->user(), 'admin.masterdata.suppliers.index', 'update') ? 'true' : 'false' }};
    const canSuppDelete = {{ \App\Support\Permission::can(auth()->user(), 'admin.masterdata.suppliers.index', 'delete') ? 'true' : 'false' }};
    // Supplier Categories endpoints
    const suppCatDataUrl = '{{ route('admin.masterdata.supplier-categories.data') }}';
    const suppCatEditTpl = '{{ route('admin.masterdata.supplier-categories.edit', ':id') }}';
    const suppCatDelTpl  = '{{ route('admin.masterdata.supplier-categories.destroy', ':id') }}';
    const canSuppCatUpdate = {{ \App\Support\Permission::can(auth()->user(), 'admin.masterdata.supplier-categories.index', 'update') ? 'true' : 'false' }};
    const canSuppCatDelete = {{ \App\Support\Permission::can(auth()->user(), 'admin.masterdata.supplier-categories.index', 'delete') ? 'true' : 'false' }};

    document.addEventListener('DOMContentLoaded', function() {
        const table = $('#suppliers_table').DataTable({
            processing: true,
            serverSide: false,
            dom: 'lrtip',
            ajax: {
                url: suppDataUrl,
                dataSrc: 'data',
                error: function(xhr){
                    console.error('Suppliers AJAX error:', xhr.responseText);
                    alert('Gagal memuat data supplier');
                }
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'name', name: 'name' },
                { data: 'category', name: 'category', defaultContent: '-' },
                { data: 'email', name: 'email', defaultContent: '-' },
                { data: 'phone', name: 'phone', defaultContent: '-' },
                { data: 'address', name: 'address', defaultContent: '-' },
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    className: 'text-end',
                    render: function (data, type, row) {
                        const editUrl = suppEditTpl.replace(':id', data);
                        const delUrl  = suppDelTpl.replace(':id', data);
                        let html = '';
                        if (canSuppUpdate) html += `<a href=\"${editUrl}\" class=\"btn btn-light-primary btn-sm me-2\">Edit</a>`;
                        if (canSuppDelete) html += `<button type=\"button\" data-id=\"${data}\" data-url=\"${delUrl}\" class=\"btn btn-light-danger btn-sm btn-delete\">Hapus</button>`;
                        return html || '-';
                    }
                }
            ]
        });

        // Supplier categories table
        const catTable = $('#supplier_categories_table').DataTable({
            processing: true,
            serverSide: false,
            dom: 'lrtip',
            ajax: {
                url: suppCatDataUrl,
                dataSrc: 'data',
                error: function(xhr){
                    console.error('SupplierCategories AJAX error:', xhr.responseText);
                    alert('Gagal memuat data kategori supplier');
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
                        const editUrl = suppCatEditTpl.replace(':id', data);
                        const delUrl  = suppCatDelTpl.replace(':id', data);
                        let html = '';
                        if (canSuppCatUpdate) html += `<button type=\"button\" data-id=\"${data}\" data-name=\"${row.name || ''}\" class=\"btn btn-light-primary btn-sm me-2 btn-edit-suppcat\">Edit</button>`;
                        if (canSuppCatDelete) html += `<button type=\"button\" data-id=\"${data}\" data-url=\"${delUrl}\" class=\"btn btn-light-danger btn-sm btn-delete-cat\">Hapus</button>`;
                        return html || '-';
                    }
                }
            ]
        });

        $('#suppliers_table').on('click', '.btn-delete', function() {
            const url = this.getAttribute('data-url');
            if (!confirm('Yakin ingin menghapus supplier ini?')) return;
            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: new URLSearchParams({ _method: 'DELETE' })
            }).then(res => {
                if (res.ok) {
                    $('#suppliers_table').DataTable().ajax.reload(null, false);
                } else {
                    alert('Gagal menghapus supplier');
                }
            }).catch(() => alert('Gagal menghapus supplier'));
        });

        const globalInput = document.getElementById('global_search');
        if (globalInput) {
            globalInput.addEventListener('input', function() {
                const val = this.value;
                if (document.querySelector('#tab_suppliers').classList.contains('active')) {
                    table.search(val).draw();
                } else {
                    catTable.search(val).draw();
                }
            });
        }

        // Delete handlers
        $('#supplier_categories_table').on('click', '.btn-delete-cat', function() {
            const url = this.getAttribute('data-url');
            if (!confirm('Yakin ingin menghapus kategori supplier ini?')) return;
            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: new URLSearchParams({ _method: 'DELETE' })
            }).then(res => {
                if (res.ok) catTable.ajax.reload(null, false);
                else alert('Gagal menghapus kategori supplier');
            }).catch(() => alert('Gagal menghapus kategori supplier'));
        });

        // Toggle Create buttons per tab
        const btnCreateSupp = document.getElementById('btn_create_supplier');
        const btnCreateCat  = document.getElementById('btn_create_suppcat');
        function toggleButtons(active) {
            if (btnCreateSupp) btnCreateSupp.classList.toggle('d-none', active !== 'supp');
            if (btnCreateCat)  btnCreateCat.classList.toggle('d-none', active !== 'cat');
        }
        toggleButtons('supp');
        const tabElList = [].slice.call(document.querySelectorAll('a[data-bs-toggle="tab"]'))
        tabElList.forEach(function (tabEl) {
            tabEl.addEventListener('shown.bs.tab', function (event) {
                const target = event.target.getAttribute('href');
                if (target === '#tab_suppliers') { table.columns.adjust(); toggleButtons('supp'); }
                if (target === '#tab_suppcats') { catTable.columns.adjust(); toggleButtons('cat'); }
                if (history && history.replaceState) {
                    history.replaceState(null, '', target);
                } else {
                    location.hash = target;
                }
            });
        });

        // Activate tab by hash on load
        if (location.hash === '#tab_suppcats') {
            const link = document.querySelector('a[href="#tab_suppcats"]');
            if (link) new bootstrap.Tab(link).show();
        }

        // Modal for Supplier Category create/edit
        const modalEl = document.getElementById('supplierCategoryModal');
        const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
        const form = document.getElementById('suppcat_form');
        const nameInput = document.getElementById('suppcat_name');
        const titleEl = document.getElementById('supplierCategoryModalLabel');
        const submitBtn = document.getElementById('suppcat_submit');
        const idInput = document.getElementById('suppcat_id');
        const errorEl = document.getElementById('suppcat_error');

        function openSuppCatModal(mode, data) {
            if (!modal) return;
            errorEl.classList.add('d-none');
            errorEl.textContent = '';
            if (mode === 'create') {
                titleEl.textContent = 'Tambah Kategori Supplier';
                form.setAttribute('data-mode', 'create');
                idInput.value = '';
                nameInput.value = '';
            } else {
                titleEl.textContent = 'Edit Kategori Supplier';
                form.setAttribute('data-mode', 'edit');
                idInput.value = data.id;
                nameInput.value = data.name || '';
            }
            modal.show();
        }

        const createBtn = document.getElementById('btn_create_suppcat');
        if (createBtn) {
            createBtn.addEventListener('click', function(){ openSuppCatModal('create'); });
        }

        $('#supplier_categories_table').on('click', '.btn-edit-suppcat', function(){
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            openSuppCatModal('edit', { id, name });
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
                    url = suppCatEditTpl.replace(':id', idInput.value);
                    payload.append('_method', 'PUT');
                } else {
                    url = '{{ route('admin.masterdata.supplier-categories.store') }}';
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
                        alert('Gagal menyimpan kategori supplier');
                    }
                }).catch(() => { submitBtn.disabled = false; alert('Gagal menyimpan kategori supplier'); });
            });
        }
    });
</script>
<div class="modal fade" id="supplierCategoryModal" tabindex="-1" aria-labelledby="supplierCategoryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="supplierCategoryModalLabel">Kategori Supplier</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="suppcat_error" class="alert alert-danger d-none"></div>
        <form id="suppcat_form" class="form" data-mode="create">
            <input type="hidden" id="suppcat_id" />
            <div class="mb-10">
                <label class="form-label required">Nama</label>
                <input type="text" id="suppcat_name" class="form-control" placeholder="Nama kategori supplier" required />
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" form="suppcat_form" id="suppcat_submit" class="btn btn-primary">Simpan</button>
      </div>
    </div>
  </div>
</div>
@endpush
