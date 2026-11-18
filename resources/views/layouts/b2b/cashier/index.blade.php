@extends('layouts.app')

@section('content')
    @include('layouts.b2b.partials.assets')
    @include('layouts.b2b.partials.page-styles')
    @include('layouts.b2b.cashier.partials.styles')
    @include('layouts.b2b.partials.datepicker')

    {{-- Mode POS: dijalankan sebelum skrip Kaiadmin supaya sidebar langsung tertutup dan tombol toggle-nya ikut menyesuaikan. --}}
    <script>
        document.body.classList.add('b2b-pos-mode');
        if (window.innerWidth >= 992) document.querySelector('.wrapper')?.classList.add('sidebar_minimize');
    </script>

    <div class="main-content ob-page b2b-cashier" id="b2b-cashier">
        <h1 class="visually-hidden">Kasir B2B</h1>

        @if ($outlets->isEmpty())
            <div class="ob-notice" role="alert">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <span>Akun Anda belum punya akses outlet. Minta admin menambahkan outlet di pengaturan user.</span>
            </div>
        @else
            <div class="b2b-pos">
                {{-- Katalog: outlet, cari, kategori, produk --}}
                <section class="ob-card b2b-catalog" aria-label="Katalog produk">
                    <div class="b2b-catalog__bar">
                        <div class="b2b-select-icon">
                            <i class="fas fa-store" aria-hidden="true"></i>
                            <select id="outlet" class="form-select" aria-label="Outlet">
                                @foreach ($outlets as $outlet)
                                    <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="b2b-catalog-search">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input id="product-search" type="search" class="form-control" placeholder="Cari produk atau varian" autocomplete="off" aria-label="Cari produk" aria-keyshortcuts="/">
                            <kbd class="b2b-kbd" title="Tekan / untuk mencari">/</kbd>
                        </div>
                        <span class="b2b-catalog__count" id="catalog-desc" aria-live="polite"></span>
                    </div>
                    <div id="category-chips" class="b2b-cat-tabs" role="tablist" aria-label="Filter kategori"></div>
                    <div id="product-grid" class="b2b-product-grid" aria-live="polite"></div>
                </section>

                {{-- Pesanan: customer, keranjang, total --}}
                <aside class="ob-card b2b-order" id="order-panel" aria-label="Pesanan">
                    <div class="b2b-order__customer">
                        <label for="customer" class="b2b-mini-label">Customer</label>
                        <select id="customer" class="form-select"></select>
                        <div id="customer-info" class="b2b-cust-chips" aria-live="polite"></div>
                    </div>

                    <div class="b2b-order__head">
                        <h2 class="b2b-order__title"><i class="fas fa-receipt" aria-hidden="true"></i> Pesanan <span id="cart-count" class="b2b-count">0</span></h2>
                        <button type="button" id="clear-cart" class="b2b-link-btn" hidden><i class="fas fa-trash-alt me-1" aria-hidden="true"></i>Kosongkan</button>
                    </div>

                    <div class="b2b-order__scroll">
                        <div id="cart-list" class="b2b-cart"></div>
                    </div>

                    <div class="b2b-order__summary">
                        <div class="b2b-sum-row"><span class="js-sum-subtotal-label">Subtotal</span><span class="js-sum-subtotal">Rp 0</span></div>
                        <div class="b2b-sum-row js-sum-delta-row" hidden><span class="js-sum-delta-label"></span><span class="js-sum-delta"></span></div>
                        <div class="b2b-sum-total"><span>Total</span><strong class="js-sum-total">Rp 0</strong></div>
                        <div id="quote-status" class="b2b-quote-status" role="status"></div>

                        @can('create b2b/cashier')
                            <button type="button" id="checkout" class="b2b-submit" disabled aria-keyshortcuts="F9">
                                <span>Lanjutkan<i class="fas fa-arrow-right ms-2" aria-hidden="true"></i></span>
                                <span class="b2b-submit__total js-sum-total-button"></span>
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
                    <strong class="js-sum-total">Rp 0</strong>
                </div>
                <a href="#order-panel" class="btn ob-btn ob-btn-primary" id="mobile-bar-link">Lihat pesanan <i class="fas fa-arrow-down ms-1" aria-hidden="true"></i></a>
            </div>

            @can('create b2b/cashier')
                {{-- Dialog selesaikan pesanan: jatuh tempo, pengiriman, catatan. Dipindah ke <body> saat halaman dimuat. --}}
                <div class="modal fade b2b-modal b2b-checkout" id="checkout-modal" tabindex="-1" aria-labelledby="checkout-title" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content ob-page b2b-form">
                            <div class="modal-header">
                                <div>
                                    <h2 class="b2b-modal__title" id="checkout-title">Selesaikan Pesanan</h2>
                                    <p class="b2b-modal__sub" id="checkout-sub"></p>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body">
                                <div class="b2b-modal__total">
                                    <div class="b2b-sum-row"><span class="js-sum-subtotal-label">Subtotal</span><span class="js-sum-subtotal">Rp 0</span></div>
                                    <div class="b2b-sum-row js-sum-delta-row" hidden><span class="js-sum-delta-label"></span><span class="js-sum-delta"></span></div>
                                    <div class="b2b-sum-total"><span>Total</span><strong class="js-sum-total">Rp 0</strong></div>
                                </div>
                                <div id="stock-alert" class="b2b-stock-alert" hidden>
                                    <i class="fas fa-exclamation-triangle mt-1" aria-hidden="true"></i>
                                    <span id="stock-alert-text"></span>
                                </div>

                                <div>
                                    <span class="form-label">Pembayaran</span>
                                    <div class="b2b-segment" role="radiogroup" aria-label="Jenis pembayaran">
                                        <label><input type="radio" name="payment_type" value="credit" checked> <span><i class="fas fa-calendar-alt" aria-hidden="true"></i> Tempo</span></label>
                                        <label><input type="radio" name="payment_type" value="immediate"> <span><i class="fas fa-hand-holding-usd" aria-hidden="true"></i> Bayar langsung</span></label>
                                    </div>
                                </div>

                                {{-- Tempo: jatuh tempo pilihan kasir --}}
                                <div id="credit-fields">
                                    <label for="due-date-display" class="form-label">Jatuh tempo <span class="b2b-required">*</span></label>
                                    <div class="b2b-range">
                                        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                                        <input id="due-date-display" type="text" class="form-control" readonly placeholder="Pilih tanggal jatuh tempo" aria-describedby="due-date-hint">
                                        <span class="b2b-range__days" id="due-date-days"></span>
                                    </div>
                                    <input type="hidden" id="due-date">
                                    <div class="b2b-quick-days" role="group" aria-label="Pilih cepat jatuh tempo">
                                        @foreach ([7, 14, 30, 45] as $days)
                                            <button type="button" data-days="{{ $days }}">{{ $days }} hari</button>
                                        @endforeach
                                    </div>
                                    <small id="due-date-hint" class="b2b-help"></small>
                                </div>

                                {{-- Bayar langsung: metode dari master pembayaran POS --}}
                                <div id="immediate-fields" class="b2b-pay" hidden>
                                    @if (empty($paymentMethods))
                                        <div class="b2b-stock-alert">
                                            <i class="fas fa-exclamation-triangle mt-1" aria-hidden="true"></i>
                                            <span>Belum ada metode pembayaran aktif. Tambahkan dulu di menu master pembayaran.</span>
                                        </div>
                                    @else
                                        <div>
                                            <span class="form-label">Metode <span class="b2b-required">*</span></span>
                                            <div class="b2b-segment {{ count($paymentMethods) === 3 ? 'b2b-segment--3' : '' }}" role="radiogroup" aria-label="Metode pembayaran" id="pay-categories">
                                                @foreach ($paymentMethods as $category)
                                                    @php
                                                        $icon = match (true) {
                                                            $category['is_cash'] => 'fa-money-bill-wave',
                                                            (bool) preg_match('/edc|kartu|card|debit/i', $category['name']) => 'fa-credit-card',
                                                            (bool) preg_match('/transfer|bank/i', $category['name']) => 'fa-university',
                                                            (bool) preg_match('/qris/i', $category['name']) => 'fa-qrcode',
                                                            default => 'fa-wallet',
                                                        };
                                                    @endphp
                                                    <label><input type="radio" name="pay_category" value="{{ $category['id'] }}"> <span><i class="fas {{ $icon }}" aria-hidden="true"></i> {{ $category['name'] }}</span></label>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div id="pay-sub-field" hidden>
                                            <span class="form-label">Jenis <span class="b2b-required">*</span></span>
                                            <div class="b2b-quick-days b2b-pay__sub" id="pay-sub" role="group" aria-label="Jenis pembayaran"></div>
                                        </div>
                                        <div id="pay-cash-field" hidden>
                                            <label for="cash-received" class="form-label">Uang diterima <span class="b2b-optional">(kosongkan bila uang pas)</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input id="cash-received" type="text" inputmode="numeric" class="form-control" autocomplete="off" placeholder="0">
                                            </div>
                                            <div class="b2b-quick-days" id="cash-quick" role="group" aria-label="Pilih cepat uang diterima"></div>
                                            <div id="cash-change" class="b2b-change" aria-live="polite" hidden></div>
                                        </div>
                                        <div id="pay-ref-field" hidden>
                                            <label for="pay-reference" class="form-label">No. referensi <span class="b2b-optional">(opsional)</span></label>
                                            <input id="pay-reference" type="text" class="form-control" maxlength="100" autocomplete="off" placeholder="Kode approval EDC / no. referensi transfer">
                                        </div>
                                    @endif
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
                            <div class="modal-footer">
                                <button type="button" class="btn ob-btn ob-btn-outline" data-bs-dismiss="modal">Kembali</button>
                                <button type="button" id="submit-order" class="b2b-submit">
                                    <span><i class="fas fa-check-circle me-2" aria-hidden="true"></i><span class="b2b-submit__label">Simpan Pesanan</span></span>
                                    <span class="b2b-submit__total js-sum-total-button"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan
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
                // Metode bayar langsung dari master pembayaran POS: [{ id, name, is_cash, methods: [{ id, name }] }]
                const PAYMENT_METHODS = new Map(@json($paymentMethods).map(category => [category.id, category]));

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
                    payMethodId: null,   // sub metode terpilih (mis. QRIS di bawah EDC)
                };

                const number = value => Number(value || 0).toLocaleString('id-ID');
                const emptyState = (icon, title, text) => `
                    <div class="b2b-empty">
                        <span class="b2b-empty__icon"><i class="fas ${icon}" aria-hidden="true"></i></span>
                        <p class="b2b-empty__title">${title}</p>
                        ${text ? `<p class="b2b-empty__text">${text}</p>` : ''}
                    </div>`;

                // ---------- Mode POS: tinggi halaman pas satu layar ----------
                const root = document.getElementById('b2b-cashier');
                const desktop = window.matchMedia('(min-width: 992px)');
                function fitScreen() {
                    if (!desktop.matches) { root.style.removeProperty('--cs-offset'); return; }
                    window.scrollTo(0, 0);
                    root.style.setProperty('--cs-offset', Math.round(root.getBoundingClientRect().top) + 'px');
                }
                fitScreen();
                $(window).on('resize', fitScreen);

                const modalEl = document.getElementById('checkout-modal');
                if (modalEl) document.body.appendChild(modalEl);
                const checkoutModal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

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
                    $('#catalog-desc').text('Memuat...');
                    $('#category-chips').empty();
                    $('#product-grid').html('<div class="b2b-skeleton"></div>'.repeat(10));
                    return $.getJSON(urls.products, { outlet_id: outletId }).done(response => {
                        state.products = new Map((response.data || []).map(p => [p.id, p]));
                        renderCategories();
                        renderProducts();
                    }).fail(() => {
                        $('#catalog-desc').text('');
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

                function filteredProducts() {
                    const q = $('#product-search').val().trim().toLowerCase();
                    return [...state.products.values()].filter(p =>
                        (!state.category || p.category === state.category) && (!q || p.name.toLowerCase().includes(q))
                    );
                }

                function renderProducts() {
                    const list = filteredProducts();
                    $('#catalog-desc').text(state.products.size
                        ? (list.length === state.products.size ? `${number(state.products.size)} produk` : `${number(list.length)} dari ${number(state.products.size)}`)
                        : '');
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
                    placeholder: 'Cari nama, kode, atau telepon',
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
                        info.html('<span class="b2b-cust-hint">Wajib dipilih. Kontrak dan piutangnya akan tampil di sini.</span>');
                        renderAddress();
                        updateSubmit();
                        return;
                    }
                    const r = c.receivables;
                    const contract = c.contract
                        ? `<a class="b2b-info-chip is-success" href="${B2b.escape(c.contract.url)}" target="_blank" rel="noopener" title="Buka kontrak (${number(c.contract.items_count)} produk)">
                                <i class="fas fa-file-signature" aria-hidden="true"></i>${B2b.escape(c.contract.number)} · s.d. ${B2b.escape(c.contract.end_date)}
                           </a>`
                        : '<span class="b2b-info-chip"><i class="fas fa-tag" aria-hidden="true"></i>Tanpa kontrak · harga normal</span>';
                    const receivable = r.open_invoices
                        ? `<span class="b2b-info-chip ${r.overdue_invoices ? 'is-danger' : 'is-warning'}" title="${number(r.open_invoices)} invoice belum lunas">
                                <i class="fas ${r.overdue_invoices ? 'fa-exclamation-triangle' : 'fa-file-invoice-dollar'}" aria-hidden="true"></i>Piutang ${B2b.rupiah(r.outstanding)}${r.overdue_invoices ? ` · ${number(r.overdue_invoices)} telat` : ''}
                           </span>`
                        : '<span class="b2b-info-chip"><i class="fas fa-check" aria-hidden="true"></i>Tanpa piutang</span>';
                    info.html(contract + receivable);
                    renderAddress();
                    updateSubmit();
                }

                // Satu alamat per customer; kosong berarti pesanan hanya bisa "Diambil".
                function renderAddress() {
                    const address = state.customer?.address;
                    $('#address-field').toggleClass('is-missing', !address);
                    $('#shipping-address').text(address || 'Customer belum punya alamat. Lengkapi di data customer atau pilih "Diambil".');
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
                        $('#cart-list').html(emptyState('fa-shopping-basket', 'Pesanan masih kosong', 'Ketuk produk di katalog untuk menambahkannya.'));
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

                // Total tampil di panel, dialog, dan bilah bawah (class js-sum-*).
                function renderTotals() {
                    const fallbackTotal = [...state.cart.entries()].reduce((sum, [id, item]) => sum + (state.products.get(id)?.price || 0) * item.qty, 0);
                    const quote = state.quote;
                    const total = quote ? quote.grand_total : fallbackTotal;
                    // Selisih harga kontrak terhadap harga normal (minus = lebih hemat).
                    const normal = quote ? quote.items.reduce((sum, i) => sum + i.base_price * i.qty, 0) : fallbackTotal;
                    const delta = quote ? quote.subtotal - normal : 0;

                    $('.js-sum-subtotal-label').text(delta ? 'Harga normal' : 'Subtotal');
                    $('.js-sum-subtotal').text(B2b.rupiah(delta ? normal : (quote ? quote.subtotal : fallbackTotal)));
                    $('.js-sum-delta-row').prop('hidden', !delta).toggleClass('is-down', delta < 0).toggleClass('is-up', delta > 0);
                    $('.js-sum-delta-label').text(delta < 0 ? 'Hemat harga kontrak' : 'Selisih harga kontrak');
                    $('.js-sum-delta').text((delta < 0 ? '− ' : '+ ') + B2b.rupiah(Math.abs(delta)));
                    $('.js-sum-total').text(B2b.rupiah(total));
                    $('.js-sum-total-button').text(state.cart.size ? B2b.rupiah(total) : '');

                    const units = [...state.cart.values()].reduce((sum, item) => sum + item.qty, 0);
                    $('#mobile-bar-count').text(`${number(state.cart.size)} produk · ${number(units)} unit`);
                    updateMobileBar();
                    updateSubmit();
                    renderCash();
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

                const canCheckout = () => !state.submitting && !!state.customer && state.cart.size > 0 && !!state.quote;
                function updateSubmit() {
                    $('#checkout').prop('disabled', !canCheckout());
                    $('#submit-order').prop('disabled', !canCheckout());
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

                // ---------- Selesaikan pesanan ----------
                function openCheckout() {
                    if (!checkoutModal || !canCheckout()) return;
                    const shortItems = state.quote.items.filter(i => i.stock_insufficient).length;
                    const units = [...state.cart.values()].reduce((sum, item) => sum + item.qty, 0);
                    $('#checkout-sub').text(`${state.customer.name} · ${number(state.cart.size)} produk, ${number(units)} unit`);
                    $('#stock-alert').prop('hidden', !shortItems);
                    $('#stock-alert-text').text(`${shortItems} produk melebihi stok. Pesanan tetap bisa disimpan dan stok akan menjadi minus.`);
                    renderAddress();
                    renderPayment();
                    checkoutModal.show();
                }

                // ---------- Pembayaran: Tempo / Bayar langsung ----------
                const parseRupiah = value => Number(String(value || '').replace(/\D/g, '')) || 0;
                const orderTotal = () => state.quote ? state.quote.grand_total : 0;
                function paymentChoice() {
                    return {
                        type: $('input[name="payment_type"]:checked').val() || 'credit',
                        category: PAYMENT_METHODS.get(Number($('input[name="pay_category"]:checked').val())) || null,
                    };
                }
                const submitLabel = () => paymentChoice().type === 'immediate' ? 'Simpan & Terima Bayar' : 'Simpan Pesanan';

                function renderPayment() {
                    const { type, category } = paymentChoice();
                    const immediate = type === 'immediate';
                    $('#credit-fields').prop('hidden', immediate);
                    $('#immediate-fields').prop('hidden', !immediate);

                    // Sub metode: otomatis terpilih kalau hanya ada satu.
                    const methods = category?.methods || [];
                    if (!methods.some(m => m.id === state.payMethodId)) state.payMethodId = methods.length === 1 ? methods[0].id : null;
                    $('#pay-sub-field').prop('hidden', !methods.length);
                    $('#pay-sub').html(methods.map(m =>
                        `<button type="button" data-method="${m.id}" aria-pressed="${m.id === state.payMethodId}">${B2b.escape(m.name)}</button>`).join(''));
                    $('#pay-cash-field').prop('hidden', !category?.is_cash);
                    $('#pay-ref-field').prop('hidden', !category || category.is_cash);
                    renderCash();
                    if (!state.submitting) $('#submit-order .b2b-submit__label').text(submitLabel());
                }

                // Tunai: uang diterima dan kembalian hanya hitungan di layar, tidak disimpan.
                function renderCash() {
                    if (!$('#pay-cash-field').length) return;
                    const total = orderTotal();
                    const received = parseRupiah($('#cash-received').val());
                    const options = [...new Set([total, Math.ceil(total / 50000) * 50000, Math.ceil(total / 100000) * 100000])].filter(v => v > 0);
                    $('#cash-quick').html(options.map(amount =>
                        `<button type="button" data-amount="${amount}" aria-pressed="${received === amount}">${amount === total ? 'Uang pas' : B2b.rupiah(amount)}</button>`).join(''));

                    const $change = $('#cash-change');
                    if (!received || !total) { $change.prop('hidden', true); return; }
                    const short = received < total;
                    $change.prop('hidden', false).toggleClass('is-short', short)
                        .html(`<span>${short ? 'Uang kurang' : 'Kembalian'}</span><strong>${B2b.rupiah(Math.abs(received - total))}</strong>`);
                }


                function submitOrder() {
                    if (!canCheckout()) return;
                    const pay = paymentChoice();
                    const payload = {
                        outlet_id: state.outletId,
                        b2b_customer_id: state.customer.id,
                        items: cartPayload(),
                        payment_type: pay.type,
                        notes: $('#order-notes').val() || null,
                    };
                    let change = 0;

                    if (pay.type === 'credit') {
                        const dueDate = $('#due-date').val();
                        if (!dueDate || dueDate < TODAY || daysFromToday(dueDate) > MAX_DUE_DAYS) {
                            showToast('error', `Pilih jatuh tempo antara hari ini dan ${MAX_DUE_DAYS} hari ke depan.`);
                            dueDisplay.trigger('click');
                            return;
                        }
                        payload.due_date = dueDate;
                    } else {
                        if (!pay.category) {
                            showToast('error', 'Pilih metode pembayaran.');
                            return;
                        }
                        if (pay.category.methods.length && !state.payMethodId) {
                            showToast('error', `Pilih jenis pembayaran ${pay.category.name}.`);
                            return;
                        }
                        if (pay.category.is_cash) {
                            const received = parseRupiah($('#cash-received').val());
                            if (received && received < orderTotal()) {
                                showToast('error', 'Uang diterima kurang dari total pesanan.');
                                $('#cash-received').trigger('focus');
                                return;
                            }
                            change = received ? received - orderTotal() : 0;
                        }
                        payload.payment = {
                            category_payment_id: pay.category.id,
                            payment_id: state.payMethodId,
                            reference: pay.category.is_cash ? null : ($('#pay-reference').val().trim() || null),
                        };
                    }

                    const delivery = $('input[name="delivery_method"]:checked').val();
                    if (delivery === 'internal' && !state.customer.address) {
                        showToast('error', 'Customer belum punya alamat. Lengkapi alamat customer atau gunakan "Diambil".');
                        return;
                    }
                    payload.delivery_method = delivery;
                    state.submitting = true;
                    updateSubmit();
                    $('#submit-order .b2b-submit__label').html('<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Menyimpan...');
                    $.ajax({
                        url: urls.store, method: 'POST', contentType: 'application/json',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        data: JSON.stringify(payload),
                    }).done(response => {
                        clearDraft();
                        checkoutModal.hide();
                        const paid = response.data.payment_type === 'immediate';
                        Swal.fire({
                            title: paid ? 'Pesanan lunas' : 'Pesanan tersimpan',
                            html: B2b.escape(response.message)
                                + (change > 0 ? `<div class="b2b-swal-change">Kembalian <strong>${B2b.rupiah(change)}</strong></div>` : ''),
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
                        $('#submit-order .b2b-submit__label').text(submitLabel());
                        updateSubmit();
                    });
                }

                function resetOrder() {
                    state.cart.clear();
                    state.openNotes.clear();
                    state.quote = null;
                    $('#order-notes').val('');
                    setDueDays(DEFAULT_TERM_DAYS);
                    $('input[name="payment_type"][value="credit"]').prop('checked', true);
                    $('input[name="pay_category"]').prop('checked', false);
                    state.payMethodId = null;
                    $('#cash-received, #pay-reference').val('');
                    renderPayment();
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
                    return new Date(Date.UTC(y, m - 1, d)).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' });
                }
                // Date picker seragam dengan form kontrak (daterangepicker + tema .b2b-drp).
                // Dipasang di dalam modal supaya focus trap Bootstrap tidak mengganggu dan popup ikut ter-scroll.
                const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                const dueDisplay = $('#due-date-display');
                let duePicker = null;
                if (dueDisplay.length && $.fn.daterangepicker) {
                    dueDisplay.daterangepicker({
                        singleDatePicker: true,
                        autoApply: true,
                        autoUpdateInput: false,
                        parentEl: '#checkout-modal',
                        drops: 'auto',
                        minDate: moment(TODAY, 'YYYY-MM-DD'),
                        maxDate: moment(TODAY, 'YYYY-MM-DD').add(MAX_DUE_DAYS, 'days'),
                        locale: {
                            format: 'DD/MM/YYYY',
                            daysOfWeek: ['Mg', 'Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb'],
                            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                            firstDay: 1,
                        },
                    });
                    duePicker = dueDisplay.data('daterangepicker');
                    duePicker.container.addClass('b2b-drp');
                    dueDisplay
                        .on('show.daterangepicker', () => dueDisplay.addClass('is-open'))
                        .on('hide.daterangepicker', () => dueDisplay.removeClass('is-open'))
                        .on('apply.daterangepicker', (event, picker) => {
                            $('#due-date').val(picker.startDate.format('YYYY-MM-DD'));
                            renderDueDate();
                        });
                    $('#checkout-modal').on('hide.bs.modal', () => duePicker.hide());
                }

                function setDueDays(days) {
                    $('#due-date').val(addDays(TODAY, days));
                    renderDueDate();
                }
                function renderDueDate() {
                    const value = $('#due-date').val();
                    const days = value ? daysFromToday(value) : null;
                    if (value) {
                        const [y, m, d] = value.split('-').map(Number);
                        dueDisplay.val(`${String(d).padStart(2, '0')} ${shortMonths[m - 1]} ${y}`);
                        if (duePicker) { duePicker.setStartDate(moment(value, 'YYYY-MM-DD')); duePicker.setEndDate(moment(value, 'YYYY-MM-DD')); }
                    } else {
                        dueDisplay.val('');
                    }
                    $('#due-date-days').text(days === null ? '' : (days === 0 ? 'hari ini' : `${days} hari`));
                    $('.b2b-quick-days button[data-days]').each(function () {
                        this.setAttribute('aria-pressed', String(Number(this.dataset.days) === days));
                    });
                    // Input sudah menampilkan tanggal + jumlah hari; keterangan cukup nama hari dan penanda saran default.
                    $('#due-date-hint').text(value
                        ? `${formatDate(value)}${days === DEFAULT_TERM_DAYS ? ' · sesuai saran default' : ''}`
                        : `Saran default ${DEFAULT_TERM_DAYS} hari. Pilih tanggal atau tombol cepat.`);
                }
                // Hanya tombol jatuh tempo (data-days); tombol jenis & uang cepat memakai gaya yang sama tapi handler sendiri.
                $(document).on('click', '.b2b-quick-days button[data-days]', function () { setDueDays(Number(this.dataset.days)); });

                // ---------- Event ----------
                $('#outlet').on('change', function () {
                    const next = this.value;
                    const apply = () => { state.cart.clear(); state.quote = null; loadProducts(next).then(() => { renderCart(); requote(); saveDraft(); }); };
                    if (!state.cart.size) return apply();
                    Swal.fire({ title: 'Ganti outlet?', text: 'Pesanan akan dikosongkan karena produk berbeda per outlet.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, ganti', cancelButtonText: 'Batal' })
                        .then(result => result.isConfirmed ? apply() : $('#outlet').val(state.outletId));
                });
                $('#product-search')
                    .on('input', renderProducts)
                    .on('keydown', function (event) {
                        // Enter: kalau hasil cari tinggal satu, langsung masuk pesanan. Esc: kosongkan pencarian.
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            const list = filteredProducts();
                            if (list.length === 1) { addToCart(list[0].id, 1); this.value = ''; renderProducts(); }
                        } else if (event.key === 'Escape' && this.value) {
                            this.value = '';
                            renderProducts();
                        }
                    });
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
                $(document).on('change', 'input[name="delivery_method"]', function () {
                    $('#address-field').prop('hidden', $('input[name="delivery_method"]:checked').val() !== 'internal');
                });
                $(document).on('change', 'input[name="payment_type"], input[name="pay_category"]', renderPayment);
                $(document).on('click', '#pay-sub button', function () { state.payMethodId = Number(this.dataset.method); renderPayment(); });
                $(document).on('click', '#cash-quick button', function () { $('#cash-received').val(number(this.dataset.amount)); renderCash(); });
                $(document).on('input', '#cash-received', function () {
                    const amount = parseRupiah(this.value);
                    this.value = amount ? number(amount) : '';
                    renderCash();
                });
                $('#checkout').on('click', openCheckout);
                $('#submit-order').on('click', submitOrder);

                // Pintasan keyboard: "/" cari produk, F9 lanjutkan pesanan.
                $(document).on('keydown', function (event) {
                    const typing = $(event.target).is('input, textarea, select, [contenteditable="true"]');
                    if (event.key === '/' && !typing && !$('.modal.show, .swal2-container').length) {
                        event.preventDefault();
                        $('#product-search').trigger('focus');
                    } else if (event.key === 'F9') {
                        event.preventDefault();
                        $('.modal.show').length ? submitOrder() : openCheckout();
                    }
                });

                // ---------- Mulai ----------
                setDueDays(DEFAULT_TERM_DAYS);
                renderPayment();
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
