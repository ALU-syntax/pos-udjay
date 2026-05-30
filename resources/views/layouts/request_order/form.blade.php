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

        if (empty($formItems)) {
            $formItems = [[
                'raw_material_id' => null,
                'qty_requested' => null,
                'unit_id' => null,
                'notes' => null,
            ]];
        }
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
                <div class="card-header bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="mb-0">Daftar Bahan Baku</h5>
                        <small class="text-muted">Informasi stok muncul setelah bahan dipilih.</small>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addRequestOrderItem">
                        <i class="fa fa-plus me-1"></i>Tambah Item
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive request-order-items-wrap">
                        <table class="table table-sm align-middle mb-0" id="requestOrderItemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 44px;">No</th>
                                    <th style="min-width: 310px;">Bahan Baku</th>
                                    <th style="width: 150px;">Qty</th>
                                    <th style="min-width: 180px;">Satuan</th>
                                    <th style="min-width: 180px;">Catatan</th>
                                    <th style="width: 54px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($formItems as $index => $item)
                                    <tr class="request-item-row">
                                        <td class="item-number">{{ $index + 1 }}</td>
                                        <td>
                                            <select name="items[{{ $index }}][raw_material_id]" data-field="raw_material_id"
                                                class="form-select form-select-sm select2RequestOrder item-raw-material @error('items.' . $index . '.raw_material_id') is-invalid @enderror"
                                                required>
                                                <option value="">Pilih bahan</option>
                                                @foreach ($rawMaterials as $rawMaterial)
                                                    <option value="{{ $rawMaterial->id }}" @if (($item['raw_material_id'] ?? null) == $rawMaterial->id) selected @endif>
                                                        {{ $rawMaterial->name }}{{ $rawMaterial->code ? ' - ' . $rawMaterial->code : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
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
                                            <input name="items[{{ $index }}][notes]" data-field="notes"
                                                value="{{ $item['notes'] ?? '' }}" type="text"
                                                class="form-control form-control-sm @error('items.' . $index . '.notes') is-invalid @enderror"
                                                placeholder="Opsional">
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
                <td class="item-number">__NUMBER__</td>
                <td>
                    <select name="items[__INDEX__][raw_material_id]" data-field="raw_material_id"
                        class="form-select form-select-sm select2RequestOrder item-raw-material" required>
                        <option value="">Pilih bahan</option>
                        @foreach ($rawMaterials as $rawMaterial)
                            <option value="{{ $rawMaterial->id }}">
                                {{ $rawMaterial->name }}{{ $rawMaterial->code ? ' - ' . $rawMaterial->code : '' }}
                            </option>
                        @endforeach
                    </select>
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
                    <input name="items[__INDEX__][notes]" data-field="notes" type="text"
                        class="form-control form-control-sm" placeholder="Opsional">
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

            function formatQty(value) {
                return Number(value || 0).toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 5
                });
            }

            function unitLabel(unit) {
                return unit.symbol ? unit.name + ' (' + unit.symbol + ')' : unit.name;
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

            function refreshUnitSelect(row) {
                const materialId = row.find('.item-raw-material').val();
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
                const materialId = row.find('.item-raw-material').val();
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
                        <div class="ro-stock-grid">
                            <span>Base: <strong>${baseUnit}</strong></span>
                            <span>Available: <strong>${formatQty(info.total_available)} ${baseUnit}</strong></span>
                            <span>Reserved: <strong>${formatQty(info.total_reserved)} ${baseUnit}</strong></span>
                            <span>Free: <strong>${formatQty(info.total_free)} ${baseUnit}</strong></span>
                        </div>
                        ${fulfillmentInventoryId ? `
                            <div class="ro-stock-focus">
                                Inventory pemenuhan:
                                <strong>${fulfillmentStock ? formatQty(fulfillmentStock.qty_free) + ' ' + baseUnit + ' free' : 'belum ada stok'}</strong>
                            </div>
                        ` : ''}
                        ${locationRows ? `<div class="ro-stock-locations">${locationRows}</div>` : '<div class="text-muted small mt-1">Belum ada catatan stok untuk bahan ini.</div>'}
                    </div>
                `);
            }

            function refreshAllStockInfo() {
                $('#requestOrderItemsTable tbody tr').each(function() {
                    refreshStockInfo($(this));
                });
            }

            function reindexRequestOrderItems() {
                $('#requestOrderItemsTable tbody tr').each(function(index) {
                    const row = $(this);
                    row.find('.item-number').text(index + 1);
                    row.find('[data-field]').each(function() {
                        const field = $(this).data('field');
                        $(this).attr('name', 'items[' + index + '][' + field + ']');
                    });
                });
            }

            $(function() {
                initRequestOrderSelect2(document);
                $('#requestOrderItemsTable tbody tr').each(function() {
                    const row = $(this);
                    refreshUnitSelect(row);
                    refreshStockInfo(row);
                });

                $('#addRequestOrderItem').on('click', function() {
                    const index = $('#requestOrderItemsTable tbody tr').length;
                    const template = $('#requestOrderItemTemplate').html()
                        .replaceAll('__INDEX__', index)
                        .replaceAll('__NUMBER__', index + 1);
                    const row = $(template);

                    $('#requestOrderItemsTable tbody').append(row);
                    initRequestOrderSelect2(row);
                    refreshUnitSelect(row);
                    refreshStockInfo(row);
                });

                $('#requestOrderItemsTable').on('change', '.item-raw-material', function() {
                    const row = $(this).closest('tr');
                    refreshUnitSelect(row);
                    refreshStockInfo(row);
                });

                $('#fulfillmentInventory').on('change', refreshAllStockInfo);

                $('#requestOrderItemsTable').on('click', '.remove-request-order-item', function() {
                    const rows = $('#requestOrderItemsTable tbody tr');
                    const row = $(this).closest('tr');

                    if (rows.length === 1) {
                        row.find('input').val('');
                        row.find('.item-raw-material').val('').trigger('change');
                        row.find('.item-unit').val('').trigger('change');
                        return;
                    }

                    row.find('.select2RequestOrder').each(function() {
                        if ($(this).hasClass('select2-hidden-accessible')) {
                            $(this).select2('destroy');
                        }
                    });
                    row.remove();
                    reindexRequestOrderItems();
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
            }

            #requestOrderItemsTable th {
                background: #f8fafc;
                white-space: nowrap;
            }

            #requestOrderItemsTable .select2-container {
                width: 100% !important;
            }

            .ro-stock-info {
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
                background: #fbfcfe;
                padding: 8px 10px;
            }

            .ro-stock-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 4px 10px;
                color: #667085;
                font-size: 12px;
            }

            .ro-stock-grid strong,
            .ro-stock-focus strong,
            .ro-stock-location strong {
                color: #1f2937;
            }

            .ro-stock-focus {
                margin-top: 7px;
                padding-top: 7px;
                border-top: 1px solid rgba(18, 38, 63, 0.08);
                color: #4b5563;
                font-size: 12px;
            }

            .ro-stock-locations {
                margin-top: 7px;
                display: grid;
                gap: 4px;
                font-size: 12px;
            }

            .ro-stock-location {
                display: flex;
                justify-content: space-between;
                gap: 12px;
            }

            @media (max-width: 767.98px) {
                .ro-stock-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    @endpush
@endsection
