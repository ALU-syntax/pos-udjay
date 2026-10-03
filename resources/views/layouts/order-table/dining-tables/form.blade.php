@php
    $diningTable = $diningTable ?? $data;
    $editing = filled($diningTable->id);
@endphp

<x-modal title="{{ $editing ? 'Edit Meja' : 'Tambah Meja' }}"
    description="Token QR dibuat otomatis oleh sistem dan tidak dapat diisi manual."
    update="{{ $editing }}"
    action="{{ $editing ? route('order-table/dining-tables/update', $diningTable->id) : route('order-table/dining-tables/store') }}"
    method="POST">
    @if ($editing)
        @method('put')
    @endif

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
            value="{{ old('code', $diningTable->code) }}" placeholder="M-14" required>
        <small class="text-muted">Harus unik dalam outlet.</small>
    </div>
    <div class="col-md-7 mb-3">
        <label for="name" class="form-label">Nama Meja <span class="text-danger">*</span></label>
        <input id="name" name="name" type="text" maxlength="100" class="form-control"
            value="{{ old('name', $diningTable->name) }}" placeholder="Meja Teras 14" required>
    </div>
    <div class="col-md-6 mb-3">
        <label for="capacity" class="form-label">Kapasitas</label>
        <div class="input-group">
            <input id="capacity" name="capacity" type="number" min="1" class="form-control"
                value="{{ old('capacity', $diningTable->capacity) }}" placeholder="4">
            <span class="input-group-text">orang</span>
        </div>
    </div>
    @if (!$editing)
        <div class="col-md-6 mb-3">
            <label for="qr_active" class="form-label">Status QR <span class="text-danger">*</span></label>
            <select id="qr_active" name="qr_active" class="form-select" required>
                <option value="1" @selected(old('qr_active', true) == 1)>Aktif</option>
                <option value="0" @selected(old('qr_active', true) == 0)>Nonaktif</option>
            </select>
        </div>
    @endif
</x-modal>
