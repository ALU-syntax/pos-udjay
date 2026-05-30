@extends('layouts.app')
@section('content')
    @php
        $formItems = old('items');

        if (!$formItems) {
            $formItems = $data->exists
                ? $data->items->map(fn ($item) => [
                    'raw_material_id' => $item->raw_material_id,
                    'qty_requested' => $item->qty_requested,
                    'unit_id' => $item->unit_id,
                    'notes' => $item->notes,
                ])->values()->all()
                : [];
        }

        $formItems = collect($formItems ?: [])->filter(fn ($item) => filled($item['raw_material_id'] ?? null))->values()->all();
        $rawMaterialsById = $rawMaterials->keyBy('id');
        $rawMaterialCatalog = $rawMaterials->mapWithKeys(fn ($rawMaterial) => [
            $rawMaterial->id => [
                'id' => $rawMaterial->id,
                'name' => $rawMaterial->name,
                'code' => $rawMaterial->code,
                'base_unit' => $rawMaterial->baseUnit?->symbol ?: $rawMaterial->baseUnit?->name,
            ],
        ])->all();
    @endphp

    <div class="main-content request-order-form-page">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h2 class="h4 mb-1 font-weight-bold">
                    <i class="fa fa-clipboard-list me-2"></i>{{ $title }}
                </h2>
                <p class="text-muted small mb-0">Susun kebutuhan bahan baku sebagai draft sebelum disubmit untuk review.</p>
            </div>
            <a href="{{ $data->exists ? route('warehouse/request-order/detail', $data->id) : route('warehouse/request-order') }}"
                class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-arrow-left me-1"></i>Kembali
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                Ada data yang belum sesuai. Periksa kembali field yang ditandai.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ $action }}" method="POST" id="requestOrderForm">
            @csrf
            @if ($data->exists)
                @method('put')
            @endif

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Informasi Request</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Nomor Request</label>
                                <input name="request_number" value="{{ old('request_number', $data->request_number) }}"
                                    type="text" class="form-control @error('request_number') is-invalid @enderror"
                                    maxlength="100" placeholder="Kosongkan untuk auto-generate">
                                @error('request_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Tanggal Dibutuhkan</label>
                                <input name="needed_at" value="{{ old('needed_at', optional($data->needed_at)->format('Y-m-d')) }}"
                                    type="date" class="form-control @error('needed_at') is-invalid @enderror">
                                @error('needed_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Lokasi Pemohon <span class="text-danger">*</span></label>
                                <select name="requester_inventory_id"
                                    class="form-select select2RequestOrder @error('requester_inventory_id') is-invalid @enderror"
                                    required>
                                    <option value="">Pilih lokasi pemohon</option>
                                    @foreach ($inventories as $inventory)
                                        <option value="{{ $inventory->id }}" @if (old('requester_inventory_id', $data->requester_inventory_id) == $inventory->id) selected @endif>
                                            {{ $inventory->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('requester_inventory_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Inventory Pemenuhan</label>
                                <select name="fulfillment_inventory_id" id="fulfillmentInventory"
                                    class="form-select select2RequestOrder @error('fulfillment_inventory_id') is-invalid @enderror">
                                    <option value="">Belum ditentukan</option>
                                    @foreach ($inventories as $inventory)
                                        <option value="{{ $inventory->id }}" @if (old('fulfillment_inventory_id', $data->fulfillment_inventory_id) == $inventory->id) selected @endif>
                                            {{ $inventory->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('fulfillment_inventory_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label>Catatan</label>
                                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3"
                                    placeholder="Catatan tambahan untuk request order">{{ old('notes', $data->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Pilih Bahan Baku</h5>
                    <small class="text-muted">Pilih bahan satu per satu. Bahan yang sudah dipilih akan dikunci agar tidak duplikat.</small>
                </div>
                <div class="card-body">
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-lg-9">
                            <label class="form-label">Bahan Baku</label>
                            <select id="rawMaterialPicker" class="form-select select2RequestOrder">
                                <option value="">Pilih bahan baku</option>
                                @foreach ($rawMaterials as $rawMaterial)
                                    <option value="{{ $rawMaterial->id }}">
                                        {{ $rawMaterial->name }}{{ $rawMaterial->code ? ' - ' . $rawMaterial->code : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <button type="button" class="btn btn-outline-secondary w-100" id="resetRawMaterialPicker">
                                <i class="fa fa-undo me-1"></i>Reset
                            </button>
                        </div>
                    </div>

                    @error('items')
                        <div class="alert alert-danger py-2">{{ $message }}</div>
                    @enderror

                    <div class="table-responsive request-order-items-wrap">
                        <table class="table table-sm align-middle mb-0" id="requestOrderItemsTable">
                            <thead>
                                <tr>
                                    <th style="min-width: 330px;">Bahan Baku</th>
                                    <th style="width: 150px;">Qty</th>
                                    <th style="min-width: 190px;">Satuan</th>
                                    <th style="min-width: 260px;">Catatan</th>
                                    <th style="width: 54px;"></th>
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
                                                data-field="raw_material_id" class="item-raw-material-id"
                                                value="{{ $rawMaterialId }}">
                                            <div class="selected-material-display">
                                                <div class="fw-semibold item-material-name">{{ optional($rawMaterial)->name ?? '-' }}</div>
                                                <small class="text-muted item-material-code">
                                                    {{ optional($rawMaterial)->code ?? 'Tanpa kode' }}
                                                    @if ($rawMaterial?->baseUnit)
                                                        - Base: {{ $rawMaterial->baseUnit->symbol ?: $rawMaterial->baseUnit->name }}
                                                    @endif
                                                </small>
                                            </div>
                                            @error('items.' . $index . '.raw_material_id')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <div class="item-stock-info mt-2"></div>
                                        </td>
                                        <td>
                                            <input name="items[{{ $index }}][qty_requested]" data-field="qty_requested"
                                                value="{{ $item['qty_requested'] ?? '' }}" type="number" step="0.00001"
                                                min="0.00001"
                                                class="form-control form-control-sm text-end @error('items.' . $index . '.qty_requested') is-invalid @enderror"
                                                required>
                                            @error('items.' . $index . '.qty_requested')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <select name="items[{{ $index }}][unit_id]" data-field="unit_id"
                                                data-selected="{{ $item['unit_id'] ?? '' }}"
                                                class="form-select form-select-sm select2RequestOrder item-unit @error('items.' . $index . '.unit_id') is-invalid @enderror"
                                                required>
                                                <option value="">Pilih bahan dahulu</option>
                                            </select>
                                            @error('items.' . $index . '.unit_id')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <textarea name="items[{{ $index }}][notes]" data-field="notes" rows="2"
                                                class="form-control form-control-sm item-notes @error('items.' . $index . '.notes') is-invalid @enderror"
                                                placeholder="Opsional">{{ $item['notes'] ?? '' }}</textarea>
                                            @error('items.' . $index . '.notes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm remove-request-order-item">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div id="requestOrderItemsEmpty" class="text-center text-muted py-4">
                            Belum ada bahan baku terpilih.
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
                        <a href="{{ $data->exists ? route('warehouse/request-order/detail', $data->id) : route('warehouse/request-order') }}"
                            class="btn btn-outline-secondary">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save me-1"></i>{{ $data->exists ? 'Update Draft' : 'Simpan Draft' }}
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <template id="requestOrderItemTemplate">
            <tr class="request-item-row">
                <td>
                    <input type="hidden" name="items[__INDEX__][raw_material_id]"
                        data-field="raw_material_id" class="item-raw-material-id">
                    <div class="selected-material-display">
                        <div class="fw-semibold item-material-name"></div>
                        <small class="text-muted item-material-code"></small>
                    </div>
                    <div class="item-stock-info mt-2"></div>
                </td>
                <td>
                    <input name="items[__INDEX__][qty_requested]" data-field="qty_requested" type="number"
                        step="0.00001" min="0.00001" class="form-control form-control-sm text-end" required>
                </td>
                <td>
                    <select name="items[__INDEX__][unit_id]" data-field="unit_id"
                        class="form-select form-select-sm select2RequestOrder item-unit" required>
                        <option value="">Pilih bahan dahulu</option>
                    </select>
                </td>
                <td>
                    <textarea name="items[__INDEX__][notes]" data-field="notes" rows="2"
                        class="form-control form-control-sm item-notes" placeholder="Opsional"></textarea>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-request-order-item">
                        <i class="fa fa-times"></i>
                    </button>
                </td>
            </tr>
        </template>
    </div>

    @push('js')
        <script>
            const unitOptionsByRawMaterial = @json($unitOptionsByRawMaterial);
            const rawMaterialStockInfo = @json($rawMaterialStockInfo);
            const rawMaterialCatalog = @json($rawMaterialCatalog);

            function formatQty(value) {
                return Number(value || 0).toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 5
                });
            }

            function unitLabel(unit) {
                return unit.symbol ? unit.name + ' (' + unit.symbol + ')' : unit.name;
            }

            function materialSubtitle(material) {
                return material.code || 'Tanpa kode';
            }

            function initRequestOrderSelect2(context) {
                $(context).find('.select2RequestOrder').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        return;
                    }

                    $(this).select2({
                        width: '100%'
                    });
                });
            }

            function selectedMaterialIds() {
                return $('#requestOrderItemsTable tbody .item-raw-material-id')
                    .map(function() {
                        return String($(this).val());
                    })
                    .get()
                    .filter(Boolean);
            }

            function refreshMaterialPickerOptions() {
                const selectedIds = new Set(selectedMaterialIds());
                const picker = $('#rawMaterialPicker');

                if (picker.hasClass('select2-hidden-accessible')) {
                    picker.select2('destroy');
                }

                picker.find('option').each(function() {
                    const value = String(this.value || '');
                    $(this).prop('disabled', value !== '' && selectedIds.has(value));
                });

                picker.val('');
                picker.select2({
                    width: '100%'
                });
            }

            function refreshUnitSelect(row) {
                const materialId = row.find('.item-raw-material-id').val();
                const unitSelect = row.find('.item-unit');
                const options = unitOptionsByRawMaterial[materialId] || [];
                const selected = unitSelect.data('selected') || '';

                if (unitSelect.hasClass('select2-hidden-accessible')) {
                    unitSelect.select2('destroy');
                }

                unitSelect.empty();

                if (!materialId) {
                    unitSelect.append(new Option('Pilih bahan dahulu', '', false, false));
                    unitSelect.prop('disabled', true);
                } else if (options.length === 0) {
                    unitSelect.append(new Option('Tidak ada satuan tersedia', '', false, false));
                    unitSelect.prop('disabled', true);
                } else {
                    unitSelect.append(new Option('Pilih satuan', '', false, false));
                    options.forEach(function(unit) {
                        unitSelect.append(new Option(unitLabel(unit), unit.id, false, String(selected) === String(unit.id)));
                    });
                    unitSelect.prop('disabled', false);
                }

                unitSelect.data('selected', '');
                unitSelect.select2({
                    width: '100%'
                });
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

                const baseUnit = info.base_unit || 'base';
                const fulfillmentStock = fulfillmentInventoryId
                    ? (info.locations || []).find((location) => String(location.inventory_id) === String(fulfillmentInventoryId))
                    : null;
                const locationRows = (info.locations || []).slice(0, 4).map(function(location) {
                    return `<div class="ro-stock-location">
                        <span>${location.inventory_name}</span>
                        <strong>${formatQty(location.qty_free)} ${baseUnit}</strong>
                    </div>`;
                }).join('');

                container.html(`
                    <div class="ro-stock-info">
                        ${fulfillmentInventoryId ? `
                            <div class="ro-stock-focus">
                                Inventory pemenuhan:
                                <strong>${fulfillmentStock ? formatQty(fulfillmentStock.qty_free) + ' ' + baseUnit + ' free' : 'belum ada stok'}</strong>
                            </div>
                        ` : ''}
                        ${locationRows ? `<div class="ro-stock-locations">${locationRows}</div>` : '<div class="text-muted small">Tidak ada catatan untuk Bahan ini</div>'}
                    </div>
                `);
            }

            function refreshAllStockInfo() {
                $('#requestOrderItemsTable tbody tr').each(function() {
                    refreshStockInfo($(this));
                });
            }

            function updateEmptyState() {
                const hasRows = $('#requestOrderItemsTable tbody tr').length > 0;

                $('#requestOrderItemsTable').toggle(hasRows);
                $('#requestOrderItemsEmpty').toggle(!hasRows);
            }

            function reindexRequestOrderItems() {
                $('#requestOrderItemsTable tbody tr').each(function(index) {
                    const row = $(this);
                    row.find('[data-field]').each(function() {
                        const field = $(this).data('field');
                        $(this).attr('name', 'items[' + index + '][' + field + ']');
                    });
                });
            }

            function createRequestOrderItemRow(materialId) {
                const material = rawMaterialCatalog[materialId];

                if (!material) {
                    return null;
                }

                const index = $('#requestOrderItemsTable tbody tr').length;
                const template = $('#requestOrderItemTemplate').html()
                    .replaceAll('__INDEX__', index);
                const row = $(template);

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

            $(function() {
                initRequestOrderSelect2(document);

                $('#requestOrderItemsTable tbody tr').each(function() {
                    const row = $(this);
                    refreshUnitSelect(row);
                    refreshStockInfo(row);
                });

                updateEmptyState();
                refreshMaterialPickerOptions();

                $('#rawMaterialPicker').on('change', function() {
                    const materialId = String($(this).val() || '');

                    if (!materialId) {
                        return;
                    }

                    if (selectedMaterialIds().includes(materialId)) {
                        showToast('warning', 'Bahan baku ini sudah dipilih.');
                        refreshMaterialPickerOptions();
                        return;
                    }

                    createRequestOrderItemRow(materialId);
                });

                $('#resetRawMaterialPicker').on('click', function() {
                    $('#rawMaterialPicker').val('').trigger('change');
                    $('#requestOrderItemsTable tbody tr').each(function() {
                        $(this).find('.select2RequestOrder').each(function() {
                            if ($(this).hasClass('select2-hidden-accessible')) {
                                $(this).select2('destroy');
                            }
                        });
                    });
                    $('#requestOrderItemsTable tbody').empty();
                    updateEmptyState();
                    refreshMaterialPickerOptions();
                });

                $('#fulfillmentInventory').on('change', refreshAllStockInfo);

                $('#requestOrderItemsTable').on('click', '.remove-request-order-item', function() {
                    const row = $(this).closest('tr');

                    row.find('.select2RequestOrder').each(function() {
                        if ($(this).hasClass('select2-hidden-accessible')) {
                            $(this).select2('destroy');
                        }
                    });
                    row.remove();
                    reindexRequestOrderItems();
                    updateEmptyState();
                    refreshMaterialPickerOptions();
                });

                $('#requestOrderForm').on('submit', function(e) {
                    if ($('#requestOrderItemsTable tbody tr').length === 0) {
                        e.preventDefault();
                        showToast('error', 'Minimal satu bahan baku wajib dipilih.');
                    }
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .request-order-form-page .card {
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
            }

            .request-order-items-wrap {
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
                overflow: hidden;
            }

            #requestOrderItemsTable th {
                background: #f8fafc;
                white-space: nowrap;
            }

            #requestOrderItemsTable .select2-container {
                width: 100% !important;
            }

            .selected-material-display {
                padding: 2px 0;
            }

            .ro-stock-info {
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
                background: #fbfcfe;
                padding: 8px 10px;
            }

            .ro-stock-focus strong,
            .ro-stock-location strong {
                color: #1f2937;
            }

            .ro-stock-focus {
                color: #4b5563;
                font-size: 12px;
            }

            .ro-stock-locations {
                margin-top: 6px;
                display: grid;
                gap: 4px;
                font-size: 12px;
            }

            .ro-stock-location {
                display: flex;
                justify-content: space-between;
                gap: 12px;
            }

            #requestOrderItemsTable td:nth-child(4) {
                min-width: 260px;
            }

            .item-notes {
                resize: none;
            }
        </style>
    @endpush
@endsection
