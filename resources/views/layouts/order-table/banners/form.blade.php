@php
    $banner = $banner ?? $data;
    $editing = filled($banner->id);
    $canManageGlobal = $canManageGlobal ?? false;
    $targetOption = $targetOption ?? null;
    $remoteImageUrl = filter_var($banner->image_url, FILTER_VALIDATE_URL)
        && in_array(strtolower((string) parse_url($banner->image_url, PHP_URL_SCHEME)), ['http', 'https'], true)
        ? $banner->image_url
        : '';
@endphp

<x-modal addStyle="modal-xl" title="{{ $editing ? 'Edit Banner' : 'Tambah Banner' }}"
    description="Banner berupa gambar jadi atau kartu teks, dan dapat diarahkan ke produk, kategori, voucher, atau URL."
    update="{{ $editing }}"
    action="{{ $editing ? route('order-table/banners/update', $banner->id) : route('order-table/banners/store') }}"
    method="POST">
    @if ($editing)
        @method('put')
    @endif

    <div class="col-12 mb-3 global-impact-warning d-none"><div class="alert alert-warning mb-0"><strong>Perhatian:</strong> banner global tampil di seluruh outlet aktif.</div></div>
    <div class="col-md-6 mb-3">
        <label for="outlet_id" class="form-label">Outlet <span class="text-danger">*</span></label>
        <select id="outlet_id" name="outlet_id" class="form-select" @required(!$canManageGlobal)>
            @if ($canManageGlobal)<option value="" @selected(old('outlet_id', $banner->outlet_id) === null || old('outlet_id') === '')>Global - semua outlet</option>@else<option value="">Pilih outlet</option>@endif
            @foreach ($outlets as $outlet)<option value="{{ $outlet->id }}" @selected((string) old('outlet_id', $banner->outlet_id) === (string) $outlet->id)>{{ $outlet->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label for="display_mode" class="form-label">Jenis Banner <span class="text-danger">*</span></label>
        <select id="display_mode" name="display_mode" class="form-select" required>
            @foreach (['image' => 'Gambar (tulisan sudah ada di gambar)', 'text' => 'Kartu teks'] as $value => $label)
                <option value="{{ $value }}" @selected(old('display_mode', $banner->display_mode ?? 'image') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label for="title" class="form-label">Judul <span class="text-danger">*</span></label>
        <input id="title" name="title" type="text" maxlength="255" data-text-maxlength="60" class="form-control" value="{{ old('title', $banner->title) }}" required>
        <small class="text-muted banner-image-only">Tidak ditampilkan; dipakai sebagai teks alternatif gambar.</small>
        <small class="text-muted banner-text-only">Judul utama kartu, maks. 60 karakter.</small>
    </div>
    <div class="col-md-6 mb-3 banner-text-only">
        <label for="eyebrow" class="form-label">Label Kecil</label>
        <input id="eyebrow" name="eyebrow" type="text" maxlength="40" class="form-control" value="{{ old('eyebrow', $banner->eyebrow) }}" placeholder="Contoh: Promo Minggu Ini">
    </div>
    <div class="col-md-6 mb-3 banner-text-only">
        <label for="tone" class="form-label">Warna <span class="text-danger">*</span></label>
        <select id="tone" name="tone" class="form-select">
            @foreach (['black' => 'Hitam', 'red' => 'Merah', 'coffee' => 'Kopi', 'yellow' => 'Kuning'] as $value => $label)
                <option value="{{ $value }}" @selected(old('tone', $banner->tone ?? 'black') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-muted">Jadi latar bila tanpa gambar, atau lapisan warna di atas gambar latar.</small>
    </div>
    <div class="col-12 mb-3 banner-text-only">
        <label for="subtitle" class="form-label">Subjudul</label>
        <input id="subtitle" name="subtitle" type="text" maxlength="120" class="form-control" value="{{ old('subtitle', $banner->subtitle) }}" placeholder="Contoh: Min. belanja Rp 30.000, semua menu">
    </div>
    <div class="col-md-6 mb-3">
        <label for="image_url" class="form-label"><span class="banner-image-only">URL Gambar</span><span class="banner-text-only">URL Gambar Latar (opsional)</span></label>
        <input id="image_url" name="image_url" type="url" maxlength="500" class="form-control" value="{{ old('image_url', $remoteImageUrl) }}" placeholder="https://...">
        <small class="text-muted">Gunakan URL atau unggah file di sebelahnya. Ukuran 1200×675 (16:9).</small>
    </div>
    <div class="col-md-6 mb-3">
        <label for="image" class="form-label">Unggah Gambar</label>
        <input id="image" name="image_upload" type="file" accept="image/jpeg,image/png,image/webp" class="form-control">
        @if ($editing && $banner->image_url)
            <div class="form-check mt-2 banner-text-only">
                <input id="remove_image" name="remove_image" type="checkbox" value="1" class="form-check-input" @checked(old('remove_image'))>
                <label for="remove_image" class="form-check-label">Hapus gambar latar</label>
            </div>
        @endif
    </div>
    <div class="col-12 mb-3">
        <div class="border rounded p-2 text-center bg-light"><img class="banner-image-preview {{ $banner->image_url ? '' : 'd-none' }}" src="{{ $banner->image_url ?: '' }}" alt="Preview banner" style="max-height:180px;max-width:100%;object-fit:contain"><span class="banner-image-placeholder {{ $banner->image_url ? 'd-none' : '' }} text-muted">Preview gambar</span></div>
    </div>
    <div class="col-md-4 mb-3">
        <label for="action_type" class="form-label">Jenis Aksi <span class="text-danger">*</span></label>
        <select id="action_type" name="action_type" class="form-select" required>
            @foreach (['none' => 'Tanpa aksi', 'product' => 'Buka produk', 'category' => 'Ke kategori', 'voucher' => 'Salin kode voucher', 'url' => 'URL eksternal'] as $value => $label)
                <option value="{{ $value }}" @selected(old('action_type', $banner->action_type ?? 'none') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-8 mb-3 action-url-field">
        <label for="action_url" class="form-label">URL Tujuan</label>
        <input id="action_url" name="action_value" type="url" maxlength="500" class="form-control" value="{{ old('action_value', $banner->action_value) }}" placeholder="https://...">
    </div>
    <div class="col-md-8 mb-3 action-target-field d-none">
        <label for="action_target" class="form-label">Target Aksi</label>
        <select id="action_target" name="action_value" class="form-select" disabled>
            @if ($targetOption)<option value="{{ data_get($targetOption, 'id') }}" selected>{{ data_get($targetOption, 'text', data_get($targetOption, 'name')) }}</option>@endif
        </select>
        <small class="text-muted">Target dipilih dari daftar yang diizinkan, bukan ID manual.</small>
    </div>
    <div class="col-md-3 mb-3"><label for="sort_order" class="form-label">Urutan</label><input id="sort_order" name="sort_order" type="number" min="0" class="form-control" value="{{ old('sort_order', $banner->sort_order ?? 0) }}"></div>
    <div class="col-md-3 mb-3"><label for="start_at" class="form-label">Mulai</label><input id="start_at" name="start_at" type="datetime-local" class="form-control" value="{{ old('start_at', $banner->start_at?->format('Y-m-d\TH:i')) }}"></div>
    <div class="col-md-3 mb-3"><label for="end_at" class="form-label">Selesai</label><input id="end_at" name="end_at" type="datetime-local" class="form-control" value="{{ old('end_at', $banner->end_at?->format('Y-m-d\TH:i')) }}"></div>
    @if (!$editing)
        <div class="col-md-3 mb-3"><label for="status" class="form-label">Status</label><select id="status" name="status" class="form-select"><option value="1" @selected(old('status', true) == 1)>Aktif</option><option value="0" @selected(old('status', true) == 0)>Nonaktif</option></select></div>
    @else
        <div class="col-md-3 mb-3"><label class="form-label">Status</label><input class="form-control bg-light" value="{{ $banner->status ? 'Aktif' : 'Nonaktif' }}" readonly></div>
    @endif
</x-modal>
