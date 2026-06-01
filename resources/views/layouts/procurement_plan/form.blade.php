@extends('layouts.app')
@section('content')
    @php
        $selectedSources = collect(old('selected_sources', []))->map(fn ($id) => (int) $id)->all();
        $formatQty = fn ($value) => number_format((float) $value, 1, ',', '.');
        $formatMoney = fn ($value) => $value === null ? '-' : 'Rp ' . number_format((float) $value, 2, ',', '.');
    @endphp

    <div class="main-content procurement-plan-form-page">
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ $errors->first() }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h2 class="h4 mb-1 font-weight-bold">
                    <i class="fa fa-clipboard-check me-2"></i>{{ $title }}
                </h2>
                <p class="text-muted small mb-0">Pilih bahan baku dari Request Order approved, lalu tentukan supplier untuk setiap bahan.</p>
            </div>
            <a href="{{ route('warehouse/procurement-plan') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-arrow-left me-1"></i>Kembali
            </a>
        </div>

        <form action="{{ $action }}" method="POST" id="procurementPlanForm">
            @csrf

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Informasi Plan</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Nomor Plan</label>
                            <input type="text" name="plan_number"
                                class="form-control @error('plan_number') is-invalid @enderror"
                                value="{{ old('plan_number', $planNumber) }}" placeholder="Auto jika dikosongkan">
                            @error('plan_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Lokasi Planning <span class="text-danger">*</span></label>
                            <select name="planning_location_id"
                                class="form-select @error('planning_location_id') is-invalid @enderror" required>
                                <option value="">Pilih lokasi planning</option>
                                @foreach ($inventories as $inventory)
                                    <option value="{{ $inventory->id }}" @selected((int) old('planning_location_id') === (int) $inventory->id)>
                                        {{ $inventory->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('planning_location_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ringkasan Pilihan</label>
                            <div class="pp-selection-summary">
                                <span><strong id="selectedSourceCount">0</strong> request</span>
                                <span><strong id="selectedMaterialCount">0</strong> bahan</span>
                                <span><strong id="selectedAllocationTotal">0,0</strong> base</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror"
                                placeholder="Catatan procurement plan">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="container ps-0">
                        <div class="row">
                            <h5 class="mb-0">Item Request Order Approved</h5>
                            <small class="text-muted">Parent row adalah bahan baku. Buka detail untuk memilih request dari masing-masing pemohon.</small>
                        </div>
                        <div class="row">
                            <div class="col-8">
                                <input type="search" id="sourceSearch" class="form-control form-control-sm" placeholder="Cari bahan/request">
                            </div>
                            <div class="col-4 d-flex flex-row-reverse">
                                <button type="button" class="btn btn-outline-primary btn-sm ms-3" id="selectAllSources">
                                    <i class="fa fa-check-square me-1"></i>Pilih Semua
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="clearAllSources">
                                    <i class="fa fa-times-circle me-1"></i>Hapus Semua Pilihan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="pp-source-scroll">
                        <table class="table table-hover table-sm mb-0 align-middle pp-source-table" id="approvedRequestItemsTable">
                            <thead>
                                <tr>
                                    <th class="text-center">Pilih</th>
                                    <th>Bahan Baku</th>
                                    <th class="text-end">Total Base</th>
                                    <th>Pilih Supplier</th>
                                    <th class="text-end">Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sourceGroups as $group)
                                    @php
                                        $groupId = (int) $group->raw_material_id;
                                        $groupSourceIds = $group->items->pluck('id')->map(fn ($id) => (int) $id)->all();
                                        $isGroupSelected = count(array_intersect($groupSourceIds, $selectedSources)) > 0;
                                        $supplierOptions = $supplierOptionsByRawMaterial[$groupId] ?? [];
                                        $defaultSupplierId = collect($supplierOptions)->firstWhere('is_preferred', true)['id'] ?? ($supplierOptions[0]['id'] ?? null);
                                        $supplierField = 'supplier_raw_materials.' . $groupId;
                                        $selectedSupplierId = (int) old($supplierField, $defaultSupplierId);
                                        $selectedSupplierOption = collect($supplierOptions)->firstWhere('id', $selectedSupplierId);
                                        $baseUnit = $group->base_satuan_name
                                            ?: optional(optional($group->raw_material)->baseUnit)->symbol
                                            ?: optional(optional($group->raw_material)->baseUnit)->name;
                                        $groupSearchText = strtolower(
                                            ($group->raw_material?->name ?? '') . ' ' .
                                            ($group->raw_material?->code ?? '') . ' ' .
                                            $group->items->map(fn ($item) => ($item->rawMaterialRequest?->request_number ?? '') . ' ' . ($item->rawMaterialRequest?->requesterInventory?->name ?? ''))->implode(' ')
                                        );
                                    @endphp
                                    <tr class="pp-group-row" data-group-id="{{ $groupId }}" data-collapse-target="#sourceGroup{{ $groupId }}" data-search-text="{{ $groupSearchText }}">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input group-checkbox" data-group-id="{{ $groupId }}" @checked($isGroupSelected)>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-start gap-2">
                                                <button class="btn btn-outline-secondary btn-sm pp-collapse-toggle" type="button"
                                                    data-bs-target="#sourceGroup{{ $groupId }}"
                                                    aria-expanded="false" aria-controls="sourceGroup{{ $groupId }}">
                                                    <i class="fa fa-chevron-down"></i>
                                                </button>
                                                <div>
                                                    <div class="fw-semibold">{{ optional($group->raw_material)->name ?? '-' }}</div>
                                                    <small class="text-muted">
                                                        {{ optional($group->raw_material)->code ?? 'Tanpa kode' }}{{ $baseUnit ? ' · Base: ' . $baseUnit : '' }}
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-semibold">{{ $formatQty($group->total_remaining_base) }}</div>
                                            <small class="text-muted">Approved {{ $formatQty($group->total_approved_base) }}</small>
                                        </td>
                                        <td>
                                            <select name="supplier_raw_materials[{{ $groupId }}]"
                                                class="form-select form-select-sm supplier-select @error($supplierField) is-invalid @enderror"
                                                data-group-id="{{ $groupId }}" @disabled(empty($supplierOptions))>
                                                @if (empty($supplierOptions))
                                                    <option value="">Tidak ada supplier</option>
                                                @else
                                                    @foreach ($supplierOptions as $supplierOption)
                                                        <option value="{{ $supplierOption['id'] }}"
                                                            data-price="{{ $supplierOption['current_price'] }}"
                                                            data-unit="{{ $supplierOption['purchase_unit'] }}"
                                                            @selected((int) $supplierOption['id'] === $selectedSupplierId)>
                                                            {{ $supplierOption['supplier_name'] }}
                                                            @if ($supplierOption['purchase_unit'])
                                                                · {{ $supplierOption['purchase_unit'] }}
                                                            @endif
                                                            @if ($supplierOption['is_preferred'])
                                                                · Preferred
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @error($supplierField)
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-semibold group-price" data-group-id="{{ $groupId }}">
                                                {{ $formatMoney($selectedSupplierOption['current_price'] ?? null) }}
                                            </div>
                                            <small class="text-muted group-price-unit" data-group-id="{{ $groupId }}">
                                                {{ $selectedSupplierOption['purchase_unit'] ?? '' }}
                                            </small>
                                        </td>
                                    </tr>
                                    <tr class="pp-detail-row" data-group-id="{{ $groupId }}" data-search-text="{{ $groupSearchText }}">
                                        <td colspan="5" class="p-0 border-top-0">
                                            <div class="pp-detail-panel" id="sourceGroup{{ $groupId }}">
                                                <div class="pp-child-wrap">
                                                    <table class="table table-sm mb-0 align-middle pp-child-table">
                                                        <thead>
                                                            <tr>
                                                                <th class="text-center">Pilih</th>
                                                                <th>Pemohon</th>
                                                                <th>Request Order</th>
                                                                <th class="text-end">Approved Base</th>
                                                                <th class="text-end">Harga</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($group->items as $sourceItem)
                                                                @php
                                                                    $sourceId = (int) $sourceItem->id;
                                                                    $isSelected = in_array($sourceId, $selectedSources, true);
                                                                    $requestOrder = $sourceItem->rawMaterialRequest;
                                                                @endphp
                                                                <tr class="pp-child-source-row"
                                                                    data-group-id="{{ $groupId }}"
                                                                    data-search-text="{{ strtolower(($requestOrder?->request_number ?? '') . ' ' . ($requestOrder?->requesterInventory?->name ?? '') . ' ' . ($sourceItem->rawMaterial?->name ?? '')) }}">
                                                                    <td class="text-center">
                                                                        <input type="checkbox" name="selected_sources[]" value="{{ $sourceId }}"
                                                                            class="form-check-input child-checkbox"
                                                                            data-group-id="{{ $groupId }}"
                                                                            data-qty="{{ $sourceItem->remaining_qty_base }}"
                                                                            @checked($isSelected)>
                                                                    </td>
                                                                    <td>{{ optional($requestOrder?->requesterInventory)->name ?? '-' }}</td>
                                                                    <td>
                                                                        <div class="fw-semibold">{{ $requestOrder?->request_number ?? '-' }}</div>
                                                                        <small class="text-muted">{{ optional($requestOrder?->needed_at)->format('d M Y') ?? '-' }}</small>
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <div>{{ $formatQty($sourceItem->remaining_qty_base) }}</div>
                                                                        @if ((float) $sourceItem->allocated_qty_base > 0)
                                                                            <small class="text-muted">Allocated {{ $formatQty($sourceItem->allocated_qty_base) }}</small>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <span class="child-price" data-group-id="{{ $groupId }}">
                                                                            {{ $formatMoney($selectedSupplierOption['current_price'] ?? null) }}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            Belum ada item Request Order approved yang bisa dipilih.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex flex-wrap justify-content-end gap-2">
                    <a href="{{ route('warehouse/procurement-plan') }}" class="btn btn-outline-secondary">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary" @disabled($sourceGroups->isEmpty())>
                        <i class="fa fa-save me-1"></i>Simpan Draft
                    </button>
                </div>
            </div>
        </form>
    </div>

    @push('js')
        <script>
            $(function() {
                function formatQty(value) {
                    return Number(value || 0).toLocaleString('id-ID', {
                        minimumFractionDigits: 1,
                        maximumFractionDigits: 1
                    });
                }

                function formatMoney(value) {
                    if (value === undefined || value === null || value === '') {
                        return '-';
                    }

                    return 'Rp ' + Number(value || 0).toLocaleString('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }

                function groupChildren(groupId) {
                    return $('.child-checkbox[data-group-id="' + groupId + '"]');
                }

                function updateGroupCheckbox(groupId) {
                    const children = groupChildren(groupId);
                    const checkedChildren = children.filter(':checked');
                    const parent = $('.group-checkbox[data-group-id="' + groupId + '"]');

                    parent.prop('checked', children.length > 0 && checkedChildren.length === children.length);
                    parent.prop('indeterminate', checkedChildren.length > 0 && checkedChildren.length < children.length);
                }

                function updateGroupPrice(groupId) {
                    const select = $('.supplier-select[data-group-id="' + groupId + '"]');
                    const selectedOption = select.find('option:selected');
                    const price = selectedOption.data('price');
                    const unit = selectedOption.data('unit') || '';

                    $('.group-price[data-group-id="' + groupId + '"]').text(formatMoney(price));
                    $('.group-price-unit[data-group-id="' + groupId + '"]').text(unit);
                    $('.child-price[data-group-id="' + groupId + '"]').text(formatMoney(price));
                }

                function updateSelectionSummary() {
                    const checkedChildren = $('.child-checkbox:checked');
                    const selectedGroups = new Set();
                    let selectedTotal = 0;

                    checkedChildren.each(function() {
                        selectedGroups.add($(this).data('group-id'));
                        selectedTotal += Number($(this).data('qty') || 0);
                    });

                    $('#selectedSourceCount').text(checkedChildren.length);
                    $('#selectedMaterialCount').text(selectedGroups.size);
                    $('#selectedAllocationTotal').text(formatQty(selectedTotal));
                }

                function updateAllGroups() {
                    $('.group-checkbox').each(function() {
                        const groupId = $(this).data('group-id');

                        updateGroupCheckbox(groupId);
                        updateGroupPrice(groupId);
                    });

                    updateSelectionSummary();
                }

                $('.group-checkbox').on('change', function() {
                    const groupId = $(this).data('group-id');
                    groupChildren(groupId).prop('checked', $(this).is(':checked'));

                    updateGroupCheckbox(groupId);
                    updateSelectionSummary();
                });

                $('.child-checkbox').on('change', function() {
                    const groupId = $(this).data('group-id');

                    updateGroupCheckbox(groupId);
                    updateSelectionSummary();
                });

                $('.supplier-select').on('change', function() {
                    updateGroupPrice($(this).data('group-id'));
                });

                function setGroupOpen(groupId, shouldOpen) {
                    const group = $('.pp-group-row[data-group-id="' + groupId + '"]');
                    const detail = $('.pp-detail-row[data-group-id="' + groupId + '"]');
                    const toggle = group.find('.pp-collapse-toggle');

                    group.toggleClass('is-expanded', shouldOpen);
                    detail.toggleClass('is-open', shouldOpen);
                    toggle
                        .toggleClass('is-expanded', shouldOpen)
                        .attr('aria-expanded', shouldOpen ? 'true' : 'false');
                }

                function toggleGroup(groupId) {
                    const detail = $('.pp-detail-row[data-group-id="' + groupId + '"]');

                    setGroupOpen(groupId, !detail.hasClass('is-open'));
                }

                $('.pp-collapse-toggle').on('click', function(e) {
                    e.stopPropagation();
                    toggleGroup($(this).closest('.pp-group-row').data('group-id'));
                });

                $('.pp-group-row').on('click', function(e) {
                    if ($(e.target).closest('button, input, select, textarea, label, a, .select2-container').length) {
                        return;
                    }

                    toggleGroup($(this).data('group-id'));
                });

                $('#sourceSearch').on('keyup search', function() {
                    const keyword = this.value.toLowerCase();

                    $('.pp-group-row').each(function() {
                        const group = $(this);
                        const groupId = group.data('group-id');
                        const detail = $('.pp-detail-row[data-group-id="' + groupId + '"]');
                        const groupText = String(group.data('search-text') || '');
                        let childMatched = false;

                        detail.find('.pp-child-source-row').each(function() {
                            const child = $(this);
                            const matched = String(child.data('search-text') || '').includes(keyword);
                            child.toggle(matched || groupText.includes(keyword));
                            childMatched = childMatched || matched;
                        });

                        const visible = keyword === '' || groupText.includes(keyword) || childMatched;
                        group.toggle(visible);
                        detail.toggle(visible);
                    });
                });

                $('#selectAllSources').on('click', function() {
                    $('.child-checkbox').prop('checked', true);
                    $('.group-checkbox').each(function() {
                        updateGroupCheckbox($(this).data('group-id'));
                    });

                    updateSelectionSummary();
                });

                $('#clearAllSources').on('click', function() {
                    $('.child-checkbox').prop('checked', false);
                    $('.group-checkbox').each(function() {
                        updateGroupCheckbox($(this).data('group-id'));
                    });

                    updateSelectionSummary();
                });

                $('#procurementPlanForm').on('submit', function(e) {
                    if ($('.child-checkbox:checked').length === 0) {
                        e.preventDefault();
                        showToast('error', 'Pilih minimal satu request dari item Request Order approved.');
                        return;
                    }

                    let invalidSupplier = false;

                    $('.child-checkbox:checked').each(function() {
                        const groupId = $(this).data('group-id');
                        const select = $('.supplier-select[data-group-id="' + groupId + '"]');

                        if (!select.val()) {
                            invalidSupplier = true;
                        }
                    });

                    if (invalidSupplier) {
                        e.preventDefault();
                        showToast('error', 'Pilih supplier untuk setiap bahan baku yang dipilih.');
                    }
                });

                updateAllGroups();
            });
        </script>
    @endpush

    @push('css')
        <style>
            .procurement-plan-form-page .card {
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
            }

            .pp-selection-summary {
                display: flex;
                align-items: center;
                gap: 12px;
                min-height: 40px;
                padding: 8px 12px;
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
                background: #fbfcfe;
                flex-wrap: wrap;
            }

            .pp-source-scroll {
                overflow-x: auto;
                padding: 8px 0;
            }

            .pp-source-table {
                min-width: 1080px;
            }

            .pp-source-table th,
            .pp-source-table td {
                white-space: nowrap;
            }

            .pp-source-table > :not(caption) > * > * {
                padding: 0.55rem 0.75rem;
            }

            .pp-group-row {
                cursor: pointer;
                transition: background-color 0.18s ease, box-shadow 0.18s ease;
            }

            .pp-group-row:hover td,
            .pp-group-row.is-expanded td {
                background: #f8fafc;
            }

            .pp-group-row.is-expanded td {
                box-shadow: inset 0 1px 0 rgba(18, 38, 63, 0.06), inset 0 -1px 0 rgba(18, 38, 63, 0.06);
            }

            .pp-source-table td:nth-child(2) {
                white-space: normal;
                min-width: 280px;
                max-width: 420px;
                overflow-wrap: anywhere;
            }

            .pp-source-table td:nth-child(4) {
                min-width: 260px;
            }

            .pp-collapse-toggle {
                width: 32px;
                height: 32px;
                padding: 0;
                flex: 0 0 32px;
            }

            .pp-collapse-toggle i {
                transition: transform 0.18s ease;
            }

            .pp-collapse-toggle.is-expanded i,
            .pp-collapse-toggle[aria-expanded="true"] i {
                transform: rotate(180deg);
            }

            .pp-child-wrap {
                padding: 8px 14px 12px 56px;
                background: linear-gradient(180deg, #f8fafc 0%, #fbfcfe 100%);
            }

            .pp-detail-row > td {
                background: #fbfcfe;
            }

            .pp-detail-panel {
                max-height: 0;
                overflow: hidden;
                opacity: 0;
                transform: translateY(-4px);
                transition: max-height 0.16s ease, opacity 0.12s ease, transform 0.16s ease;
                will-change: max-height, opacity, transform;
                contain: layout paint;
            }

            .pp-detail-row.is-open .pp-detail-panel {
                max-height: min(58vh, 620px);
                opacity: 1;
                transform: translateY(0);
            }

            .pp-detail-row.is-open .pp-child-wrap {
                max-height: min(58vh, 620px);
                overflow-y: auto;
            }

            .pp-child-table {
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
                background: #fff;
                overflow: hidden;
            }

            .pp-child-table th,
            .pp-child-table td {
                white-space: nowrap;
                padding: 0.45rem 0.65rem;
            }

            .pp-child-table thead th {
                background: #f3f6fb;
                color: #4b5563;
                font-size: 0.78rem;
                font-weight: 700;
            }

            .pp-child-table td:nth-child(2),
            .pp-child-table td:nth-child(3) {
                white-space: normal;
                min-width: 220px;
                max-width: 360px;
                overflow-wrap: anywhere;
            }

            @media (max-width: 768px) {

                .pp-child-wrap {
                    padding-left: 12px;
                }
            }
        </style>
    @endpush
@endsection
