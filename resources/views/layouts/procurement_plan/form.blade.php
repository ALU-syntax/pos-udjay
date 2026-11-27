@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')
    @include('layouts.warehouse.partials.ui-styles')

    @php
        $selectedSources = collect(old('selected_sources', []))->map(fn ($id) => (int) $id)->all();
        // Qty tanpa nol di belakang koma (decimal:5); Rupiah tanpa ,00 kalau bulat
        $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 5, ',', '.'), '0'), ',');
        $rupiah = fn ($value) => $value === null ? null : 'Rp ' . number_format((float) $value, fmod((float) $value, 1) ? 2 : 0, ',', '.');
        $sourceCount = $sourceGroups->sum(fn ($group) => $group->items->count());
    @endphp

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">
                    <a href="{{ route('warehouse/procurement-plan') }}">Procurement Plan</a><span class="wh-crumb-sep" aria-hidden="true">/</span>Tambah
                </div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-clipboard-check"></i></span>
                    {{ $title }}
                </h1>
                <p class="ob-header__desc">Pilih item Request Order yang sudah disetujui, tentukan supplier tiap bahan, lalu PO draft dibuat per supplier.</p>
            </div>
            <div class="wh-actions">
                <a href="{{ route('warehouse/procurement-plan') }}" class="btn ob-btn ob-btn-outline">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Kembali
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="ob-notice pp-notice pp-notice--error" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i> {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ $action }}" method="POST" id="procurementPlanForm">
            @csrf

            {{-- Informasi plan --}}
            <div class="ob-card pp-card">
                <div class="wh-card__head">
                    <div>
                        <h2 class="wh-card__title"><i class="fas fa-info-circle" aria-hidden="true"></i> Informasi Plan</h2>
                        <p class="wh-card__desc">Nomor plan dan lokasi tempat stok dicek sebelum dibeli.</p>
                    </div>
                </div>
                <div class="wh-card__body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="plan_number">Nomor Plan</label>
                            <input id="plan_number" name="plan_number" type="text" maxlength="100"
                                class="form-control @error('plan_number') is-invalid @enderror"
                                value="{{ old('plan_number', $planNumber) }}" placeholder="Contoh: {{ $planNumber }}">
                            @error('plan_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <small class="text-muted">Kosongkan untuk dibuat otomatis.</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="planning_location_id">Lokasi Planning <span class="text-danger">*</span></label>
                            <select id="planning_location_id" name="planning_location_id"
                                class="form-select @error('planning_location_id') is-invalid @enderror" required>
                                <option value="">Pilih lokasi planning</option>
                                @foreach ($inventories as $inventory)
                                    <option value="{{ $inventory->id }}" @selected((int) old('planning_location_id') === (int) $inventory->id)>
                                        {{ $inventory->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('planning_location_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @else
                                <small class="text-muted">Stok bebas di lokasi ini mengurangi qty yang perlu dibeli.</small>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="notes">Catatan</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror"
                                placeholder="Contoh: pembelian mingguan untuk outlet Kemang">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Item request order --}}
            <div class="ob-card pp-card">
                <div class="wh-card__head">
                    <div>
                        <h2 class="wh-card__title"><i class="fas fa-layer-group" aria-hidden="true"></i> Item Request Order Disetujui <span class="wh-count">{{ $sourceGroups->count() }}</span></h2>
                        <p class="wh-card__desc">Satu baris per bahan baku. Buka baris untuk memilih request dari masing-masing peminta.</p>
                    </div>
                </div>

                @if ($sourceGroups->isEmpty())
                    <div class="wh-empty">
                        <span class="wh-empty__icon" aria-hidden="true"><i class="fas fa-layer-group"></i></span>
                        <p class="wh-empty__title">Belum ada item yang bisa direncanakan</p>
                        <p class="wh-empty__text">Item muncul di sini setelah Request Order disetujui dan belum masuk plan lain.</p>
                    </div>
                @else
                    <div class="wh-card__body pp-toolbar">
                        <div class="pp-toolbar__search">
                            <label class="form-label" for="sourceSearch">Cari</label>
                            <input type="search" id="sourceSearch" class="form-control" placeholder="Nama bahan, nomor request, atau peminta">
                        </div>
                        <div class="pp-toolbar__actions">
                            <button type="button" class="btn ob-btn ob-btn-outline" id="selectAllSources">
                                <i class="fas fa-check-double" aria-hidden="true"></i> Pilih semua
                            </button>
                            <button type="button" class="btn ob-btn ob-btn-outline" id="clearAllSources">
                                <i class="fas fa-eraser" aria-hidden="true"></i> Kosongkan pilihan
                            </button>
                        </div>
                    </div>

                    <div class="ob-table-scroll">
                        <table class="wh-table pp-sources" id="approvedRequestItemsTable">
                            <thead>
                                <tr>
                                    <th scope="col" class="pp-col-check"><span class="visually-hidden">Pilih</span></th>
                                    <th scope="col">Bahan Baku / Request</th>
                                    <th scope="col" class="text-end">Qty Dibutuhkan</th>
                                    <th scope="col" class="pp-col-supplier">Supplier</th>
                                    <th scope="col" class="text-end">Harga Beli</th>
                                </tr>
                            </thead>
                            @foreach ($sourceGroups as $group)
                                @php
                                    $groupId = (int) $group->raw_material_id;
                                    $groupSourceIds = $group->items->pluck('id')->map(fn ($id) => (int) $id)->all();
                                    $hasSelected = count(array_intersect($groupSourceIds, $selectedSources)) > 0;
                                    $supplierOptions = $supplierOptionsByRawMaterial[$groupId] ?? [];
                                    $defaultSupplierId = collect($supplierOptions)->firstWhere('is_preferred', true)['id'] ?? ($supplierOptions[0]['id'] ?? null);
                                    $supplierField = 'supplier_raw_materials.' . $groupId;
                                    $selectedSupplierId = (int) old($supplierField, $defaultSupplierId);
                                    $selectedSupplier = collect($supplierOptions)->firstWhere('id', $selectedSupplierId);
                                    $unit = $group->base_satuan_name ?: ($group->raw_material?->baseUnit?->symbol ?: $group->raw_material?->baseUnit?->name);
                                    $groupSearch = mb_strtolower(($group->raw_material?->name ?? '') . ' ' . ($group->raw_material?->code ?? ''));
                                    $isOpen = $hasSelected || $errors->has($supplierField);
                                    // Bahan tanpa supplier aktif tidak bisa dipilih: PO tidak bisa dibuat
                                    $noSupplier = empty($supplierOptions);
                                @endphp
                                <tbody class="pp-group {{ $isOpen ? 'is-open' : '' }}" data-group-id="{{ $groupId }}" data-search="{{ $groupSearch }}">
                                    <tr class="pp-group-row">
                                        <td class="pp-col-check">
                                            <input type="checkbox" class="form-check-input group-checkbox" data-group-id="{{ $groupId }}"
                                                aria-label="Pilih semua request {{ $group->raw_material?->name }}" @disabled($noSupplier)>
                                        </td>
                                        <td>
                                            <div class="pp-material">
                                                <button type="button" class="ob-icon-btn ob-icon-btn--sm pp-toggle" aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                                                    aria-label="Tampilkan request {{ $group->raw_material?->name }}"><i class="fas fa-chevron-down" aria-hidden="true"></i></button>
                                                <div class="wh-cell-stack">
                                                    <span class="wh-strong">{{ $group->raw_material?->name ?? '-' }}</span>
                                                    <span class="ob-cell-meta">
                                                        {{ $group->raw_material?->code ?: 'Tanpa kode' }} · {{ $group->items->count() }} request
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="wh-cell-stack wh-cell-stack--end">
                                                <span class="wh-strong wh-num">{{ $qty($group->total_remaining_base) }} {{ $unit }}</span>
                                                @if ((float) $group->total_allocated_base > 0)
                                                    <span class="ob-cell-meta wh-num">dari {{ $qty($group->total_approved_base) }} disetujui</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="pp-col-supplier">
                                            @if ($noSupplier)
                                                <select name="supplier_raw_materials[{{ $groupId }}]" class="form-select supplier-select" data-group-id="{{ $groupId }}" disabled>
                                                    <option value="">Belum ada supplier</option>
                                                </select>
                                                <small class="pp-warn">Belum bisa dipilih. Tambahkan bahan ini ke salah satu supplier dulu.</small>
                                            @else
                                                <select name="supplier_raw_materials[{{ $groupId }}]" data-group-id="{{ $groupId }}"
                                                    class="form-select supplier-select @error($supplierField) is-invalid @enderror"
                                                    aria-label="Supplier {{ $group->raw_material?->name }}">
                                                    @foreach ($supplierOptions as $option)
                                                        <option value="{{ $option['id'] }}" data-price="{{ $option['current_price'] }}" data-unit="{{ $option['purchase_unit'] }}"
                                                            @selected((int) $option['id'] === $selectedSupplierId)>
                                                            {{ $option['supplier_name'] }}{{ $option['is_preferred'] ? ' · Utama' : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @endif
                                            @error($supplierField)
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td class="text-end">
                                            <div class="wh-cell-stack wh-cell-stack--end">
                                                <span class="wh-strong wh-num group-price" data-group-id="{{ $groupId }}">{{ $rupiah($selectedSupplier['current_price'] ?? null) ?? 'Belum diisi' }}</span>
                                                <span class="ob-cell-meta group-price-unit" data-group-id="{{ $groupId }}">{{ !empty($selectedSupplier['purchase_unit']) ? 'per ' . $selectedSupplier['purchase_unit'] : '' }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @foreach ($group->items as $sourceItem)
                                        @php
                                            $requestOrder = $sourceItem->rawMaterialRequest;
                                            $requester = $requestOrder?->requesterInventory?->name;
                                            $childSearch = mb_strtolower(($requestOrder?->request_number ?? '') . ' ' . ($requester ?? ''));
                                        @endphp
                                        <tr class="pp-source-row" data-search="{{ $childSearch }}">
                                            <td class="pp-col-check">
                                                <input type="checkbox" name="selected_sources[]" value="{{ $sourceItem->id }}"
                                                    class="form-check-input child-checkbox" data-group-id="{{ $groupId }}"
                                                    aria-label="Pilih {{ $requestOrder?->request_number }}" @disabled($noSupplier)
                                                    @checked(! $noSupplier && in_array((int) $sourceItem->id, $selectedSources, true))>
                                            </td>
                                            <td>
                                                <div class="wh-cell-stack pp-source">
                                                    <span class="pp-source__name">{{ $requester ?? '-' }}</span>
                                                    <span class="ob-cell-meta">
                                                        {{ $requestOrder?->request_number ?? '-' }}{{ $requestOrder?->needed_at ? ' · dibutuhkan ' . $requestOrder->needed_at->translatedFormat('d M Y') : '' }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <div class="wh-cell-stack wh-cell-stack--end">
                                                    <span class="wh-num">{{ $qty($sourceItem->remaining_qty_base) }} {{ $unit }}</span>
                                                    @if ((float) $sourceItem->allocated_qty_base > 0)
                                                        <span class="ob-cell-meta wh-num">{{ $qty($sourceItem->allocated_qty_base) }} sudah di plan lain</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td colspan="2"></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            @endforeach
                        </table>
                    </div>
                    <div id="sourceSearchEmpty" class="wh-empty" hidden>
                        <span class="wh-empty__icon" aria-hidden="true"><i class="fas fa-search"></i></span>
                        <p class="wh-empty__title">Tidak ada yang cocok</p>
                        <p class="wh-empty__text">Coba kata kunci lain atau kosongkan pencarian.</p>
                    </div>
                @endif

                <div class="pp-form-foot">
                    <p class="pp-summary" aria-live="polite">
                        <strong id="selectedSourceCount">0</strong> dari {{ $sourceCount }} request dipilih ·
                        <strong id="selectedMaterialCount">0</strong> bahan
                    </p>
                    <div class="wh-actions">
                        <a href="{{ route('warehouse/procurement-plan') }}" class="btn ob-btn ob-btn-outline">Batal</a>
                        <button type="submit" class="btn ob-btn ob-btn-primary" @disabled($sourceGroups->isEmpty())>
                            <i class="fas fa-file-invoice" aria-hidden="true"></i> Simpan & Buat PO
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @push('js')
        <script>
            $(function () {
                function formatMoney(value) {
                    if (value === undefined || value === null || value === '') return 'Belum diisi';

                    const number = Number(value);
                    return 'Rp ' + number.toLocaleString('id-ID', { minimumFractionDigits: number % 1 ? 2 : 0, maximumFractionDigits: 2 });
                }

                function children(groupId) {
                    return $('.child-checkbox[data-group-id="' + groupId + '"]');
                }

                function updateGroupCheckbox(groupId) {
                    const all = children(groupId);
                    const checked = all.filter(':checked').length;

                    $('.group-checkbox[data-group-id="' + groupId + '"]')
                        .prop('checked', all.length > 0 && checked === all.length)
                        .prop('indeterminate', checked > 0 && checked < all.length);
                }

                function updateGroupPrice(groupId) {
                    const option = $('.supplier-select[data-group-id="' + groupId + '"] option:selected');
                    const unit = option.data('unit') || '';

                    $('.group-price[data-group-id="' + groupId + '"]').text(formatMoney(option.data('price')));
                    $('.group-price-unit[data-group-id="' + groupId + '"]').text(unit ? 'per ' + unit : '');
                }

                function updateSummary() {
                    const checked = $('.child-checkbox:checked');
                    const groups = new Set(checked.map(function () { return $(this).data('group-id'); }).get());

                    $('#selectedSourceCount').text(checked.length);
                    $('#selectedMaterialCount').text(groups.size);
                }

                function setOpen(group, open) {
                    group.toggleClass('is-open', open);
                    group.find('.pp-toggle').attr('aria-expanded', open ? 'true' : 'false');
                }

                function refreshAll() {
                    $('.group-checkbox').each(function () {
                        updateGroupCheckbox($(this).data('group-id'));
                        updateGroupPrice($(this).data('group-id'));
                    });
                    updateSummary();
                }

                $('.group-checkbox').on('change', function () {
                    const groupId = $(this).data('group-id');
                    children(groupId).prop('checked', this.checked);
                    updateGroupCheckbox(groupId);
                    updateSummary();
                });

                $('.child-checkbox').on('change', function () {
                    updateGroupCheckbox($(this).data('group-id'));
                    updateSummary();
                });

                $('.supplier-select').on('change', function () {
                    updateGroupPrice($(this).data('group-id'));
                });

                // Klik baris bahan (selain kontrol) membuka/menutup daftar request-nya
                $('.pp-group-row').on('click', function (event) {
                    if ($(event.target).closest('input, select, a, label').length) return;
                    const group = $(this).closest('.pp-group');
                    setOpen(group, !group.hasClass('is-open'));
                });

                $('#sourceSearch').on('input search', function () {
                    const keyword = this.value.trim().toLowerCase();
                    let visibleGroups = 0;

                    $('.pp-group').each(function () {
                        const group = $(this);
                        const groupMatch = keyword === '' || String(group.data('search')).includes(keyword);
                        let childMatch = false;

                        group.find('.pp-source-row').each(function () {
                            const match = groupMatch || String($(this).data('search')).includes(keyword);
                            $(this).toggleClass('is-filtered', !match);
                            childMatch = childMatch || match;
                        });

                        const visible = groupMatch || childMatch;
                        group.prop('hidden', !visible);
                        if (visible) visibleGroups++;
                        // Request yang cocok langsung terlihat
                        if (keyword !== '' && !groupMatch && childMatch) setOpen(group, true);
                    });

                    $('#sourceSearchEmpty').prop('hidden', visibleGroups > 0);
                    $('#approvedRequestItemsTable').closest('.ob-table-scroll').prop('hidden', visibleGroups === 0);
                });

                $('#selectAllSources, #clearAllSources').on('click', function () {
                    $('.child-checkbox:not(:disabled)').prop('checked', this.id === 'selectAllSources');
                    refreshAll();
                });

                $('#procurementPlanForm').on('submit', function (event) {
                    const checked = $('.child-checkbox:checked');

                    if (checked.length === 0) {
                        event.preventDefault();
                        showToast('error', 'Pilih minimal satu request dari item Request Order yang disetujui.');
                        return;
                    }

                    const missing = new Set();
                    checked.each(function () {
                        const groupId = $(this).data('group-id');
                        if (!$('.supplier-select[data-group-id="' + groupId + '"]').val()) missing.add(groupId);
                    });

                    if (missing.size > 0) {
                        event.preventDefault();
                        missing.forEach(function (groupId) { setOpen($('.pp-group[data-group-id="' + groupId + '"]'), true); });
                        showToast('error', 'Ada bahan terpilih yang belum punya supplier. Lepas pilihannya atau tambahkan bahan itu ke supplier dulu.');
                    }
                });

                $('#planning_location_id').select2({ width: '100%' });
                refreshAll();
            });
        </script>
    @endpush

    @push('css')
        <style>
            .pp-card { margin-bottom: 1.25rem; }
            .pp-notice { margin-bottom: 1rem; }
            .pp-notice--error { border-color: var(--ob-danger); color: var(--ob-danger); background: var(--ob-danger-soft); }

            .pp-toolbar { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .75rem; border-bottom: 1px solid var(--ob-border); }
            .pp-toolbar__search { flex: 1 1 320px; min-width: 0; }
            .pp-toolbar__actions { display: flex; flex-wrap: wrap; gap: .5rem; }

            .pp-sources .pp-col-check { width: 44px; padding-right: 0; }
            .pp-sources .pp-col-supplier { width: 260px; min-width: 220px; }
            .pp-sources td:nth-child(2) { min-width: 260px; }
            .pp-sources td:nth-child(3) { min-width: 140px; }
            .pp-sources td:nth-child(5) { min-width: 130px; }

            /* Tiap bahan = satu tbody: baris bahan + baris request (tersembunyi sampai dibuka) */
            .pp-group-row { cursor: pointer; }
            .pp-group.is-open .pp-group-row td { background: var(--ob-surface-2); }
            .pp-source-row { display: none; }
            .pp-group.is-open .pp-source-row:not(.is-filtered) { display: table-row; }
            .pp-source-row td { background: var(--ob-surface-2); font-size: .82rem; padding-top: .55rem; padding-bottom: .55rem; }
            /* wh-table menghapus border baris terakhir tbody; baris anak tetap butuh pemisah tipis */
            .wh-table tbody.pp-group tr:last-child td { border-bottom: 1px solid var(--ob-border); }

            .pp-material { display: flex; align-items: flex-start; gap: .6rem; }
            .pp-toggle i { transition: transform .15s ease; }
            .pp-toggle[aria-expanded="true"] i { transform: rotate(180deg); }
            .pp-source { padding-left: 2.6rem; }
            .pp-source__name { font-weight: 600; color: var(--ob-text-2); }

            .pp-sources .form-check-input { width: 1.05rem; height: 1.05rem; margin: 0; border: 1.5px solid var(--ob-border-strong); cursor: pointer; }
            .pp-sources .form-check-input:disabled { cursor: not-allowed; opacity: .45; }

            .pp-warn { display: block; margin-top: .3rem; font-size: .75rem; color: var(--ob-danger); }

            .pp-form-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.25rem; border-top: 1px solid var(--ob-border); }
            .pp-summary { margin: 0; font-size: .85rem; color: var(--ob-muted); }
            .pp-summary strong { color: var(--ob-text); font-variant-numeric: tabular-nums; }
        </style>
    @endpush
@endsection
