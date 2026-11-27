{{-- ob-page memberi token & gaya report ke modal (modal dirender di luar .main-content) --}}
<x-modal title="{{ $data->id ? 'Edit Bahan Baku' : 'Tambah Bahan Baku' }}" addStyle="modal-lg ob-page ob-modal"
    update="{{ $data->id ? true : false }}"
    action="{{ $action }}" method="POST">
    @if ($data->id)
        @method('put')
    @endif

    @php
        $selectedStatus = (string) (int) old('is_active', $data->exists ? $data->is_active : 1);
    @endphp

    <div class="col-12">
        <p class="ob-modal__desc">Bahan baku dipakai di stok gudang, pembelian ke supplier, dan konversi satuan.</p>
    </div>

    {{-- Identitas --}}
    <div class="col-12 ob-form-heading ob-form-heading--first">
        <h3 class="ob-section-title"><i class="fas fa-cube" aria-hidden="true"></i> Identitas Bahan</h3>
    </div>
    <div class="col-md-5 mb-3">
        <label class="form-label" for="code">Kode Bahan</label>
        <input id="code" name="code" value="{{ old('code', $data->code) }}" type="text" class="form-control"
            placeholder="Contoh: RM-0001" maxlength="50">
        <small class="text-muted">Harus unik. Kosongkan untuk dibuat otomatis (RM-0001, RM-0002, ...).</small>
    </div>
    <div class="col-md-7 mb-3">
        <label class="form-label" for="name">Nama Bahan <span class="text-danger">*</span></label>
        <input id="name" name="name" value="{{ old('name', $data->name) }}" type="text" class="form-control"
            placeholder="Contoh: Tepung Terigu, Gula Pasir" maxlength="255" required>
        <small class="text-muted">Nama yang dipakai tim gudang. Nama versi supplier diatur di detail supplier.</small>
    </div>

    {{-- Klasifikasi --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-tags" aria-hidden="true"></i> Klasifikasi</h3>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="raw_material_category_id">Kategori</label>
        <select id="raw_material_category_id" name="raw_material_category_id" class="form-select select2InsideModal">
            <option value="">Tanpa kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('raw_material_category_id', $data->raw_material_category_id) == $category->id)>
                    {{ $category->name }}{{ $category->is_active ? '' : ' (Nonaktif)' }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Hanya kategori aktif yang bisa dipilih.</small>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="base_unit_id">Satuan Dasar <span class="text-danger">*</span></label>
        <select id="base_unit_id" name="base_unit_id" class="form-select select2InsideModal" required>
            <option value="">Pilih satuan dasar</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected(old('base_unit_id', $data->base_unit_id) == $unit->id)>
                    {{ $unit->name }}{{ $unit->symbol ? ' (' . $unit->symbol . ')' : '' }}{{ $unit->is_active ? '' : ' (Nonaktif)' }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Satuan terkecil untuk mencatat stok. Satuan beli lain diatur lewat konversi.</small>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="storage_type_id">Tipe Penyimpanan <span class="text-danger">*</span></label>
        <select id="storage_type_id" name="storage_type_id" class="form-select select2InsideModal" required>
            <option value="">Pilih tipe penyimpanan</option>
            @foreach ($storageTypes as $storageType)
                <option value="{{ $storageType->id }}" @selected(old('storage_type_id', $data->storage_type_id) == $storageType->id)>
                    {{ $storageType->name }}
                </option>
            @endforeach
        </select>
        @if ($storageTypes->isEmpty())
            <small class="text-danger">Tipe penyimpanan belum tersedia. Jalankan seeder tipe penyimpanan terlebih dahulu.</small>
        @else
            <small class="text-muted">Menentukan cara simpan di gudang, misalnya suhu ruang atau beku.</small>
        @endif
    </div>

    {{-- Pengaturan --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-sliders-h" aria-hidden="true"></i> Pengaturan</h3>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="is_active">Status <span class="text-danger">*</span></label>
        <select id="is_active" name="is_active" class="form-select" required>
            <option value="1" @selected($selectedStatus === '1')>Aktif</option>
            <option value="0" @selected($selectedStatus === '0')>Nonaktif</option>
        </select>
        <small class="text-muted">Bahan nonaktif tetap tersimpan dan bisa diaktifkan lagi kapan saja.</small>
    </div>

    {{-- Catatan --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-sticky-note" aria-hidden="true"></i> Catatan</h3>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label" for="notes">Catatan</label>
        <textarea id="notes" name="notes" class="form-control" rows="3" maxlength="1000"
            placeholder="Contoh: simpan di tempat kering, cek expired saat penerimaan">{{ old('notes', $data->notes) }}</textarea>
        <small class="text-muted">Maksimal 1.000 karakter. Tampil di bawah nama bahan pada daftar.</small>
    </div>

    <script>
        $('.select2InsideModal').select2({
            dropdownParent: $("#modal_action"),
            width: '100%'
        });
    </script>
</x-modal>
