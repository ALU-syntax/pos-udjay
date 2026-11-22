@extends('layouts.app')

@php
    $editing = $contract->exists;
    // Tab awal: outlet pertama yang sudah punya harga di kontrak ini, kalau tidak ada outlet pertama di daftar.
    $initialOutletId = $initialItems->pluck('outlet_id')->filter()->first() ?? $outlets->first()?->id;
@endphp

@section('content')
    @include('layouts.b2b.partials.assets')
    @include('layouts.b2b.partials.page-styles')
    @include('layouts.b2b.contracts.partials.styles')
    @include('layouts.b2b.partials.datepicker')

    <div class="main-content ob-page">
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">
                    <a href="{{ route('b2b/contracts') }}">Kontrak B2B</a>
                    @if ($editing)
                        <span class="b2b-crumb-sep" aria-hidden="true">/</span><a href="{{ route('b2b/contracts/show', $contract->id) }}">{{ $contract->contract_number }}</a>
                    @endif
                </div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas {{ $editing ? 'fa-pen' : 'fa-file-signature' }}"></i></span>
                    {{ $editing ? 'Edit Kontrak' : 'Buat Kontrak' }}
                </h1>
                <p class="ob-header__desc">
                    @if ($copiedFrom)
                        Disalin dari {{ $copiedFrom->contract_number }}. Periode otomatis disambung setelah kontrak lama berakhir.
                    @elseif ($editing)
                        Tanggal dan harga bisa diubah selama kontrak belum dihentikan.
                    @else
                        Nomor kontrak dibuat otomatis. Kontrak langsung berlaku sesuai tanggal mulai.
                    @endif
                </p>
            </div>
        </div>

        @if ($copiedFrom && $skippedVariants > 0)
            <div class="ob-notice mb-3" role="note">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                <span><strong>{{ $skippedVariants }} produk tidak disalin</strong> dari {{ $copiedFrom->contract_number }} karena produknya sudah dihapus.</span>
            </div>
        @endif

        @if ($editing && $contract->isRunning())
            <div class="ob-notice mb-3" role="note">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                <span>Kontrak ini <strong>sedang berjalan</strong>. Perubahan harga langsung berlaku untuk pesanan berikutnya. Pesanan yang sudah dibuat tetap memakai harga lama.</span>
            </div>
        @endif

        <form id="contract-form" class="b2b-form" action="{{ $editing ? route('b2b/contracts/update', $contract->id) : route('b2b/contracts/store') }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf
            @if ($editing)
                @method('PUT')
            @endif
            @if ($copiedFrom)
                {{-- Dicatat di log kontrak baru supaya detail kontrak bisa menampilkan "Diperpanjang dari". --}}
                <input type="hidden" name="copied_from" value="{{ $copiedFrom->id }}">
            @endif

            {{-- Baris atas: data kontrak & ringkasan; tabel harga di bawahnya selebar halaman. --}}
            <div class="b2b-contract-top">
                <div class="ob-card">
                    <div class="b2b-card__head">
                        <div>
                            <h2 class="b2b-card__title"><i class="fas fa-handshake" aria-hidden="true"></i> Customer &amp; Periode</h2>
                            <p class="b2b-card__desc">Periode tidak boleh bertabrakan dengan kontrak lain milik customer yang sama.</p>
                        </div>
                    </div>
                    <div class="b2b-card__body">
                        <div class="b2b-fields">
                            <div class="b2b-field">
                                <label for="b2b_customer_id" class="form-label">Customer <span class="b2b-required">*</span></label>
                                {{-- Saat edit, select dinonaktifkan sehingga tidak ikut terkirim: customer kontrak tidak bisa diganti. --}}
                                <select id="b2b_customer_id" name="b2b_customer_id" class="form-select" required @disabled($editing)>
                                    <option value="">Pilih customer</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}" @selected((int) $contract->b2b_customer_id === $customer->id)>{{ $customer->name }} · {{ $customer->code }}</option>
                                    @endforeach
                                </select>
                                @if ($editing)
                                    <small class="b2b-help"><i class="fas fa-lock me-1" aria-hidden="true"></i>Customer tidak bisa diganti setelah kontrak dibuat.</small>
                                @endif
                            </div>
                            <div class="b2b-field">
                                <label for="period" class="form-label">Periode kontrak <span class="b2b-required">*</span></label>
                                <div class="b2b-range">
                                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                                    <input id="period" type="text" class="form-control" readonly placeholder="Pilih tanggal mulai – berakhir" aria-describedby="period-hint">
                                    <span class="b2b-range__days" id="period-days"></span>
                                </div>
                                {{-- Nilai yang dikirim ke server; diisi dari date range picker (YYYY-MM-DD). --}}
                                <input type="hidden" id="start_date" name="start_date" value="{{ $contract->start_date?->format('Y-m-d') }}">
                                <input type="hidden" id="end_date" name="end_date" value="{{ $contract->end_date?->format('Y-m-d') }}">
                                <small class="b2b-help" id="period-hint">Klik untuk memilih rentang, atau pakai pilihan cepat 3 bulan, 6 bulan, dan 1 tahun.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ob-card">
                    <div class="b2b-card__head">
                        <h2 class="b2b-card__title"><i class="fas fa-paperclip" aria-hidden="true"></i> Dokumen &amp; Catatan</h2>
                    </div>
                    <div class="b2b-card__body">
                        <div class="b2b-field mb-3">
                            <span class="b2b-group-label">File kontrak <span class="b2b-optional">(opsional)</span></span>
                            {{-- Kotak file: kosong, file baru dipilih, file tersimpan, atau tersimpan-tapi-akan-dihapus. Diatur oleh renderAttachment(). --}}
                            <div class="b2b-file" id="attachment-box">
                                <span class="b2b-file__icon" aria-hidden="true"><i class="fas fa-file-upload"></i></span>
                                <span class="b2b-file__text">
                                    <strong id="attachment-name">{{ $contract->attachment_name ?: 'Pilih file' }}</strong>
                                    <span id="attachment-hint">PDF, JPG, PNG, atau DOC · maks. 5 MB</span>
                                </span>
                                <label for="attachment" class="btn ob-btn ob-btn-outline ob-btn-sm mb-0" id="attachment-pick">{{ $contract->attachment_name ? 'Ganti' : 'Pilih' }}</label>
                                <button type="button" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger" id="attachment-clear" hidden></button>
                                <input id="attachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                <input id="remove_attachment" name="remove_attachment" type="hidden" value="0">
                            </div>
                        </div>
                        <div class="b2b-field">
                            <label for="notes" class="form-label">Catatan <span class="b2b-optional">(opsional)</span></label>
                            <textarea id="notes" name="notes" rows="3" class="form-control" maxlength="2000" placeholder="Catatan internal, mis. hasil negosiasi">{{ $contract->notes }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="ob-card">
                    <div class="b2b-card__head">
                        <h2 class="b2b-card__title"><i class="fas fa-clipboard-list" aria-hidden="true"></i> Ringkasan</h2>
                    </div>
                    <div class="b2b-card__body">
                        <dl class="b2b-summary__list">
                            <div class="b2b-summary__item"><dt>Produk</dt><dd id="sum-products">0</dd></div>
                            <div class="b2b-summary__item"><dt>Outlet</dt><dd id="sum-outlets">0</dd></div>
                            <div class="b2b-summary__item b2b-summary__item--wide"><dt>Lama kontrak</dt><dd id="sum-duration">—</dd></div>
                        </dl>
                        <ul class="b2b-outlet-breakdown" id="outlet-breakdown" aria-label="Produk per outlet"></ul>
                        <ul class="b2b-checklist" aria-label="Kelengkapan kontrak">
                            <li data-check="customer"><i class="fas fa-check-circle" aria-hidden="true"></i> Customer dipilih</li>
                            <li data-check="period"><i class="fas fa-check-circle" aria-hidden="true"></i> Periode valid</li>
                            <li data-check="items"><i class="fas fa-check-circle" aria-hidden="true"></i> Minimal satu produk dipilih</li>
                            <li data-check="prices"><i class="fas fa-check-circle" aria-hidden="true"></i> Semua produk terpilih sudah berharga</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="ob-card">
                <div class="b2b-card__head">
                    <div>
                        <h2 class="b2b-card__title"><i class="fas fa-tags" aria-hidden="true"></i> Harga Kontrak <span class="b2b-count" id="product-count">0</span></h2>
                        <p class="b2b-card__desc">Centang produk lalu isi harga kontraknya, atau langsung ketik harganya. Produk yang tidak dicentang tetap memakai harga normal.</p>
                    </div>
                </div>
                <div class="b2b-card__body">
                    <div class="b2b-outlet-tabs" role="tablist" aria-label="Outlet">
                        @foreach ($outlets as $outlet)
                            <button type="button" class="b2b-outlet-tab" role="tab" data-outlet="{{ $outlet->id }}" aria-selected="false">
                                {{ $outlet->name }} <span class="b2b-outlet-tab__count"></span>
                            </button>
                        @endforeach
                    </div>

                    <div class="b2b-pick-toolbar">
                        <div class="b2b-pick-search">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input type="search" id="pick-search" class="form-control" placeholder="Cari produk atau varian" aria-label="Cari produk atau varian">
                        </div>
                        <select id="pick-category" class="form-select" aria-label="Kategori">
                            <option value="">Semua kategori</option>
                        </select>
                        <label class="b2b-chip"><input type="checkbox" id="pick-selected-only"><span>Yang dipilih saja</span></label>
                    </div>

                    <div data-error-for="items"></div>
                    <div class="b2b-pick-wrap">
                        <table class="b2b-pick-table">
                            <thead>
                                <tr>
                                    <th class="col-check"><span class="visually-hidden">Pilih</span></th>
                                    <th>Produk / varian</th>
                                    <th class="col-normal text-end">Harga normal</th>
                                    <th class="col-qty">Mulai qty</th>
                                    <th class="col-price">Harga kontrak</th>
                                    <th class="col-delta text-end">Selisih</th>
                                    <th class="col-action"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody id="pick-body"></tbody>
                        </table>
                    </div>
                    <div class="b2b-pick-foot">
                        <span id="pick-foot-count"></span>
                        <span><i class="fas fa-layer-group me-1" aria-hidden="true"></i>Klik ikon tingkat untuk harga khusus mulai qty tertentu.</span>
                    </div>
                    <div id="items-payload" hidden></div>
                </div>
            </div>

            <div class="b2b-form-footer">
                <span class="b2b-form-footer__hint"><span class="b2b-required">*</span> wajib diisi</span>
                <a href="{{ $editing ? route('b2b/contracts/show', $contract->id) : route('b2b/contracts') }}" class="btn ob-btn ob-btn-ghost">Batal</a>
                <button type="submit" class="btn ob-btn ob-btn-primary"><i class="fas fa-save" aria-hidden="true"></i> {{ $editing ? 'Simpan Perubahan' : 'Simpan Kontrak' }}</button>
            </div>
        </form>
    </div>

    @push('js')
        <script>
            $(function () {
                const form = $('#contract-form');
                const variantUrl = @json(route('b2b/selectors/variants'));
                const outletNames = @json($outlets->pluck('name', 'id'));
                const rupiah = value => 'Rp ' + Number(value || 0).toLocaleString('id-ID');
                // Input harga berupa teks dengan pemisah ribuan (40.000); nilai yang disimpan tetap angka bulat.
                const thousands = value => value === '' || value === null || value === undefined ? '' : Number(value).toLocaleString('id-ID');
                function formatPriceInput(el) {
                    const caret = el.value.slice(0, el.selectionStart ?? el.value.length).replace(/\D/g, '').length;
                    const digits = el.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '').slice(0, 12);
                    el.value = thousands(digits === '' ? '' : Number(digits));
                    // Kursor tetap di posisi digit yang sama walau titik ribuan bertambah/berkurang.
                    let position = 0;
                    for (let seen = 0; position < el.value.length && seen < caret; position++) {
                        if (/\d/.test(el.value[position])) seen++;
                    }
                    if (document.activeElement === el) el.setSelectionRange(position, position);
                    return digits === '' ? '' : Number(digits);
                }
                const esc = B2b.escape;

                // items: varian terpilih (lintas outlet) → {id, outlet_id, product, variant, category, price, active, deleted, tiers:[{id, min_qty, price}]}
                const state = {
                    outletId: @json($initialOutletId),
                    catalog: {},
                    items: new Map(@json($initialItems).map(item => [item.id, item])),
                    errors: {},
                };

                $('#b2b_customer_id').select2({ width: '100%', placeholder: 'Pilih customer' });

                // ---------- Periode (satu date range picker → input hidden start_date / end_date) ----------
                function initPeriod() {
                    const period = $('#period');
                    const from = $('#start_date');
                    const to = $('#end_date');
                    // moment di project hanya punya locale Inggris, jadi nama bulan ditulis sendiri.
                    const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                    const label = date => `${date.format('DD')} ${shortMonths[date.month()]} ${date.year()}`;
                    function setPeriod(start, end) {
                        period.val(label(start) + '  –  ' + label(end));
                        from.val(start.format('YYYY-MM-DD'));
                        to.val(end.format('YYYY-MM-DD')).trigger('change');
                        $('#period-days').text(days(start, end).toLocaleString('id-ID') + ' hari');
                        from.add(to).removeClass('is-invalid').closest('.b2b-field').find('.b2b-error').remove();
                    }
                    const start = from.val() ? moment(from.val(), 'YYYY-MM-DD') : moment().startOf('day');
                    const end = to.val() ? moment(to.val(), 'YYYY-MM-DD') : start.clone().add(1, 'year').subtract(1, 'day');
                    // Pilihan cepat dihitung dari tanggal mulai yang tersimpan saat form dibuka.
                    const span = months => [start.clone(), start.clone().add(months, 'months').subtract(1, 'day')];
                    period.daterangepicker({
                        autoUpdateInput: false,
                        startDate: start,
                        endDate: end,
                        drops: 'auto',
                        showDropdowns: true,
                        linkedCalendars: false,
                        alwaysShowCalendars: true,
                        ranges: { '3 bulan': span(3), '6 bulan': span(6), '1 tahun': span(12) },
                        buttonClasses: 'btn btn-sm',
                        applyButtonClasses: 'btn-primary',
                        cancelButtonClasses: 'btn-light',
                        locale: {
                            format: 'DD/MM/YYYY',
                            separator: ' – ',
                            applyLabel: 'Terapkan',
                            cancelLabel: 'Batal',
                            customRangeLabel: 'Pilih sendiri',
                            daysOfWeek: ['Mg', 'Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb'],
                            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                            firstDay: 1,
                        },
                    });
                    const picker = period.data('daterangepicker');
                    picker.container.addClass('b2b-drp');

                    // Ringkasan di footer popup: tanggal dalam bahasa Indonesia + jumlah hari, bukan format bawaan DD/MM/YYYY.
                    const days = (a, b) => b.clone().startOf('day').diff(a.clone().startOf('day'), 'days') + 1;
                    function renderSelected() {
                        const box = picker.container.find('.drp-selected');
                        if (!picker.endDate) {
                            box.html(`<strong>${label(picker.startDate)}  –  …</strong><small>Pilih tanggal berakhir</small>`);
                            return;
                        }
                        box.html(`<strong>${label(picker.startDate)}  –  ${label(picker.endDate)}</strong>`
                            + `<small class="is-days">${days(picker.startDate, picker.endDate).toLocaleString('id-ID')} hari</small>`);
                    }
                    ['updateView', 'setEndDate'].forEach(name => {
                        const original = picker[name];
                        picker[name] = function () {
                            const result = original.apply(this, arguments);
                            renderSelected();
                            return result;
                        };
                    });

                    period.on('show.daterangepicker', () => { period.addClass('is-open'); renderSelected(); })
                        .on('hide.daterangepicker', () => period.removeClass('is-open'))
                        .on('apply.daterangepicker', (event, picker) => setPeriod(picker.startDate, picker.endDate));
                    setPeriod(start, end);
                }
                initPeriod();

                // ---------- Data outlet ----------
                function loadOutlet(outletId) {
                    state.outletId = outletId;
                    $('.b2b-outlet-tab').each(function () {
                        const active = String(this.dataset.outlet) === String(outletId);
                        $(this).toggleClass('is-active', active).attr('aria-selected', active);
                    });
                    if (state.catalog[outletId]) return renderTable();

                    $('#pick-body').html('<tr class="b2b-pick-state"><td colspan="7"><span class="spinner-border spinner-border-sm me-2"></span>Memuat produk…</td></tr>');
                    $.getJSON(variantUrl, { outlet_id: outletId })
                        .done(response => { state.catalog[outletId] = response.data || []; if (state.outletId === outletId) renderTable(); })
                        .fail(() => $('#pick-body').html('<tr class="b2b-pick-state"><td colspan="7">Gagal memuat produk. <a href="#" data-retry>Coba lagi</a></td></tr>'));
                }

                // Katalog outlet + varian terpilih yang sudah tidak ada di katalog (mis. produk dihapus).
                function outletRows() {
                    const catalog = state.catalog[state.outletId] || [];
                    const ids = new Set(catalog.map(v => v.id));
                    const missing = [...state.items.values()].filter(item => String(item.outlet_id) === String(state.outletId) && !ids.has(item.id));
                    return [...missing.map(item => ({ ...item, deleted: true })), ...catalog];
                }

                // ---------- Tabel ----------
                function deltaHtml(normal, price) {
                    if (price === '' || price === null || price === undefined || normal === null || normal === undefined) return '';
                    const delta = Number(price) - Number(normal);
                    if (delta === 0) return '<span class="b2b-delta is-same">sama</span>';
                    return `<span class="b2b-delta ${delta < 0 ? 'is-down' : 'is-up'}">${delta < 0 ? '−' : '+'}${Math.abs(delta).toLocaleString('id-ID')}</span>`;
                }

                function errorHtml(variantId, tier) {
                    const message = state.errors[`${variantId}:${tier}`];
                    return message ? `<div class="b2b-pick-error">${esc(message)}</div>` : '';
                }

                function rowsHtml(variant) {
                    const item = state.items.get(variant.id);
                    const selected = !!item;
                    const tiers = item ? item.tiers : [{ id: null, min_qty: 1, price: '' }];
                    const invalid = key => state.errors[key] ? 'is-invalid' : '';
                    const meta = [
                        variant.variant ? esc(variant.variant) : null,
                        variant.category ? esc(variant.category) : null,
                        variant.deleted ? '<span class="badge badge-muted">dihapus</span>' : (variant.active === false ? '<span class="badge badge-muted">nonaktif</span>' : null),
                    ].filter(Boolean).join(' · ');
                    const search = `${variant.product} ${variant.variant || ''} ${variant.category || ''}`.toLowerCase();

                    let html = `
                        <tr class="b2b-pick-row ${selected ? 'is-selected' : ''}" data-variant="${variant.id}" data-search="${esc(search)}" data-category="${esc(variant.category || '')}">
                            <td class="col-check"><input type="checkbox" class="form-check-input" data-toggle ${selected ? 'checked' : ''} aria-label="Pilih ${esc(variant.product)}"></td>
                            <td><div class="b2b-pick-name">${esc(variant.product)}</div>${meta ? `<div class="b2b-pick-meta">${meta}</div>` : ''}</td>
                            <td class="col-normal text-end b2b-money">${variant.price === null || variant.price === undefined ? '—' : rupiah(variant.price)}</td>
                            <td class="col-qty"><input type="number" min="1" step="1" inputmode="numeric" class="form-control ${invalid(variant.id + ':0:min_qty')}" data-tier="0" data-key="min_qty" value="${tiers[0].min_qty ?? 1}" ${selected ? '' : 'disabled'} aria-label="Mulai qty"></td>
                            <td class="col-price">
                                <div class="input-group"><span class="input-group-text">Rp</span><input type="text" inputmode="numeric" autocomplete="off" class="form-control item-price ${invalid(variant.id + ':0:price')}" data-tier="0" data-key="price" value="${thousands(tiers[0].price)}" placeholder="${thousands(variant.price)}" aria-label="Harga kontrak ${esc(variant.product)}"></div>
                                ${errorHtml(variant.id, 0)}
                            </td>
                            <td class="col-delta text-end" data-delta="0">${selected ? deltaHtml(variant.price, tiers[0].price) : ''}</td>
                            <td class="col-action">${selected ? `<button type="button" class="ob-icon-btn ob-icon-btn--sm" data-add-tier title="Tambah harga untuk qty lain" aria-label="Tambah tingkat qty"><i class="fas fa-layer-group"></i></button>` : ''}</td>
                        </tr>`;

                    tiers.slice(1).forEach((tier, offset) => {
                        const index = offset + 1;
                        html += `
                            <tr class="b2b-pick-tier" data-variant="${variant.id}" data-tier-row="${index}">
                                <td></td>
                                <td colspan="2"><span class="b2b-pick-tier__label">Tingkat qty ${index + 1}</span></td>
                                <td class="col-qty"><input type="number" min="1" step="1" inputmode="numeric" class="form-control ${invalid(variant.id + ':' + index + ':min_qty')}" data-tier="${index}" data-key="min_qty" value="${tier.min_qty ?? ''}" placeholder="mis. 10" aria-label="Mulai qty tingkat ${index + 1}"></td>
                                <td class="col-price">
                                    <div class="input-group"><span class="input-group-text">Rp</span><input type="text" inputmode="numeric" autocomplete="off" class="form-control item-price ${invalid(variant.id + ':' + index + ':price')}" data-tier="${index}" data-key="price" value="${thousands(tier.price)}" aria-label="Harga tingkat ${index + 1}"></div>
                                    ${errorHtml(variant.id, index)}
                                </td>
                                <td class="col-delta text-end" data-delta="${index}">${deltaHtml(variant.price, tier.price)}</td>
                                <td class="col-action"><button type="button" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger" data-remove-tier title="Hapus tingkat" aria-label="Hapus tingkat"><i class="fas fa-times"></i></button></td>
                            </tr>`;
                    });

                    return html;
                }

                function renderTable() {
                    const rows = outletRows();
                    const categories = [...new Set(rows.map(v => v.category).filter(Boolean))].sort();
                    const current = $('#pick-category').val();
                    $('#pick-category').html('<option value="">Semua kategori</option>' + categories.map(c => `<option value="${esc(c)}">${esc(c)}</option>`).join(''))
                        .val(categories.includes(current) ? current : '');

                    $('#pick-body').html(rows.length
                        ? rows.map(rowsHtml).join('')
                        : '<tr class="b2b-pick-state"><td colspan="7">Outlet ini belum punya produk.</td></tr>');
                    applyFilters();
                }

                function rerenderVariant(variantId) {
                    const variant = outletRows().find(v => v.id === variantId);
                    const rows = $(`#pick-body [data-variant="${variantId}"]`);
                    // Varian yang sudah tidak ada di katalog hanya tampil selama masih dipilih.
                    if (variant) rows.first().before(rowsHtml(variant));
                    rows.remove();
                    applyFilters();
                }

                function applyFilters() {
                    const term = $('#pick-search').val().trim().toLowerCase();
                    const category = $('#pick-category').val();
                    const selectedOnly = $('#pick-selected-only').is(':checked');
                    let shown = 0, total = 0, picked = 0;

                    $('#pick-body .b2b-pick-row').each(function () {
                        const row = $(this);
                        const id = Number(row.data('variant'));
                        const selected = state.items.has(id);
                        const visible = (!term || String(row.data('search')).includes(term))
                            && (!category || row.data('category') === category)
                            && (!selectedOnly || selected);
                        row.toggle(visible);
                        $(`#pick-body .b2b-pick-tier[data-variant="${id}"]`).toggle(visible);
                        total++; if (visible) shown++; if (selected) picked++;
                    });

                    $('#pick-foot-count').html(total
                        ? `<strong>${picked}</strong> dipilih dari ${total} produk di ${esc(outletNames[state.outletId] || 'outlet ini')}${shown < total ? ` · ${shown} tampil` : ''}`
                        : '');
                    updateSummary();
                }

                // ---------- Ringkasan ----------
                function updateSummary() {
                    const perOutlet = {};
                    let missingPrice = false;
                    state.items.forEach(item => {
                        perOutlet[item.outlet_id] = (perOutlet[item.outlet_id] || 0) + 1;
                        if (item.tiers.some(t => t.price === '' || t.price === null || t.min_qty === '' || t.min_qty === null)) missingPrice = true;
                    });

                    $('.b2b-outlet-tab').each(function () { $(this).find('.b2b-outlet-tab__count').text(perOutlet[this.dataset.outlet] || ''); });
                    $('#sum-products, #product-count').text(state.items.size);
                    $('#sum-outlets').text(Object.keys(perOutlet).length);
                    $('#outlet-breakdown').html(Object.entries(perOutlet)
                        .sort((a, b) => b[1] - a[1])
                        .map(([id, count]) => `<li><button type="button" data-goto-outlet="${id}"><span><i class="fas fa-store me-2"></i>${esc(outletNames[id] || 'Outlet lain')}</span><strong>${count}</strong></button></li>`)
                        .join(''));

                    const start = $('#start_date').val();
                    const end = $('#end_date').val();
                    const validPeriod = !!(start && end && end >= start);
                    $('#sum-duration').text(validPeriod ? `${(Math.round((new Date(end) - new Date(start)) / 86400000) + 1).toLocaleString('id-ID')} hari` : '—');
                    const checks = { customer: !!$('#b2b_customer_id').val(), period: validPeriod, items: state.items.size > 0, prices: state.items.size > 0 && !missingPrice };
                    Object.entries(checks).forEach(([key, done]) => $(`[data-check="${key}"]`).toggleClass('is-done', done));
                }

                // ---------- Interaksi ----------
                const variantOf = id => outletRows().find(v => v.id === id);

                function select(variantId, focus = true) {
                    const variant = variantOf(variantId);
                    if (!variant || state.items.has(variantId)) return;
                    state.items.set(variantId, { ...variant, outlet_id: Number(state.outletId), tiers: [{ id: null, min_qty: 1, price: '' }] });
                    rerenderVariant(variantId);
                    if (focus) $(`#pick-body .b2b-pick-row[data-variant="${variantId}"] .item-price`).trigger('focus');
                }

                function unselect(variantId) {
                    state.items.delete(variantId);
                    Object.keys(state.errors).filter(key => key.startsWith(variantId + ':')).forEach(key => delete state.errors[key]);
                    rerenderVariant(variantId);
                }

                $('.b2b-outlet-tab').on('click', function () { loadOutlet(Number(this.dataset.outlet)); });
                $('#outlet-breakdown').on('click', '[data-goto-outlet]', function () { loadOutlet(Number(this.dataset.gotoOutlet)); });
                $('#pick-body').on('click', '[data-retry]', event => { event.preventDefault(); loadOutlet(state.outletId); });
                $('#pick-search').on('input', applyFilters);
                $('#pick-category, #pick-selected-only').on('change', applyFilters);

                $('#pick-body').on('change', '[data-toggle]', function () {
                    const id = Number($(this).closest('tr').data('variant'));
                    this.checked ? select(id) : unselect(id);
                });
                // Klik di area baris (bukan input/tombol) = centang/lepas.
                $('#pick-body').on('click', '.b2b-pick-row td', function (event) {
                    if ($(event.target).closest('input, button, a').length) return;
                    const id = Number($(this).closest('tr').data('variant'));
                    state.items.has(id) ? unselect(id) : select(id);
                });

                // Mengetik harga pada produk yang belum dicentang otomatis memilihnya.
                $('#pick-body').on('input', '[data-key]', function () {
                    const input = $(this);
                    const id = Number(input.closest('tr').data('variant'));
                    const tier = Number(input.data('tier'));
                    const key = input.data('key');
                    if (!state.items.has(id)) {
                        const value = input.val();
                        select(id, false);
                        const fresh = $(`#pick-body .b2b-pick-row[data-variant="${id}"] .item-price`);
                        fresh.trigger('focus').val(value).trigger('input');
                        return;
                    }
                    const item = state.items.get(id);
                    item.tiers[tier][key] = key === 'price' ? formatPriceInput(this) : (input.val() === '' ? '' : Number(input.val()));
                    delete state.errors[`${id}:${tier}:${key}`];
                    input.removeClass('is-invalid');
                    if (key === 'price') $(`#pick-body [data-variant="${id}"] [data-delta="${tier}"]`).html(deltaHtml(item.price, item.tiers[tier].price));
                    updateSummary();
                });

                $('#pick-body').on('click', '[data-add-tier]', function () {
                    const id = Number($(this).closest('tr').data('variant'));
                    const item = state.items.get(id);
                    item.tiers.push({ id: null, min_qty: '', price: '' });
                    rerenderVariant(id);
                    $(`#pick-body .b2b-pick-tier[data-variant="${id}"][data-tier-row="${item.tiers.length - 1}"] [data-key="min_qty"]`).trigger('focus');
                });

                $('#pick-body').on('click', '[data-remove-tier]', function () {
                    const row = $(this).closest('tr');
                    const id = Number(row.data('variant'));
                    state.items.get(id).tiers.splice(Number(row.data('tierRow')), 1);
                    rerenderVariant(id);
                });

                form.on('change', '#start_date, #end_date, #b2b_customer_id', updateSummary);
                // ---------- File kontrak ----------
                const savedAttachment = @json($contract->attachment_name);
                const attachmentHint = 'PDF, JPG, PNG, atau DOC · maks. 5 MB';
                const fileSize = bytes => bytes >= 1048576
                    ? (bytes / 1048576).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' MB'
                    : Math.max(1, Math.round(bytes / 1024)).toLocaleString('id-ID') + ' KB';
                function renderAttachment() {
                    const input = $('#attachment')[0];
                    const file = input.files[0];
                    const removing = $('#remove_attachment').val() === '1';
                    const box = $('#attachment-box').removeClass('is-new is-saved is-removed');
                    const clear = $('#attachment-clear');
                    let name = 'Pilih file', hint = attachmentHint, pick = 'Pilih', icon = 'fa-file-upload', action = null;

                    if (file) {
                        box.addClass('is-new');
                        [name, hint, pick, icon] = [file.name, `${fileSize(file.size)} · siap diunggah`, 'Ganti', 'fa-file-alt'];
                        action = { html: '<i class="fas fa-times"></i>', title: 'Lepas file ini' };
                    } else if (savedAttachment && removing) {
                        box.addClass('is-removed');
                        [name, hint, icon] = [savedAttachment, 'Akan dihapus saat disimpan', 'fa-file-excel'];
                        action = { html: '<i class="fas fa-undo"></i>', title: 'Batalkan hapus' };
                    } else if (savedAttachment) {
                        box.addClass('is-saved');
                        [name, hint, pick, icon] = [savedAttachment, 'Tersimpan di kontrak ini', 'Ganti', 'fa-file-alt'];
                        action = { html: '<i class="fas fa-trash-alt"></i>', title: 'Hapus dokumen tersimpan' };
                    }

                    $('#attachment-name').text(name);
                    $('#attachment-hint').text(hint);
                    $('#attachment-pick').text(pick);
                    box.find('.b2b-file__icon i').attr('class', 'fas ' + icon);
                    clear.prop('hidden', !action).toggleClass('ob-icon-btn--danger', !removing);
                    if (action) clear.html(action.html).attr({ title: action.title, 'aria-label': action.title });
                }
                $('#attachment').on('change', function () {
                    if (this.files[0]) $('#remove_attachment').val('0');
                    renderAttachment();
                });
                $('#attachment-clear').on('click', function () {
                    const input = $('#attachment');
                    if (input[0].files.length) {
                        input.val('');
                    } else if (savedAttachment) {
                        $('#remove_attachment').val($('#remove_attachment').val() === '1' ? '0' : '1');
                    }
                    input.closest('.b2b-field').find('.b2b-error').remove();
                    input.removeClass('is-invalid');
                    renderAttachment();
                });
                // Klik area kotak (selain tombol) juga membuka pemilih file.
                $('#attachment-box').on('click', function (event) {
                    if (!$(event.target).closest('button, label, input').length) $('#attachment').trigger('click');
                });
                renderAttachment();

                // ---------- Simpan ----------
                // Bentuk items[i][...] dari state; indexMap menerjemahkan error server (items.i.field) kembali ke baris tabel.
                function buildPayload() {
                    const container = $('#items-payload').empty();
                    const indexMap = [];
                    state.items.forEach(item => item.tiers.forEach((tier, tierIndex) => {
                        const i = indexMap.length;
                        indexMap.push({ variantId: item.id, tier: tierIndex, outletId: item.outlet_id });
                        [['id', tier.id ?? ''], ['variant_id', item.id], ['min_qty', tier.min_qty], ['price', tier.price]].forEach(([field, value]) => {
                            container.append($('<input type="hidden">').attr('name', `items[${i}][${field}]`).val(value ?? ''));
                        });
                    }));
                    return indexMap;
                }

                function showItemErrors(errors, indexMap) {
                    state.errors = {};
                    let firstOutlet = null;
                    Object.entries(errors || {}).forEach(([key, messages]) => {
                        const match = key.match(/^items\.(\d+)\.(\w+)$/);
                        if (!match || !indexMap[match[1]]) return;
                        const target = indexMap[match[1]];
                        state.errors[`${target.variantId}:${target.tier}:${match[2]}`] = true;
                        state.errors[`${target.variantId}:${target.tier}`] = [].concat(messages)[0];
                        firstOutlet ??= target.outletId;
                    });
                    if (firstOutlet !== null) {
                        $('#pick-selected-only').prop('checked', true);
                        String(firstOutlet) === String(state.outletId) ? renderTable() : loadOutlet(Number(firstOutlet));
                    }
                }

                form.on('submit', function (event) {
                    event.preventDefault();
                    const indexMap = buildPayload();
                    B2b.submit(this).fail(xhr => showItemErrors(xhr.responseJSON?.errors, indexMap));
                });

                if (state.outletId) loadOutlet(state.outletId);
                updateSummary();
            });
        </script>
    @endpush
@endsection
