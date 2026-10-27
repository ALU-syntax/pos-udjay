@php
    $diningTable = $diningTable ?? $data;
    $editing = filled($diningTable->id);
@endphp

{{-- ob-page memberi token & gaya report ke modal (modal dirender di luar .main-content) --}}
<x-modal addStyle="modal-lg ob-page ob-modal" title="{{ $editing ? 'Edit Meja' : 'Tambah Meja' }}"
    update="{{ $editing }}"
    action="{{ $editing ? route('order-table/dining-tables/update', $diningTable->id) : route('order-table/dining-tables/store') }}"
    method="POST">
    @if ($editing)
        @method('put')
    @endif

    <div class="col-12">
        <p class="ob-modal__desc">QR dibuat otomatis oleh sistem saat meja disimpan dan tidak dapat diisi manual.</p>
    </div>

    {{-- Identitas --}}
    <div class="col-12 ob-form-heading ob-form-heading--first">
        <h3 class="ob-section-title"><i class="fas fa-store" aria-hidden="true"></i> Outlet &amp; Identitas Meja</h3>
    </div>
    <div class="col-12 mb-3">
        <label for="outlet_id" class="form-label">Outlet <span class="text-danger">*</span></label>
        <select id="outlet_id" name="outlet_id" class="form-select" required>
            <option value="">Pilih outlet</option>
            @foreach ($outlets as $outlet)
                <option value="{{ $outlet->id }}" @selected(old('outlet_id', $diningTable->outlet_id) == $outlet->id)>{{ $outlet->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-5 mb-3">
        <label for="code" class="form-label">Kode Meja <span class="text-danger">*</span></label>
        <input id="code" name="code" type="text" maxlength="50" class="form-control text-uppercase"
            value="{{ old('code', $diningTable->code) }}" autocomplete="off" placeholder="Contoh: M-14" required>
        <small class="text-muted">Harus unik dalam outlet.</small>
    </div>
    <div class="col-md-7 mb-3">
        <label for="name" class="form-label">Nama Meja <span class="text-danger">*</span></label>
        <input id="name" name="name" type="text" maxlength="100" class="form-control"
            value="{{ old('name', $diningTable->name) }}" placeholder="Contoh: Meja Teras 14" required>
        <small class="text-muted">Tampil di halaman pelanggan dan lembar cetak QR.</small>
    </div>

    {{-- Kapasitas & status --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-qrcode" aria-hidden="true"></i> Kapasitas &amp; Status QR</h3>
    </div>
    <div class="col-md-6 mb-3">
        <label for="capacity" class="form-label">Kapasitas</label>
        <div class="input-group">
            <input id="capacity" name="capacity" type="number" min="1" class="form-control ob-input-suffixed"
                value="{{ old('capacity', $diningTable->capacity) }}" placeholder="4">
            <span class="input-group-text ob-input-suffix">orang</span>
        </div>
        <small class="text-muted">Opsional.</small>
    </div>
    @if (!$editing)
        <div class="col-md-6 mb-3">
            <label for="qr_active" class="form-label">Status QR <span class="text-danger">*</span></label>
            <select id="qr_active" name="qr_active" class="form-select" required>
                <option value="1" @selected(old('qr_active', true) == 1)>Aktif</option>
                <option value="0" @selected(old('qr_active', true) == 0)>Nonaktif</option>
            </select>
        </div>
    @else
        <div class="col-md-6 mb-3">
            <label for="qr_active_readonly" class="form-label">Status QR</label>
            <input id="qr_active_readonly" class="form-control" value="{{ $diningTable->qr_active ? 'Aktif' : 'Nonaktif' }}" readonly>
            <small class="text-muted">Ubah lewat tombol aktif/nonaktif di daftar meja.</small>
        </div>
    @endif
</x-modal>
