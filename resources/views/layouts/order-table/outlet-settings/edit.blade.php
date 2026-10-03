@php
    $setting = $setting ?? $data ?? new App\Models\OrderTable\OutletSetting;
    $creating = $creating ?? !$setting->exists;
    $outlet = $outlet ?? $setting->outlet;
@endphp

<x-modal addStyle="modal-lg" title="{{ $creating ? 'Tambah Pengaturan Order' : 'Pengaturan Order - '.$outlet->name }}"
    description="Periksa kembali jam operasional dan lokasi sebelum menyimpan."
    update="{{ !$creating }}" action="{{ $creating ? route('order-table/outlet-settings/store') : route('order-table/outlet-settings/update', $setting->id) }}" method="POST">
    @if (!$creating)
        @method('put')
    @endif

    @if ($creating)
        <div class="col-12 mb-3">
            <label for="outlet_id" class="form-label">Outlet <span class="text-danger">*</span></label>
            <select id="outlet_id" name="outlet_id" class="form-select" required>
                <option value="">Pilih outlet yang belum dikonfigurasi</option>
                @foreach ($outlets as $availableOutlet)
                    <option value="{{ $availableOutlet->id }}" @selected(old('outlet_id') == $availableOutlet->id)>{{ $availableOutlet->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="col-12 mb-3">
        <div class="alert alert-warning mb-0">
            <strong>Perhatian:</strong> pause manual dan perubahan jam berlaku segera. Outlet tanpa koordinat menghasilkan status geofence tidak diketahui.
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <label for="order_enabled" class="form-label">Layanan Order Table <span class="text-danger">*</span></label>
        <select id="order_enabled" name="order_enabled" class="form-select" required>
            <option value="1" @selected(old('order_enabled', $setting->order_enabled) == 1)>Aktif</option>
            <option value="0" @selected(old('order_enabled', $setting->order_enabled) == 0)>Nonaktif</option>
        </select>
    </div>
    @if ($creating)
        <input type="hidden" name="forced_close" value="0">
    @endif
    <div class="col-md-6 mb-3">
        <label for="stock_mode" class="form-label">Mode Pemeriksaan Stok <span class="text-danger">*</span></label>
        <select id="stock_mode" name="stock_mode" class="form-select" required>
            <option value="off" @selected(old('stock_mode', $setting->stock_mode) === 'off')>Tanpa pemeriksaan stok</option>
            <option value="status_only" @selected(old('stock_mode', $setting->stock_mode) === 'status_only')>Tampilkan status stok saja</option>
            <option value="strict" @selected(old('stock_mode', $setting->stock_mode) === 'strict')>Ketat, blokir jika stok tidak cukup</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label for="service_fee_pct" class="form-label">Biaya Layanan (%)</label>
        <input id="service_fee_pct" name="service_fee_pct" type="number" min="0" max="100" step="0.01"
            class="form-control" value="{{ old('service_fee_pct', $setting->service_fee_pct) }}" placeholder="0.00">
    </div>
    <div class="col-md-6 mb-3">
        <label for="open_time" class="form-label">Jam Buka</label>
        <input id="open_time" name="open_time" type="time" class="form-control"
            value="{{ old('open_time', $setting->open_time ? substr($setting->open_time, 0, 5) : '') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label for="close_time" class="form-label">Jam Tutup</label>
        <input id="close_time" name="close_time" type="time" class="form-control"
            value="{{ old('close_time', $setting->close_time ? substr($setting->close_time, 0, 5) : '') }}">
        <small class="text-muted">Jam tutup boleh melewati tengah malam.</small>
    </div>
    <div class="col-md-6 mb-3">
        <label for="auto_preparing_delay_seconds" class="form-label">Jeda Otomatis ke Persiapan (detik)</label>
        <input id="auto_preparing_delay_seconds" name="auto_preparing_delay_seconds" type="number" min="0"
            class="form-control" value="{{ old('auto_preparing_delay_seconds', $setting->auto_preparing_delay_seconds) }}" required>
        <small class="text-muted">Nilai 30 detik direkomendasikan. Ubah hanya bila alur operasional sudah disetujui.</small>
    </div>
    <div class="col-md-6 mb-3">
        <label for="session_close_time" class="form-label">Batas Penutupan Sesi <span class="text-danger">*</span></label>
        <input id="session_close_time" name="session_close_time" type="time" class="form-control"
            value="{{ old('session_close_time', substr($setting->session_close_time ?? '23:59', 0, 5)) }}" required>
    </div>

    <div class="col-12"><hr><h6 class="mb-3">Koordinat dan Geofence Outlet</h6></div>
    <div class="col-md-4 mb-3">
        <label for="latitude" class="form-label">Latitude</label>
        <input id="latitude" name="latitude" type="number" min="-90" max="90" step="0.0000001"
            class="form-control" value="{{ old('latitude', $outlet->latitude) }}" placeholder="-7.7955800">
    </div>
    <div class="col-md-4 mb-3">
        <label for="longitude" class="form-label">Longitude</label>
        <input id="longitude" name="longitude" type="number" min="-180" max="180" step="0.0000001"
            class="form-control" value="{{ old('longitude', $outlet->longitude) }}" placeholder="110.3694900">
    </div>
    <div class="col-md-4 mb-3">
        <label for="geofence_radius_m" class="form-label">Radius Geofence (meter)</label>
        <input id="geofence_radius_m" name="geofence_radius_m" type="number" min="50" max="2000"
            class="form-control" value="{{ old('geofence_radius_m', $outlet->geofence_radius_m ?? 150) }}" required>
        <small class="text-muted">Rekomendasi 50 sampai 2.000 meter.</small>
    </div>
</x-modal>
