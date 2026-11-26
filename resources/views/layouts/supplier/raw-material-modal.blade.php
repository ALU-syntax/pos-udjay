{{-- ob-page memberi token & gaya report ke modal (modal dirender di luar .main-content) --}}
<x-modal addStyle="modal-lg ob-page ob-modal" title="{{ isset($update) ? 'Edit Bahan Baku Supplier' : 'Tambah Bahan Baku Supplier' }}"
    update="{{ isset($update) && $update ? true : false }}"
    action="{{ $action }}" method="POST">
    @if (isset($update) && $update)
        @method('put')
    @endif

    @php
        $selectedPreferred = (string) (int) old('is_preferred', $item->is_preferred ?? false);
        $selectedStatus = (string) (int) old('is_active', $item->is_active ?? true);
    @endphp

    <div class="col-12">
        <p class="ob-modal__desc">Bahan baku yang dijual <strong>{{ $supplier->name }}</strong>. Harga yang berubah otomatis tercatat di histori harga.</p>
    </div>

    {{-- Bahan baku --}}
    <div class="col-12 ob-form-heading ob-form-heading--first">
        <h3 class="ob-section-title"><i class="fas fa-box" aria-hidden="true"></i> Bahan Baku</h3>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label" for="raw_material_id">Bahan Baku Master <span class="text-danger">*</span></label>
        <select id="raw_material_id" name="raw_material_id" class="form-select" required>
            <option value="">Pilih bahan baku</option>
            @foreach ($rawMaterials as $material)
                <option value="{{ $material->id }}" @selected((string) old('raw_material_id', $item->raw_material_id) === (string) $material->id)>
                    {{ $material->name }}@if ($material->baseUnit) ({{ $material->baseUnit->name }})@endif{{ $material->is_active ? '' : ' — nonaktif' }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Satuan di dalam kurung adalah satuan dasar stok.</small>
    </div>
    <div class="col-md-7 mb-3">
        <label class="form-label" for="supplier_material_name">Nama di Supplier</label>
        <input id="supplier_material_name" name="supplier_material_name" value="{{ old('supplier_material_name', $item->supplier_material_name) }}" type="text"
            class="form-control" placeholder="Nama bahan sesuai nota/katalog supplier" maxlength="255">
    </div>
    <div class="col-md-5 mb-3">
        <label class="form-label" for="supplier_sku">SKU Supplier</label>
        <input id="supplier_sku" name="supplier_sku" value="{{ old('supplier_sku', $item->supplier_sku) }}" type="text"
            class="form-control" placeholder="Opsional" maxlength="100">
    </div>

    {{-- Pembelian --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-shopping-cart" aria-hidden="true"></i> Pembelian</h3>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="purchase_unit_id">Satuan Beli <span class="text-danger">*</span></label>
        <select id="purchase_unit_id" name="purchase_unit_id" class="form-select" required>
            <option value="">Pilih satuan</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected((string) old('purchase_unit_id', $item->purchase_unit_id) === (string) $unit->id)>{{ $unit->name }}</option>
            @endforeach
        </select>
        <small class="text-muted">Satuan saat memesan ke supplier, mis. karung atau dus.</small>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="current_price">Harga per Satuan Beli</label>
        <div class="input-group">
            <span class="input-group-text sp-input-prefix">Rp</span>
            <input id="current_price" name="current_price" type="number" step="0.01" min="0" class="form-control sp-input-prefixed"
                value="{{ old('current_price', $item->current_price) }}" placeholder="0">
        </div>
        <small class="text-muted">Kosongkan jika harga belum diketahui.</small>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="minimum_order_qty">Minimal Order (MOQ)</label>
        <input id="minimum_order_qty" name="minimum_order_qty" type="number" step="0.01" min="0" class="form-control"
            value="{{ old('minimum_order_qty', $item->minimum_order_qty !== null ? (float) $item->minimum_order_qty : 0) }}" placeholder="0">
        <small class="text-muted">Dalam satuan beli. Isi 0 jika tidak ada minimal.</small>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="lead_time_days">Lead Time <span class="text-danger">*</span></label>
        <div class="input-group">
            <input id="lead_time_days" name="lead_time_days" type="number" step="1" min="0" class="form-control ob-input-suffixed"
                value="{{ old('lead_time_days', $item->lead_time_days ?? 0) }}" placeholder="0" required>
            <span class="input-group-text ob-input-suffix">hari</span>
        </div>
        <small class="text-muted">Lama dari pesan sampai barang tiba. 0 = hari itu juga.</small>
    </div>

    {{-- Pengaturan --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-sliders-h" aria-hidden="true"></i> Pengaturan</h3>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="is_preferred">Supplier Utama <span class="text-danger">*</span></label>
        <select id="is_preferred" name="is_preferred" class="form-select" required>
            <option value="1" @selected($selectedPreferred === '1')>Ya, prioritaskan supplier ini</option>
            <option value="0" @selected($selectedPreferred === '0')>Tidak</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="is_active">Status <span class="text-danger">*</span></label>
        <select id="is_active" name="is_active" class="form-select" required>
            <option value="1" @selected($selectedStatus === '1')>Aktif</option>
            <option value="0" @selected($selectedStatus === '0')>Nonaktif</option>
        </select>
        <small class="text-muted">Bahan nonaktif tidak bisa dipilih di procurement plan.</small>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label" for="raw_material_notes">Catatan</label>
        <textarea id="raw_material_notes" name="notes" class="form-control" rows="3" placeholder="Contoh: harga belum termasuk ongkir">{{ old('notes', $item->notes) }}</textarea>
    </div>
</x-modal>
