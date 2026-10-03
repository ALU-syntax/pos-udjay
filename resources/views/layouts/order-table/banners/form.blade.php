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
    description="Banner dapat diarahkan ke URL atau tujuan internal Order Table."
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
        <label for="title" class="form-label">Judul <span class="text-danger">*</span></label>
        <input id="title" name="title" type="text" maxlength="255" class="form-control" value="{{ old('title', $banner->title) }}" required>
    </div>
    <div class="col-md-6 mb-3">
        <label for="image_url" class="form-label">URL Gambar</label>
        <input id="image_url" name="image_url" type="url" maxlength="500" class="form-control" value="{{ old('image_url', $remoteImageUrl) }}" placeholder="https://...">
        <small class="text-muted">Gunakan URL atau unggah file di sebelahnya.</small>
    </div>
    <div class="col-md-6 mb-3">
        <label for="image" class="form-label">Unggah Gambar</label>
        <input id="image" name="image_upload" type="file" accept="image/jpeg,image/png,image/webp" class="form-control">
    </div>
    <div class="col-12 mb-3">
        <div class="border rounded p-2 text-center bg-light"><img class="banner-image-preview {{ $banner->image_url ? '' : 'd-none' }}" src="{{ $banner->image_url ?: '' }}" alt="Preview banner" style="max-height:180px;max-width:100%;object-fit:contain"><span class="banner-image-placeholder {{ $banner->image_url ? 'd-none' : '' }} text-muted">Preview gambar</span></div>
    </div>
    <div class="col-md-4 mb-3">
        <label for="action_type" class="form-label">Jenis Aksi <span class="text-danger">*</span></label>
        <select id="action_type" name="action_type" class="form-select" required>
            @foreach (['url' => 'URL eksternal', 'internal' => 'Halaman internal', 'product' => 'Produk', 'category' => 'Kategori', 'voucher' => 'Voucher', 'promo' => 'Promo'] as $value => $label)
                <option value="{{ $value }}" @selected(old('action_type', $banner->action_type ?? 'url') === $value)>{{ $label }}</option>
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
    <div class="col-md-3 mb-3">
        <label for="position" class="form-label">Posisi <span class="text-danger">*</span></label>
        <select id="position" name="position" class="form-select" required>
            @foreach (['home_top' => 'Beranda atas', 'home_middle' => 'Beranda tengah', 'home_bottom' => 'Beranda bawah'] as $value => $label)<option value="{{ $value }}" @selected(old('position', $banner->position ?? 'home_top') === $value)>{{ $label }}</option>@endforeach
        </select>
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
