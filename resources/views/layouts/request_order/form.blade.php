@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')
    @include('layouts.warehouse.partials.ui-styles')

    @php
        $formItems = old('items');

        if (!$formItems) {
            $formItems = $data->exists
                ? $data->items->map(fn ($item) => [
                    'raw_material_id' => $item->raw_material_id,
                    // Tanpa nol di belakang koma agar input tidak tampil "2.00000"
                    'requested_qty' => rtrim(rtrim(number_format((float) $item->requested_qty, 5, '.', ''), '0'), '.'),
                    'requested_satuan_id' => $item->requested_satuan_id,
                    'notes' => $item->notes,
                ])->values()->all()
                : [];
        }

        $formItems = collect($formItems ?: [])->filter(fn ($item) => filled($item['raw_material_id'] ?? null))->values()->all();
        $rawMaterialsById = $rawMaterials->keyBy('id');
        $baseUnitOf = fn ($rawMaterial) => $rawMaterial?->baseUnit?->symbol ?: $rawMaterial?->baseUnit?->name;
        $rawMaterialCatalog = $rawMaterials->mapWithKeys(fn ($rawMaterial) => [
            $rawMaterial->id => [
                'id' => $rawMaterial->id,
                'name' => $rawMaterial->name,
                'code' => $rawMaterial->code,
                'base_unit' => $baseUnitOf($rawMaterial),
            ],
        ])->all();
        $backUrl = $data->exists ? route('warehouse/request-order/detail', $data->id) : route('warehouse/request-order');
    @endphp

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">
                    <a href="{{ route('warehouse/request-order') }}">Request Order</a><span class="wh-crumb-sep" aria-hidden="true">/</span>{{ $data->exists ? $data->request_number : 'Tambah' }}
                </div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-clipboard-list"></i></span>
                    {{ $title }}
                </h1>
                <p class="ob-header__desc">Susun kebutuhan bahan baku sebagai draft. Draft bisa diubah sampai diajukan untuk review.</p>
            </div>
            <div class="wh-actions">
                <a href="{{ $backUrl }}" class="btn ob-btn ob-btn-outline">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Kembali
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="ob-notice ro-notice" role="status">
                <i class="fas fa-check-circle" aria-hidden="true"></i> {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="ob-notice ro-notice ro-notice--error" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i> Ada data yang belum sesuai. Periksa kembali field yang ditandai.
            </div>
        @endif

        <form action="{{ $action }}" method="POST" id="requestOrderForm">
            @csrf
            @if ($data->exists)
                @method('put')
            @endif

            {{-- Informasi request --}}
            <div class="ob-card ro-card">
                <div class="wh-card__head">
                    <div>
                        <h2 class="wh-card__title"><i class="fas fa-info-circle" aria-hidden="true"></i> Informasi Request</h2>
                        <p class="wh-card__desc">Siapa yang meminta, dari mana dipenuhi, dan kapan dibutuhkan.</p>
                    </div>
                </div>
                <div class="wh-card__body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="request_number">Nomor Request</label>
                            <input id="request_number" name="request_number" value="{{ old('request_number', $data->request_number) }}"
                                type="text" class="form-control @error('request_number') is-invalid @enderror"
                                maxlength="100" placeholder="Contoh: RO-20261127-0001">
                            @error('request_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <small class="text-muted">Kosongkan untuk dibuat otomatis (RO-tanggal-urutan).</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="needed_at">Tanggal Dibutuhkan</label>
                            <input id="needed_at" name="needed_at" value="{{ old('needed_at', optional($data->needed_at)->format('Y-m-d')) }}"
                                type="date" class="form-control @error('needed_at') is-invalid @enderror">
                            @error('needed_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <small class="text-muted">Opsional. Request yang lewat tanggal ini ditandai di daftar.</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="requester_inventory_id">Lokasi Peminta <span class="text-danger">*</span></label>
                            <select id="requester_inventory_id" name="requester_inventory_id"
                                class="form-select select2RequestOrder @error('requester_inventory_id') is-invalid @enderror" required>
                                <option value="">Pilih lokasi peminta</option>
                                @foreach ($inventories as $inventory)
                                    <option value="{{ $inventory->id }}" @selected(old('requester_inventory_id', $data->requester_inventory_id) == $inventory->id)>
                                        {{ $inventory->name }}{{ $inventory->is_active ? '' : ' (Nonaktif)' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('requester_inventory_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @else
                                <small class="text-muted">Outlet atau kitchen yang membutuhkan bahan.</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="fulfillmentInventory">Lokasi Pemenuh</label>
                            <select name="fulfillment_inventory_id" id="fulfillmentInventory"
                                class="form-select select2RequestOrder @error('fulfillment_inventory_id') is-invalid @enderror">
                                <option value="">Belum ditentukan</option>
                                @foreach ($inventories as $inventory)
                                    <option value="{{ $inventory->id }}" @selected(old('fulfillment_inventory_id', $data->fulfillment_inventory_id) == $inventory->id)>
                                        {{ $inventory->name }}{{ $inventory->is_active ? '' : ' (Nonaktif)' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('fulfillment_inventory_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @else
                                <small class="text-muted">Gudang yang mengirim bahan. Stok bebasnya tampil di tiap item.</small>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="notes">Catatan</label>
                            <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3"
                                placeholder="Contoh: untuk persiapan event akhir pekan">{{ old('notes', $data->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Item --}}
            <div class="ob-card ro-card">
                <div class="wh-card__head">
                    <div>
                        <h2 class="wh-card__title"><i class="fas fa-boxes" aria-hidden="true"></i> Bahan Baku <span class="wh-count" id="requestOrderItemsCount">{{ count($formItems) }}</span></h2>
                        <p class="wh-card__desc">Pilih bahan satu per satu. Bahan yang sudah ada di daftar tidak bisa dipilih lagi.</p>
                    </div>
                </div>
                <div class="wh-card__body ro-picker">
                    <div class="ro-picker__field">
                        <label class="form-label" for="rawMaterialPicker">Tambah bahan baku</label>
                        <select id="rawMaterialPicker" class="form-select select2RequestOrder">
                            <option value="">Cari nama atau kode bahan</option>
                            @foreach ($rawMaterials as $rawMaterial)
                                <option value="{{ $rawMaterial->id }}">
                                    {{ $rawMaterial->name }}{{ $rawMaterial->code ? ' · ' . $rawMaterial->code : '' }}{{ $baseUnitOf($rawMaterial) ? ' (' . $baseUnitOf($rawMaterial) . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn ob-btn ob-btn-outline" id="clearRequestOrderItems">
                        <i class="fas fa-eraser" aria-hidden="true"></i> Kosongkan daftar
                    </button>
                </div>

                @error('items')
                    <div class="ob-notice ro-notice ro-notice--error ro-notice--inset" role="alert">
                        <i class="fas fa-exclamation-circle" aria-hidden="true"></i> {{ $message }}
                    </div>
                @enderror

                <div class="ob-table-scroll">
                    <table class="wh-table ro-items" id="requestOrderItemsTable">
                        <thead>
                            <tr>
                                <th scope="col">Bahan Baku</th>
                                <th scope="col" class="ro-col-qty">Qty</th>
                                <th scope="col" class="ro-col-unit">Satuan</th>
                                <th scope="col" class="ro-col-notes">Catatan</th>
                                <th scope="col"><span class="visually-hidden">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($formItems as $index => $item)
                                @php
                                    $rawMaterialId = $item['raw_material_id'] ?? null;
                                    $rawMaterial = $rawMaterialsById->get((int) $rawMaterialId);
                                @endphp
                                <tr class="request-item-row">
                                    <td>
                                        <input type="hidden" name="items[{{ $index }}][raw_material_id]"
                                            data-field="raw_material_id" class="item-raw-material-id" value="{{ $rawMaterialId }}">
                                        <div class="wh-cell-stack">
                                            <span class="wh-strong item-material-name">{{ $rawMaterial?->name ?? '-' }}</span>
                                            <span class="ob-cell-meta item-material-code">
                                                {{ $rawMaterial?->code ?: 'Tanpa kode' }}{{ $baseUnitOf($rawMaterial) ? ' · Satuan dasar: ' . $baseUnitOf($rawMaterial) : '' }}
                                            </span>
                                        </div>
                                        @error('items.' . $index . '.raw_material_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <div class="item-stock-info"></div>
                                    </td>
                                    <td>
                                        <input name="items[{{ $index }}][requested_qty]" data-field="requested_qty"
                                            value="{{ $item['requested_qty'] ?? '' }}" type="number" step="any" min="0.00001"
                                            class="form-control text-end @error('items.' . $index . '.requested_qty') is-invalid @enderror"
                                            aria-label="Qty" required>
                                        @error('items.' . $index . '.requested_qty')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <select name="items[{{ $index }}][requested_satuan_id]" data-field="requested_satuan_id"
                                            data-selected="{{ $item['requested_satuan_id'] ?? '' }}"
                                            class="form-select select2RequestOrder item-unit @error('items.' . $index . '.requested_satuan_id') is-invalid @enderror"
                                            aria-label="Satuan" required>
                                            <option value="">Pilih bahan dahulu</option>
                                        </select>
                                        @error('items.' . $index . '.requested_satuan_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <textarea name="items[{{ $index }}][notes]" data-field="notes" rows="2"
                                            class="form-control item-notes @error('items.' . $index . '.notes') is-invalid @enderror"
                                            placeholder="Opsional" aria-label="Catatan item">{{ $item['notes'] ?? '' }}</textarea>
                                        @error('items.' . $index . '.notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <button type="button" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger remove-request-order-item"
                                            title="Hapus dari daftar" aria-label="Hapus dari daftar"><i class="fas fa-times" aria-hidden="true"></i></button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div id="requestOrderItemsEmpty" class="wh-empty">
                    <span class="wh-empty__icon" aria-hidden="true"><i class="fas fa-boxes"></i></span>
                    <p class="wh-empty__title">Belum ada bahan baku</p>
                    <p class="wh-empty__text">Pilih bahan dari kolom di atas untuk menambahkannya ke request.</p>
                </div>

                <div class="ro-form-foot">
                    <a href="{{ $backUrl }}" class="btn ob-btn ob-btn-outline">Batal</a>
                    <button type="submit" class="btn ob-btn ob-btn-primary">
                        <i class="fas fa-save" aria-hidden="true"></i> {{ $data->exists ? 'Simpan Perubahan' : 'Simpan Draft' }}
                    </button>
                </div>
            </div>
        </form>

        <template id="requestOrderItemTemplate">
            <tr class="request-item-row">
                <td>
                    <input type="hidden" name="items[__INDEX__][raw_material_id]" data-field="raw_material_id" class="item-raw-material-id">
                    <div class="wh-cell-stack">
                        <span class="wh-strong item-material-name"></span>
                        <span class="ob-cell-meta item-material-code"></span>
                    </div>
                    <div class="item-stock-info"></div>
                </td>
                <td>
                    <input name="items[__INDEX__][requested_qty]" data-field="requested_qty" type="number"
                        step="any" min="0.00001" class="form-control text-end" aria-label="Qty" required>
                </td>
                <td>
                    <select name="items[__INDEX__][requested_satuan_id]" data-field="requested_satuan_id"
                        class="form-select select2RequestOrder item-unit" aria-label="Satuan" required>
                        <option value="">Pilih bahan dahulu</option>
                    </select>
                </td>
                <td>
                    <textarea name="items[__INDEX__][notes]" data-field="notes" rows="2"
                        class="form-control item-notes" placeholder="Opsional" aria-label="Catatan item"></textarea>
                </td>
                <td>
                    <button type="button" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger remove-request-order-item"
                        title="Hapus dari daftar" aria-label="Hapus dari daftar"><i class="fas fa-times" aria-hidden="true"></i></button>
                </td>
            </tr>
        </template>
    </div>

    @push('js')
        <script>
            const unitOptionsByRawMaterial = @json($unitOptionsByRawMaterial);
            const rawMaterialStockInfo = @json($rawMaterialStockInfo);
            const rawMaterialCatalog = @json($rawMaterialCatalog);

            // Nama lokasi/bahan berasal dari data user; selalu di-escape sebelum masuk HTML
            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
                }[char]));
            }

            // Tanpa nol di belakang koma: 25000 → "25.000", 0.05 → "0,05"
            function formatQty(value) {
                return Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 5 });
            }

            function unitLabel(unit) {
                return unit.symbol ? unit.name + ' (' + unit.symbol + ')' : unit.name;
            }

            function materialSubtitle(material) {
                return (material.code || 'Tanpa kode') + (material.base_unit ? ' · Satuan dasar: ' + material.base_unit : '');
            }

            function initSelect2(element) {
                element.select2({ width: '100%' });
            }

            function destroySelect2(element) {
                if (element.hasClass('select2-hidden-accessible')) {
                    element.select2('destroy');
                }
            }

            function itemRows() {
                return $('#requestOrderItemsTable tbody tr');
            }

            function selectedMaterialIds() {
                return $('#requestOrderItemsTable tbody .item-raw-material-id')
                    .map(function () { return String($(this).val()); })
                    .get()
                    .filter(Boolean);
            }

            function refreshMaterialPickerOptions() {
                const selectedIds = new Set(selectedMaterialIds());
                const picker = $('#rawMaterialPicker');

                destroySelect2(picker);
                picker.find('option').each(function () {
                    const value = String(this.value || '');
                    $(this).prop('disabled', value !== '' && selectedIds.has(value));
                });
                picker.val('');
                initSelect2(picker);
            }

            function refreshUnitSelect(row) {
                const materialId = row.find('.item-raw-material-id').val();
                const unitSelect = row.find('.item-unit');
                const options = unitOptionsByRawMaterial[materialId] || [];
                const selected = unitSelect.data('selected') || '';

                destroySelect2(unitSelect);
                unitSelect.empty();

                if (!materialId) {
                    unitSelect.append(new Option('Pilih bahan dahulu', '', false, false));
                    unitSelect.prop('disabled', true);
                } else if (options.length === 0) {
                    unitSelect.append(new Option('Tidak ada satuan tersedia', '', false, false));
                    unitSelect.prop('disabled', true);
                } else {
                    unitSelect.append(new Option('Pilih satuan', '', false, false));
                    options.forEach(function (unit) {
                        unitSelect.append(new Option(unitLabel(unit), unit.id, false, String(selected) === String(unit.id)));
                    });
                    unitSelect.prop('disabled', false);
                }

                unitSelect.data('selected', '');
                initSelect2(unitSelect);
            }

            function refreshStockInfo(row) {
                const materialId = row.find('.item-raw-material-id').val();
                const fulfillmentInventoryId = $('#fulfillmentInventory').val();
                const info = rawMaterialStockInfo[materialId];
                const container = row.find('.item-stock-info');

                if (!materialId || !info) {
                    container.html('');
                    return;
                }

                const unit = escapeHtml(info.base_unit || '');
                const locations = info.locations || [];
                const fulfillmentStock = fulfillmentInventoryId
                    ? locations.find((location) => String(location.inventory_id) === String(fulfillmentInventoryId))
                    : null;

                let focus = '';
                if (fulfillmentInventoryId) {
                    focus = fulfillmentStock
                        ? `<div class="ro-stock__focus">Stok bebas di pemenuh: <strong>${formatQty(fulfillmentStock.qty_free)} ${unit}</strong></div>`
                        : '<div class="ro-stock__focus ro-stock__focus--empty">Belum ada stok di lokasi pemenuh</div>';
                }

                const others = locations.slice(0, 4).map((location) => `<li>
                        <span>${escapeHtml(location.inventory_name)}</span>
                        <strong>${formatQty(location.qty_free)} ${unit}</strong>
                    </li>`).join('');
                const more = locations.length > 4 ? `<li class="ro-stock__more">+${locations.length - 4} lokasi lain</li>` : '';

                container.html(`<div class="ro-stock">
                        ${focus}
                        ${others ? `<ul class="ro-stock__list">${others}${more}</ul>` : '<div class="ro-stock__empty">Belum ada stok tercatat untuk bahan ini.</div>'}
                    </div>`);
            }

            function refreshAllStockInfo() {
                itemRows().each(function () { refreshStockInfo($(this)); });
            }

            function updateEmptyState() {
                const count = itemRows().length;

                $('#requestOrderItemsTable').closest('.ob-table-scroll').toggle(count > 0);
                $('#requestOrderItemsEmpty').toggle(count === 0);
                $('#clearRequestOrderItems').prop('disabled', count === 0);
                $('#requestOrderItemsCount').text(count);
            }

            function reindexRequestOrderItems() {
                itemRows().each(function (index) {
                    $(this).find('[data-field]').each(function () {
                        $(this).attr('name', 'items[' + index + '][' + $(this).data('field') + ']');
                    });
                });
            }

            function removeRows(rows) {
                rows.find('.select2RequestOrder').each(function () { destroySelect2($(this)); });
                rows.remove();
                reindexRequestOrderItems();
                updateEmptyState();
                refreshMaterialPickerOptions();
            }

            function createRequestOrderItemRow(materialId) {
                const material = rawMaterialCatalog[materialId];

                if (!material) {
                    return null;
                }

                const index = itemRows().length;
                const row = $($('#requestOrderItemTemplate').html().replaceAll('__INDEX__', index));

                row.find('.item-raw-material-id').val(material.id);
                row.find('.item-material-name').text(material.name || '-');
                row.find('.item-material-code').text(materialSubtitle(material));

                $('#requestOrderItemsTable tbody').append(row);
                refreshUnitSelect(row);
                refreshStockInfo(row);
                updateEmptyState();
                refreshMaterialPickerOptions();

                return row;
            }

            $(function () {
                $('#requester_inventory_id, #fulfillmentInventory').each(function () { initSelect2($(this)); });

                itemRows().each(function () {
                    refreshUnitSelect($(this));
                    refreshStockInfo($(this));
                });

                updateEmptyState();
                refreshMaterialPickerOptions();

                $('#rawMaterialPicker').on('change', function () {
                    const materialId = String($(this).val() || '');

                    if (!materialId) {
                        return;
                    }

                    if (selectedMaterialIds().includes(materialId)) {
                        showToast('warning', 'Bahan baku ini sudah ada di daftar.');
                        refreshMaterialPickerOptions();
                        return;
                    }

                    createRequestOrderItemRow(materialId);
                });

                $('#clearRequestOrderItems').on('click', function () {
                    const count = itemRows().length;
                    if (count === 0) return;

                    Swal.fire({
                        title: 'Kosongkan daftar bahan?',
                        text: count + ' bahan beserta qty dan catatannya akan dihapus dari form ini.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, kosongkan',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            removeRows(itemRows());
                        }
                    });
                });

                $('#fulfillmentInventory').on('change', refreshAllStockInfo);

                $('#requestOrderItemsTable').on('click', '.remove-request-order-item', function () {
                    removeRows($(this).closest('tr'));
                });

                $('#requestOrderForm').on('submit', function (event) {
                    if (itemRows().length === 0) {
                        event.preventDefault();
                        showToast('error', 'Minimal satu bahan baku wajib dipilih.');
                    }
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .ro-card { margin-bottom: 1.25rem; }
            .ro-notice { margin-bottom: 1rem; }
            .ro-notice--error { border-color: var(--ob-danger); color: var(--ob-danger); background: var(--ob-danger-soft); }
            .ro-notice--inset { margin: 0 1.25rem 1rem; }

            .ro-picker { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .75rem; border-bottom: 1px solid var(--ob-border); }
            .ro-picker__field { flex: 1 1 320px; min-width: 0; }

            .ro-items td { vertical-align: top; }
            .ro-items .ro-col-qty { width: 140px; }
            .ro-items .ro-col-unit { width: 200px; }
            .ro-items .ro-col-notes { width: 260px; }
            .ro-items td:first-child { min-width: 280px; }
            .ro-items td:nth-child(2) { min-width: 120px; }
            .ro-items td:nth-child(3) { min-width: 180px; }
            .ro-items td:nth-child(4) { min-width: 220px; }
            .ro-items .select2-container { width: 100% !important; }
            .ro-items .item-notes { resize: vertical; min-height: 38px; }

            .ro-stock { margin-top: .5rem; padding: .5rem .65rem; border: 1px solid var(--ob-border); border-radius: var(--ob-radius-sm); background: var(--ob-surface-2); font-size: .76rem; color: var(--ob-text-2); }
            .ro-stock__focus { margin-bottom: .35rem; }
            .ro-stock__focus strong { color: var(--ob-text); }
            .ro-stock__focus--empty { color: var(--ob-danger); font-weight: 600; }
            .ro-stock__list { list-style: none; margin: 0; padding: 0; display: grid; gap: .2rem; }
            .ro-stock__list li { display: flex; justify-content: space-between; gap: 1rem; }
            .ro-stock__list strong { color: var(--ob-text); font-variant-numeric: tabular-nums; white-space: nowrap; }
            .ro-stock__more, .ro-stock__empty { color: var(--ob-muted); }

            .ro-form-foot { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .5rem; padding: 1rem 1.25rem; border-top: 1px solid var(--ob-border); }
        </style>
    @endpush
@endsection
