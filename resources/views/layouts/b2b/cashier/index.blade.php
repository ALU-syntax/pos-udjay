@extends('layouts.app')

@section('content')
    @include('layouts.b2b.partials.assets')
    @include('layouts.b2b.partials.page-styles')
    @include('layouts.b2b.cashier.partials.styles')

    <div class="main-content ob-page b2b-cashier">
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Kasir B2B</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-cash-register"></i></span>
                    Pesanan Baru
                </h1>
                <p class="ob-header__desc">Pilih outlet dan customer, lalu ketuk produk. Harga kontrak dihitung ulang oleh server saat pesanan disimpan.</p>
            </div>
            @can('read b2b/orders')
                <a href="{{ route('b2b/orders') }}" class="btn ob-btn ob-btn-outline"><i class="fas fa-list" aria-hidden="true"></i> Daftar Pesanan</a>
            @endcan
        </div>

        @if ($outlets->isEmpty())
            <div class="ob-notice" role="alert">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <span>Akun Anda belum punya akses outlet. Minta admin menambahkan outlet di pengaturan user.</span>
            </div>
        @else
            {{-- Langkah 1–2: outlet & customer --}}
            <div class="ob-card mb-3">
                <div class="b2b-setup__grid">
                    <div>
                        <label for="outlet" class="b2b-setup__label"><span class="b2b-step" aria-hidden="true">1</span> Outlet</label>
                        <div class="b2b-select-icon">
                            <i class="fas fa-store" aria-hidden="true"></i>
                            <select id="outlet" class="form-select">
                                @foreach ($outlets as $outlet)
                                    <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="customer" class="b2b-setup__label"><span class="b2b-step" aria-hidden="true">2</span> Customer</label>
                        <select id="customer" class="form-select"></select>
                    </div>
                </div>
                <div id="customer-info" class="b2b-cust-panel" aria-live="polite"></div>
            </div>

            <div class="b2b-cashier__layout">
                {{-- Langkah 3: katalog --}}
                <section class="ob-card b2b-catalog" aria-labelledby="catalog-title">
                    <div class="b2b-card__head">
                        <div>
                            <h2 class="b2b-card__title" id="catalog-title"><span class="b2b-step" aria-hidden="true">3</span> Pilih Produk</h2>
                            <p class="b2b-card__desc" id="catalog-desc">Memuat produk...</p>
                        </div>
                        <div class="b2b-catalog-search">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input id="product-search" type="search" class="form-control" placeholder="Cari produk atau varian" autocomplete="off" aria-label="Cari produk">
                        </div>
                    </div>
                    <div id="category-chips" class="b2b-cat-tabs" role="tablist" aria-label="Filter kategori"></div>
                    <div id="product-grid" class="b2b-product-grid" aria-live="polite"></div>
                </section>

                {{-- Panel pesanan --}}
                <aside class="ob-card b2b-order" id="order-panel" aria-labelledby="order-title">
                    <div class="b2b-card__head b2b-order__head">
                        <h2 class="b2b-card__title" id="order-title"><i class="fas fa-receipt" aria-hidden="true"></i> Pesanan <span id="cart-count" class="b2b-count">0</span></h2>
                        <button type="button" id="clear-cart" class="b2b-link-btn" hidden><i class="fas fa-trash-alt me-1" aria-hidden="true"></i>Kosongkan</button>
                    </div>

                    <div class="b2b-order__scroll">
                        <div id="cart-list" class="b2b-cart"></div>

                        <div class="b2b-order__details b2b-form">
                            <div>
                                <label for="due-date" class="form-label">Jatuh tempo <span class="b2b-required">*</span></label>
                                <input id="due-date" type="date" class="form-control" required
                                    min="{{ today()->toDateString() }}" max="{{ today()->addDays($maxDueDays)->toDateString() }}">
                                <div class="b2b-quick-days" role="group" aria-label="Pilih cepat jatuh tempo">
                                    @foreach ([7, 14, 30, 45] as $days)
                                        <button type="button" data-days="{{ $days }}">{{ $days }} hari</button>
                                    @endforeach
                                </div>
                                <small id="due-date-hint" class="b2b-help"></small>
                            </div>

                            <div>
                                <span class="form-label">Pengiriman</span>
                                <div class="b2b-segment" role="radiogroup" aria-label="Metode pengiriman">
                                    <label><input type="radio" name="delivery_method" value="pickup" checked> <span><i class="fas fa-store" aria-hidden="true"></i> Diambil</span></label>
                                    <label><input type="radio" name="delivery_method" value="internal"> <span><i class="fas fa-truck" aria-hidden="true"></i> Diantar</span></label>
                                </div>
                                <div id="address-field" class="b2b-address" aria-live="polite" hidden>
                                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                    <span id="shipping-address"></span>
                                </div>
                            </div>

                            <div>
                                <label for="order-notes" class="form-label">Catatan pesanan <span class="b2b-optional">(opsional)</span></label>
                                <textarea id="order-notes" class="form-control" rows="2" maxlength="1000" placeholder="Misalnya: antar sebelum jam 9 pagi"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="b2b-order__summary">
                        <div class="b2b-sum-row" id="sum-subtotal-row"><span id="sum-subtotal-label">Subtotal</span><span id="sum-subtotal">Rp 0</span></div>
                        <div class="b2b-sum-row" id="sum-delta-row" hidden><span id="sum-delta-label"></span><span id="sum-delta"></span></div>
                        <div class="b2b-sum-total"><span>Total</span><strong id="sum-total">Rp 0</strong></div>
                        <div id="quote-status" class="b2b-quote-status" role="status"></div>

                        @can('create b2b/cashier')
                            <button type="button" id="submit-order" class="b2b-submit" disabled>
                                <span><i class="fas fa-check-circle me-2" aria-hidden="true"></i><span class="b2b-submit__label">Konfirmasi Pesanan</span></span>
                                <span class="b2b-submit__total" id="submit-total"></span>
                            </button>
                        @else
                            <div class="ob-notice mb-0" role="note"><i class="fas fa-lock" aria-hidden="true"></i><span>Anda hanya bisa melihat harga. Hubungi admin untuk izin membuat pesanan.</span></div>
                        @endcan
                    </div>
                </aside>
            </div>

            <div class="b2b-mobile-bar" id="mobile-bar" hidden>
                <div>
                    <small id="mobile-bar-count">0 item</small>
                    <strong id="mobile-bar-total">Rp 0</strong>
                </div>
                <a href="#order-panel" class="btn ob-btn ob-btn-primary" id="mobile-bar-link">Lihat pesanan <i class="fas fa-arrow-down ms-1" aria-hidden="true"></i></a>
            </div>
        @endif
    </div>

    @push('js')
        <script>
            $(function () {
                if (!$('#outlet').length) return;

                const urls = {
                    products: @json(route('b2b/cashier/products')),
                    customers: @json(route('b2b/cashier/customers')),
                    customer: @json(route('b2b/cashier/customer', ['customer' => '__ID__'])),
                    quote: @json(route('b2b/cashier/quote')),
                    store: @json(route('b2b/cashier/store')),
                };
                const csrf = @json(csrf_token());
                // Tanggal dari server (WIB), supaya jam tablet yang salah tidak menggeser saran jatuh tempo.
                const TODAY = @json(today()->toDateString());
                const DEFAULT_TERM_DAYS = @json($defaultTermDays);
                const MAX_DUE_DAYS = @json($maxDueDays);
                const STORAGE_KEY = 'b2b-cashier-draft';
                const LOW_STOCK = 5;
                const SUBMIT_LABEL = 'Konfirmasi Pesanan';

                const state = {
                    outletId: null,
                    products: new Map(),
                    category: '',
                    customer: null,
                    cart: new Map(),      // variantId -> { qty, notes }
                    openNotes: new Set(), // baris yang kolom catatannya sedang dibuka
                    quote: null,
                    quoteSeq: 0,
                    submitting: false,
                };

                const number = value => Number(value || 0).toLocaleString('id-ID');
                const initials = name => String(name || '').replace(/^(PT|CV)\.?\s+/i, '').split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase() || '?';
                const emptyState = (icon, title, text) => `
                    <div class="b2b-empty">
                        <span class="b2b-empty__icon"><i class="fas ${icon}" aria-hidden="true"></i></span>
                        <p class="b2b-empty__title">${title}</p>
                        ${text ? `<p class="b2b-empty__text">${text}</p>` : ''}
                    </div>`;

                // ---------- Draft keranjang (per perangkat) ----------
                function saveDraft() {
                    try {
                        localStorage.setItem(STORAGE_KEY, JSON.stringify({
                            outletId: state.outletId,
                            customer: state.customer ? { id: state.customer.id, text: state.customer.name + ' · ' + state.customer.code } : null,
                            cart: Array.from(state.cart.entries()),
                        }));
                    } catch (e) { /* storage tidak tersedia */ }
                }
                function loadDraft() {
                    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null'); } catch (e) { return null; }
                }
                function clearDraft() {
                    try { localStorage.removeItem(STORAGE_KEY); } catch (e) { /* abaikan */ }
                }

                // ---------- Katalog ----------
                function loadProducts(outletId) {
                    state.outletId = Number(outletId);
                    $('#catalog-desc').text('Memuat produk...');
                    $('#category-chips').empty();
                    $('#product-grid').html('<div class="b2b-skeleton"></div>'.repeat(8));
                    return $.getJSON(urls.products, { outlet_id: outletId }).done(response => {
                        state.products = new Map((response.data || []).map(p => [p.id, p]));
                        renderCategories();
                        renderProducts();
                    }).fail(() => {
                        $('#catalog-desc').text('Produk gagal dimuat.');
                        $('#product-grid').html(emptyState('fa-exclamation-triangle', 'Gagal memuat produk', 'Periksa koneksi lalu pilih ulang outlet.'));
                    });
                }

                function renderCategories() {
                    const counts = new Map();
                    state.products.forEach(p => { if (p.category) counts.set(p.category, (counts.get(p.category) || 0) + 1); });
                    const categories = [...counts.keys()].sort();
                    if (!categories.includes(state.category)) state.category = '';
                    const tabs = [{ value: '', label: 'Semua', count: state.products.size }, ...categories.map(c => ({ value: c, label: c, count: counts.get(c) }))];
                    $('#category-chips').toggle(categories.length > 0).html(tabs.map(t =>
                        `<button type="button" class="b2b-cat-tab" role="tab" data-category="${B2b.escape(t.value)}" aria-selected="${t.value === state.category}">
                            ${B2b.escape(t.label)} <span class="b2b-cat-tab__count">${number(t.count)}</span>
                        </button>`
                    ).join(''));
                }

                function stockBadge(stock) {
                    if (stock <= 0) return `<span class="b2b-stock b2b-stock--out">Habis</span>`;
                    if (stock <= LOW_STOCK) return `<span class="b2b-stock b2b-stock--low">Sisa ${number(stock)}</span>`;
                    return `<span class="b2b-stock">Stok ${number(stock)}</span>`;
                }

                function renderProducts() {
                    const q = $('#product-search').val().trim().toLowerCase();
                    const list = [...state.products.values()].filter(p =>
                        (!state.category || p.category === state.category) && (!q || p.name.toLowerCase().includes(q))
                    );
                    $('#catalog-desc').text(state.products.size
                        ? (list.length === state.products.size ? `${number(state.products.size)} produk di outlet ini` : `${number(list.length)} dari ${number(state.products.size)} produk`)
                        : 'Outlet ini belum punya produk aktif.');
                    if (!list.length) {
                        $('#product-grid').html(state.products.size
                            ? emptyState('fa-search', 'Produk tidak ditemukan', 'Coba kata kunci lain atau pilih kategori "Semua".')
                            : emptyState('fa-box-open', 'Belum ada produk', 'Tambahkan produk aktif untuk outlet ini di menu produk.'));
                        return;
                    }
                    $('#product-grid').html(list.map(p => {
                        const qty = state.cart.get(p.id)?.qty || 0;
                        return `
                        <button type="button" class="b2b-product ${qty ? 'in-cart' : ''}" data-variant="${p.id}" aria-label="Tambah ${B2b.escape(p.name)}${qty ? `, sudah ${qty} di pesanan` : ''}">
                            ${qty ? `<span class="b2b-product__qty">${number(qty)}</span>` : ''}
                            <span class="b2b-product__cat">${B2b.escape(p.category || 'Tanpa kategori')}</span>
                            <span class="b2b-product__name">${B2b.escape(p.name)}</span>
                            <span class="b2b-product__foot">
                                <span class="b2b-product__price">${B2b.rupiah(p.price)}</span>
                                ${stockBadge(p.stock)}
                            </span>
                        </button>`;
                    }).join(''));
                }

                // ---------- Customer ----------
                $('#customer').select2({
                    width: '100%',
                    placeholder: 'Cari nama, kode, atau telepon customer',
                    allowClear: true,
                    ajax: {
                        url: urls.customers, dataType: 'json', delay: 250,
                        data: params => ({ q: params.term }),
                        processResults: response => ({ results: response.data || [] }),
                    },
                }).on('select2:select', e => selectCustomer(e.params.data.id))
                  .on('select2:clear', () => { state.customer = null; renderCustomer(); requote(); saveDraft(); });

                function selectCustomer(id) {
                    return $.getJSON(urls.customer.replace('__ID__', id)).done(response => {
                        state.customer = response.data;
                        renderCustomer();
                        requote();
                        saveDraft();
                    }).fail(() => showToast('error', 'Customer tidak ditemukan atau nonaktif'));
                }

                function renderCustomer() {
                    const c = state.customer;
                    const info = $('#customer-info');
                    if (!c) {
                        info.html('<div class="b2b-cust-panel__empty"><i class="fas fa-user-tie" aria-hidden="true"></i> Pilih customer untuk melihat kontrak, piutang, dan alamatnya.</div>');
                        renderAddress();
                        updateSubmit();
                        return;
                    }
                    const r = c.receivables;
                    const contractFact = c.contract
                        ? `<a class="b2b-fact b2b-fact--success" href="${B2b.escape(c.contract.url)}" target="_blank" rel="noopener" title="Buka kontrak">
                                <span class="b2b-fact__icon"><i class="fas fa-file-signature" aria-hidden="true"></i></span>
                                <span class="b2b-fact__text"><strong>${B2b.escape(c.contract.number)}</strong><small>${number(c.contract.items_count)} produk · s.d. ${B2b.escape(c.contract.end_date)}</small></span>
                           </a>`
                        : `<div class="b2b-fact">
                                <span class="b2b-fact__icon"><i class="fas fa-file" aria-hidden="true"></i></span>
                                <span class="b2b-fact__text"><strong>Tanpa kontrak</strong><small>Semua produk harga normal</small></span>
                           </div>`;
                    const receivableVariant = r.overdue_invoices ? 'danger' : (r.open_invoices ? 'warning' : 'success');
                    const receivableFact = `
                        <div class="b2b-fact b2b-fact--${receivableVariant}">
                            <span class="b2b-fact__icon"><i class="fas ${r.overdue_invoices ? 'fa-exclamation-triangle' : 'fa-file-invoice-dollar'}" aria-hidden="true"></i></span>
                            <span class="b2b-fact__text">
                                <strong>${r.open_invoices ? B2b.rupiah(r.outstanding) : 'Tidak ada piutang'}</strong>
                                <small>${r.open_invoices
                                    ? `${number(r.open_invoices)} invoice belum lunas${r.overdue_invoices ? ` · ${number(r.overdue_invoices)} lewat jatuh tempo` : ''}`
                                    : 'Semua invoice lunas'}</small>
                            </span>
                        </div>`;
                    info.html(`
                        <div class="b2b-cust-panel__body">
                            <div class="b2b-cust-panel__id">
                                <span class="b2b-avatar" aria-hidden="true">${B2b.escape(initials(c.name))}</span>
                                <div><strong>${B2b.escape(c.name)}</strong><small>${B2b.escape([c.code, c.phone].filter(Boolean).join(' · '))}</small></div>
                            </div>
                            <div class="b2b-cust-panel__facts">${contractFact}${receivableFact}</div>
                        </div>`);
                    renderAddress();
                    updateSubmit();
                }

                // Satu alamat per customer; kosong berarti pesanan hanya bisa "Diambil".
                function renderAddress() {
                    const address = state.customer?.address;
                    $('#address-field').toggleClass('is-missing', !address);
                    $('#shipping-address').text(address
                        || (state.customer ? 'Customer belum punya alamat. Lengkapi di data customer atau pilih "Diambil".' : 'Pilih customer dulu untuk melihat alamat pengiriman.'));
                }

                // ---------- Keranjang ----------
                function addToCart(variantId, delta = 1) {
                    const item = state.cart.get(variantId) || { qty: 0, notes: '' };
                    item.qty = Math.max(0, item.qty + delta);
                    if (item.qty === 0) state.cart.delete(variantId); else state.cart.set(variantId, item);
                    cartChanged();
                }

                function setQty(variantId, qty) {
                    qty = Math.max(0, Math.min(1000000, parseInt(qty, 10) || 0));
                    if (qty === 0) state.cart.delete(variantId);
                    else state.cart.set(variantId, { ...(state.cart.get(variantId) || { notes: '' }), qty });
                    cartChanged();
                }

                function cartChanged() {
                    [...state.openNotes].forEach(id => { if (!state.cart.has(id)) state.openNotes.delete(id); });
                    renderCart();
                    renderProducts();
                    requote();
                    saveDraft();
                }

                function quoteItem(variantId) {
                    return state.quote?.items.find(i => i.variant_id === variantId) || null;
                }

                function renderCart() {
                    $('#cart-count').text(state.cart.size);
                    $('#clear-cart').prop('hidden', !state.cart.size);
                    if (!state.cart.size) {
                        $('#cart-list').html(emptyState('fa-shopping-basket', 'Pesanan masih kosong', 'Ketuk produk di katalog untuk menambahkannya ke pesanan.'));
                        renderTotals();
                        return;
                    }
                    $('#cart-list').html([...state.cart.entries()].map(([id, item]) => {
                        const p = state.products.get(id);
                        if (!p) return '';
                        const q = quoteItem(id);
                        const finalPrice = q ? q.final_price : p.price;
                        // Harga kontrak bisa lebih murah atau lebih mahal dari harga normal; harga normal dicoret kalau berbeda.
                        const contractPrice = q && q.contract_price;
                        const priceChanged = contractPrice && q.final_price !== q.base_price;
                        const stockShort = item.qty > p.stock;
                        const noteOpen = state.openNotes.has(id) || !!item.notes;
                        return `
                            <div class="b2b-line ${stockShort ? 'warn' : ''}" data-variant="${id}">
                                <div class="b2b-line__main">
                                    <div class="b2b-line__info">
                                        <div class="b2b-line__name">${B2b.escape(p.name)}</div>
                                        <div class="b2b-line__price">
                                            ${priceChanged ? `<s>${B2b.rupiah(q.base_price)}</s>` : ''}<span>${B2b.rupiah(finalPrice)} / unit</span>
                                            ${contractPrice ? `<span class="b2b-line__tag"><i class="fas fa-file-signature" aria-hidden="true"></i>Kontrak${q.min_qty > 1 ? ` · qty ≥ ${number(q.min_qty)}` : ''}</span>` : ''}
                                        </div>
                                        ${stockShort ? `<div class="b2b-line__warn"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i>Stok tinggal ${number(p.stock)}, stok akan minus</div>` : ''}
                                    </div>
                                    <button type="button" class="b2b-line__remove" data-remove title="Hapus dari pesanan" aria-label="Hapus ${B2b.escape(p.name)}"><i class="fas fa-times" aria-hidden="true"></i></button>
                                </div>
                                <div class="b2b-line__foot">
                                    <div class="b2b-qty">
                                        <button type="button" data-step="-1" aria-label="Kurangi ${B2b.escape(p.name)}"><i class="fas fa-minus" aria-hidden="true"></i></button>
                                        <input type="number" inputmode="numeric" min="1" value="${item.qty}" aria-label="Qty ${B2b.escape(p.name)}">
                                        <button type="button" data-step="1" aria-label="Tambah ${B2b.escape(p.name)}"><i class="fas fa-plus" aria-hidden="true"></i></button>
                                    </div>
                                    ${noteOpen ? '' : '<button type="button" class="b2b-line__note-toggle" data-note-toggle><i class="fas fa-plus me-1" aria-hidden="true"></i>Catatan</button>'}
                                    <span class="b2b-line__total">${B2b.rupiah(q ? q.line_total : p.price * item.qty)}</span>
                                </div>
                                ${noteOpen ? `<input type="text" class="b2b-line__note" maxlength="255" placeholder="Catatan item (opsional)" value="${B2b.escape(item.notes)}" aria-label="Catatan ${B2b.escape(p.name)}">` : ''}
                            </div>`;
                    }).join(''));
                    renderTotals();
                }

                function renderTotals() {
                    const fallbackTotal = [...state.cart.entries()].reduce((sum, [id, item]) => sum + (state.products.get(id)?.price || 0) * item.qty, 0);
                    const quote = state.quote;
                    const total = quote ? quote.grand_total : fallbackTotal;
                    // Selisih harga kontrak terhadap harga normal (minus = lebih hemat).
                    const normal = quote ? quote.items.reduce((sum, i) => sum + i.base_price * i.qty, 0) : fallbackTotal;
                    const delta = quote ? quote.subtotal - normal : 0;

                    $('#sum-subtotal-label').text(delta ? 'Harga normal' : 'Subtotal');
                    $('#sum-subtotal').text(B2b.rupiah(delta ? normal : (quote ? quote.subtotal : fallbackTotal)));
                    $('#sum-delta-row').prop('hidden', !delta).toggleClass('is-down', delta < 0).toggleClass('is-up', delta > 0);
                    $('#sum-delta-label').text(delta < 0 ? 'Hemat harga kontrak' : 'Selisih harga kontrak');
                    $('#sum-delta').text((delta < 0 ? '− ' : '+ ') + B2b.rupiah(Math.abs(delta)));
                    $('#sum-total').text(B2b.rupiah(total));
                    $('#submit-total').text(state.cart.size ? B2b.rupiah(total) : '');

                    const units = [...state.cart.values()].reduce((sum, item) => sum + item.qty, 0);
                    $('#mobile-bar-count').text(`${number(state.cart.size)} produk · ${number(units)} unit`);
                    $('#mobile-bar-total').text(B2b.rupiah(total));
                    updateMobileBar();
                    updateSubmit();
                }

                // ---------- Harga dari server ----------
                let quoteTimer = null;
                function cartPayload() {
                    return [...state.cart.entries()].map(([id, item]) => ({ variant_id: id, qty: item.qty, notes: item.notes || null }));
                }

                function setQuoteStatus(html, variant = '') {
                    $('#quote-status').removeClass('is-error is-contract').addClass(variant ? `is-${variant}` : '').html(html);
                }

                function requote() {
                    clearTimeout(quoteTimer);
                    state.quote = null;
                    if (!state.customer || !state.cart.size) {
                        setQuoteStatus(state.cart.size && !state.customer
                            ? '<i class="fas fa-info-circle" aria-hidden="true"></i> Pilih customer untuk menghitung harga kontrak.'
                            : '');
                        renderCart();
                        return;
                    }
                    setQuoteStatus('<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Menghitung harga...');
                    updateSubmit();
                    quoteTimer = setTimeout(() => {
                        const seq = ++state.quoteSeq;
                        $.ajax({
                            url: urls.quote, method: 'POST', contentType: 'application/json',
                            data: JSON.stringify({ _token: csrf, outlet_id: state.outletId, b2b_customer_id: state.customer.id, items: cartPayload() }),
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        }).done(response => {
                            if (seq !== state.quoteSeq) return;
                            state.quote = response.data;
                            if (state.quote.contract) {
                                setQuoteStatus(`<i class="fas fa-check-circle" aria-hidden="true"></i> Harga mengikuti kontrak ${B2b.escape(state.quote.contract.number)}`, 'contract');
                            } else {
                                setQuoteStatus('<i class="fas fa-tag" aria-hidden="true"></i> Harga normal, customer tanpa kontrak berlaku');
                            }
                            renderCart();
                        }).fail(xhr => {
                            if (seq !== state.quoteSeq) return;
                            const errors = xhr.responseJSON?.errors;
                            setQuoteStatus(`<i class="fas fa-exclamation-circle" aria-hidden="true"></i> ${B2b.escape(errors ? Object.values(errors)[0][0] : 'Gagal menghitung harga.')}`, 'error');
                            updateSubmit();
                        });
                    }, 300);
                }

                function updateSubmit() {
                    $('#submit-order').prop('disabled', state.submitting || !state.customer || !state.cart.size || !state.quote);
                }

                // Bilah bawah tablet/HP muncul selama panel pesanan belum terlihat.
                let orderPanelVisible = false;
                function updateMobileBar() {
                    $('#mobile-bar').prop('hidden', !state.cart.size || orderPanelVisible);
                }
                if ('IntersectionObserver' in window) {
                    new IntersectionObserver(entries => {
                        orderPanelVisible = entries[0].isIntersecting;
                        updateMobileBar();
                    }, { threshold: 0.15 }).observe(document.getElementById('order-panel'));
                }
                $('#mobile-bar-link').on('click', function (event) {
                    event.preventDefault();
                    document.getElementById('order-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
                });

                // ---------- Simpan pesanan ----------
                function submitOrder() {
                    const dueDate = $('#due-date').val();
                    if (!dueDate || dueDate < TODAY || daysFromToday(dueDate) > MAX_DUE_DAYS) {
                        showToast('error', `Pilih jatuh tempo antara hari ini dan ${MAX_DUE_DAYS} hari ke depan.`);
                        $('#due-date').trigger('focus');
                        return;
                    }
                    const delivery = $('input[name="delivery_method"]:checked').val();
                    if (delivery === 'internal' && !state.customer.address) {
                        showToast('error', 'Customer belum punya alamat. Lengkapi alamat customer atau gunakan "Diambil".');
                        return;
                    }
                    const shortItems = state.quote.items.filter(i => i.stock_insufficient).length;
                    const row = (label, value) => `<tr><td style="padding:.3rem 0;color:#64748b">${label}</td><td style="padding:.3rem 0;text-align:right;font-weight:600">${value}</td></tr>`;
                    Swal.fire({
                        title: 'Konfirmasi pesanan?',
                        html: `<table style="width:100%;font-size:.9rem;text-align:left">
                                ${row('Customer', B2b.escape(state.customer.name))}
                                ${row('Produk', `${number(state.cart.size)} produk`)}
                                ${row('Pengiriman', delivery === 'internal' ? 'Diantar' : 'Diambil')}
                                ${row('Jatuh tempo', `${B2b.escape(formatDate(dueDate))} (${daysFromToday(dueDate)} hari)`)}
                                <tr><td style="padding:.6rem 0 0;border-top:1px dashed #cbd5e1;font-weight:700">Total</td><td style="padding:.6rem 0 0;border-top:1px dashed #cbd5e1;text-align:right;font-size:1.15rem;font-weight:800">${B2b.rupiah(state.quote.grand_total)}</td></tr>
                               </table>
                               ${shortItems ? `<div style="margin-top:.85rem;padding:.6rem .75rem;border-radius:8px;background:#fffbeb;color:#b45309;font-size:.85rem;text-align:left"><i class="fas fa-exclamation-triangle"></i> ${shortItems} produk melebihi stok. Stok akan menjadi minus.</div>` : ''}`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, simpan pesanan',
                        cancelButtonText: 'Periksa lagi',
                    }).then(result => {
                        if (!result.isConfirmed) return;
                        state.submitting = true;
                        updateSubmit();
                        $('#submit-order .b2b-submit__label').html('<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Menyimpan...');
                        $.ajax({
                            url: urls.store, method: 'POST', contentType: 'application/json',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                            data: JSON.stringify({
                                outlet_id: state.outletId,
                                b2b_customer_id: state.customer.id,
                                items: cartPayload(),
                                delivery_method: delivery,
                                due_date: dueDate,
                                notes: $('#order-notes').val() || null,
                            }),
                        }).done(response => {
                            clearDraft();
                            Swal.fire({
                                title: 'Pesanan tersimpan',
                                text: response.message,
                                icon: 'success',
                                showDenyButton: true,
                                showCancelButton: true,
                                confirmButtonText: '<i class="fas fa-print"></i> Cetak invoice',
                                denyButtonText: 'Lihat pesanan',
                                cancelButtonText: 'Pesanan baru',
                                allowOutsideClick: false,
                            }).then(choice => {
                                if (choice.isConfirmed) window.open(response.data.invoice_url, '_blank');
                                if (choice.isDenied) { window.location.href = response.data.redirect; return; }
                                resetOrder();
                            });
                        }).fail(xhr => {
                            const errors = xhr.responseJSON?.errors;
                            showToast('error', errors ? Object.values(errors)[0][0] : (xhr.responseJSON?.message || 'Gagal menyimpan pesanan'));
                        }).always(() => {
                            state.submitting = false;
                            $('#submit-order .b2b-submit__label').text(SUBMIT_LABEL);
                            updateSubmit();
                        });
                    });
                }

                function resetOrder() {
                    state.cart.clear();
                    state.openNotes.clear();
                    state.quote = null;
                    $('#order-notes').val('');
                    setDueDays(DEFAULT_TERM_DAYS);
                    $('input[name="delivery_method"][value="pickup"]').prop('checked', true).trigger('change');
                    $('#customer').val(null).trigger('change');
                    state.customer = null;
                    renderCustomer();
                    cartChanged();
                    // Stok di katalog perlu diperbarui setelah pesanan.
                    loadProducts(state.outletId);
                }

                // ---------- Jatuh tempo ----------
                function addDays(isoDate, days) {
                    const [y, m, d] = isoDate.split('-').map(Number);
                    const date = new Date(Date.UTC(y, m - 1, d + days));
                    return date.toISOString().slice(0, 10);
                }
                function daysFromToday(isoDate) {
                    const [y1, m1, d1] = TODAY.split('-').map(Number);
                    const [y2, m2, d2] = isoDate.split('-').map(Number);
                    return Math.round((Date.UTC(y2, m2 - 1, d2) - Date.UTC(y1, m1 - 1, d1)) / 86400000);
                }
                function formatDate(isoDate) {
                    const [y, m, d] = isoDate.split('-').map(Number);
                    return new Date(Date.UTC(y, m - 1, d)).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' });
                }
                function setDueDays(days) {
                    $('#due-date').val(addDays(TODAY, days));
                    renderDueDate();
                }
                function renderDueDate() {
                    const value = $('#due-date').val();
                    const days = value ? daysFromToday(value) : null;
                    $('.b2b-quick-days button').each(function () {
                        this.setAttribute('aria-pressed', String(Number(this.dataset.days) === days));
                    });
                    $('#due-date-hint').text(value
                        ? `${formatDate(value)} · ${days === 0 ? 'hari ini' : days + ' hari dari hari ini'}${days === DEFAULT_TERM_DAYS ? ' (saran default)' : ''}`
                        : `Saran default ${DEFAULT_TERM_DAYS} hari. Pilih tanggal atau tombol cepat.`);
                }
                $('.b2b-quick-days').on('click', 'button', function () { setDueDays(Number(this.dataset.days)); });
                $('#due-date').on('change input', renderDueDate);

                // ---------- Event ----------
                $('#outlet').on('change', function () {
                    const next = this.value;
                    const apply = () => { state.cart.clear(); state.quote = null; loadProducts(next).then(() => { renderCart(); requote(); saveDraft(); }); };
                    if (!state.cart.size) return apply();
                    Swal.fire({ title: 'Ganti outlet?', text: 'Pesanan akan dikosongkan karena produk berbeda per outlet.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, ganti', cancelButtonText: 'Batal' })
                        .then(result => result.isConfirmed ? apply() : $('#outlet').val(state.outletId));
                });
                $('#product-search').on('input', renderProducts);
                $('#category-chips').on('click', 'button', function () { state.category = this.dataset.category; renderCategories(); renderProducts(); });
                $('#product-grid').on('click', '.b2b-product', function () { addToCart(Number(this.dataset.variant), 1); });
                const lineId = el => Number($(el).closest('.b2b-line').data('variant'));
                $('#cart-list')
                    .on('click', '[data-step]', function () { addToCart(lineId(this), Number(this.dataset.step)); })
                    .on('click', '[data-remove]', function () { setQty(lineId(this), 0); })
                    .on('click', '[data-note-toggle]', function () {
                        const id = lineId(this);
                        state.openNotes.add(id);
                        renderCart();
                        $(`.b2b-line[data-variant="${id}"] .b2b-line__note`).trigger('focus');
                    })
                    .on('change', '.b2b-qty input', function () { setQty(lineId(this), this.value); })
                    .on('keydown', '.b2b-qty input', function (event) { if (['-', '+', 'e', 'E', '.', ','].includes(event.key)) event.preventDefault(); })
                    .on('change', '.b2b-line__note', function () {
                        const item = state.cart.get(lineId(this));
                        if (item) { item.notes = this.value.trim(); saveDraft(); }
                    });
                $('#clear-cart').on('click', function () {
                    if (!state.cart.size) return;
                    Swal.fire({ title: 'Kosongkan pesanan?', text: 'Semua produk di pesanan ini akan dihapus.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, kosongkan', cancelButtonText: 'Batal' })
                        .then(result => { if (result.isConfirmed) { state.cart.clear(); cartChanged(); } });
                });
                $('input[name="delivery_method"]').on('change', function () {
                    $('#address-field').prop('hidden', $('input[name="delivery_method"]:checked').val() !== 'internal');
                });
                $('#submit-order').on('click', submitOrder);

                // ---------- Mulai ----------
                setDueDays(DEFAULT_TERM_DAYS);
                renderCustomer();
                const draft = loadDraft();
                if (draft && draft.outletId && $(`#outlet option[value="${draft.outletId}"]`).length) {
                    $('#outlet').val(draft.outletId);
                }
                loadProducts($('#outlet').val()).then(() => {
                    if (draft && Number(draft.outletId) === state.outletId) {
                        (draft.cart || []).forEach(([id, item]) => { if (state.products.has(Number(id))) state.cart.set(Number(id), item); });
                        if (draft.customer) {
                            $('#customer').append(new Option(draft.customer.text, draft.customer.id, true, true)).trigger('change');
                            selectCustomer(draft.customer.id);
                        }
                        if (state.cart.size) showToast('success', 'Pesanan sebelumnya dipulihkan');
                    }
                    renderCart();
                    renderProducts();
                    requote();
                });
            });
        </script>
    @endpush
@endsection
