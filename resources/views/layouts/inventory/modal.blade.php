{{-- ob-page memberi token & gaya report ke modal (modal dirender di luar .main-content) --}}
<x-modal title="{{ $data->id ? 'Edit Lokasi Inventory' : 'Tambah Lokasi Inventory' }}" addStyle="modal-lg ob-page ob-modal"
    update="{{ $data->id ? true : false }}"
    action="{{ $action }}" method="POST">
    @if ($data->id)
        @method('put')
    @endif

    @php
        $selectedStatus = (string) (int) old('is_active', $data->exists ? $data->is_active : 1);
    @endphp

    <div class="col-12">
        <p class="ob-modal__desc">Lokasi adalah tempat bahan baku disimpan, misalnya gudang pusat, kitchen outlet, atau rak tertentu.</p>
    </div>

    {{-- Identitas --}}
    <div class="col-12 ob-form-heading ob-form-heading--first">
        <h3 class="ob-section-title"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Identitas Lokasi</h3>
    </div>
    <div class="col-md-5 mb-3">
        <label class="form-label" for="code">Kode Lokasi</label>
        <input id="code" name="code" value="{{ old('code', $data->code) }}" type="text" class="form-control"
            maxlength="50" placeholder="Contoh: WH-CENTRAL, KITCH-01">
        <small class="text-muted">Opsional. Singkatan pendek agar mudah dikenali di dokumen gudang.</small>
    </div>
    <div class="col-md-7 mb-3">
        <label class="form-label" for="name">Nama Lokasi <span class="text-danger">*</span></label>
        <input id="name" name="name" value="{{ old('name', $data->name) }}" type="text" class="form-control"
            maxlength="255" required placeholder="Contoh: Gudang Pusat, Kitchen Outlet A">
    </div>

    {{-- Struktur --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-sitemap" aria-hidden="true"></i> Struktur</h3>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="inventory_type_id">Tipe Lokasi <span class="text-danger">*</span></label>
        <select id="inventory_type_id" name="inventory_type_id" class="form-select select2InsideModal" required>
            <option value="">Pilih tipe lokasi</option>
            @foreach ($types as $type)
                <option value="{{ $type->id }}" @selected(old('inventory_type_id', $data->inventory_type_id) == $type->id)>
                    {{ $type->name }}{{ $type->is_active ? '' : ' (Nonaktif)' }}
                </option>
            @endforeach
        </select>
        @if ($types->isEmpty())
            <small class="text-danger">Belum ada tipe lokasi inventory. Jalankan migration/seed tipe terlebih dahulu.</small>
        @else
            <small class="text-muted">Hanya tipe aktif yang bisa dipilih.</small>
        @endif
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="parent_id">Bagian dari Lokasi</label>
        <select id="parent_id" name="parent_id" class="form-select select2InsideModal">
            <option value="">Tidak ada (lokasi utama)</option>
            @foreach ($parents as $parent)
                <option value="{{ $parent->id }}" @selected(old('parent_id', $data->parent_id) == $parent->id)>
                    {{ $parent->name }}{{ $parent->is_active ? '' : ' (Nonaktif)' }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Isi bila lokasi ini berada di dalam lokasi lain, misalnya rak di dalam gudang.</small>
    </div>

    {{-- Cakupan --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-store" aria-hidden="true"></i> Cakupan</h3>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="outlet_id">Outlet</label>
        <select id="outlet_id" name="outlet_id" class="form-select select2InsideModal">
            <option value="">Semua outlet</option>
            @foreach ($outlets as $outlet)
                <option value="{{ $outlet->id }}" @selected(old('outlet_id', $data->outlet_id) == $outlet->id)>
                    {{ $outlet->name }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Kosongkan bila lokasi melayani semua outlet, misalnya gudang pusat.</small>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="brand_id">Brand</label>
        <select id="brand_id" name="brand_id" class="form-select select2InsideModal">
            <option value="">Semua brand</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}" @selected(old('brand_id', $data->brand_id) == $brand->id)>
                    {{ $brand->name }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Kosongkan bila stok di lokasi ini dipakai bersama semua brand.</small>
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
        <small class="text-muted">Lokasi nonaktif tetap tersimpan beserta saldo stoknya.</small>
    </div>

    <script>
        $('.select2InsideModal').select2({
            dropdownParent: $("#modal_action"),
            width: '100%'
        });
    </script>
</x-modal>
