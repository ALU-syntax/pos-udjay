@extends('layouts.app')

@section('content')
    @include('layouts.b2b.partials.assets')

    <div class="main-content ob-page b2b-cashier">
        <div class="ob-header mb-3">
            <div>
                <div class="ob-header__eyebrow">Kasir B2B</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-cash-register"></i></span>
                    Pesanan Baru
                </h1>
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
            <div class="ob-card mb-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="outlet" class="form-label fw-bold">Outlet</label>
                        <select id="outlet" class="form-select form-select-lg">
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label for="customer" class="form-label fw-bold">Customer</label>
                        <select id="customer" class="form-select"></select>
                    </div>
                </div>
                <div id="customer-info" class="b2b-customer-info d-none mt-3"></div>
            </div>

            <div class="b2b-cashier__layout">
                {{-- Katalog --}}
                <section class="ob-card b2b-cashier__catalog" aria-label="Katalog produk">
                    <div class="b2b-search">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <input id="product-search" type="search" class="form-control form-control-lg" placeholder="Cari produk..." autocomplete="off">
                    </div>
                    <div id="category-chips" class="b2b-chips" role="tablist" aria-label="Filter kategori"></div>
                    <div id="product-grid" class="b2b-product-grid" aria-live="polite">
                        <div class="b2b-muted p-3">Memuat produk...</div>
                    </div>
                </section>

                {{-- Keranjang --}}
                <section class="ob-card b2b-cashier__cart" aria-label="Keranjang">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="ob-section-title mb-0"><i class="fas fa-shopping-cart" aria-hidden="true"></i> Keranjang <span id="cart-count" class="badge badge-primary ms-1">0</span></h2>
                        <button type="button" id="clear-cart" class="btn btn-link btn-sm text-danger p-0">Kosongkan</button>
                    </div>
                    <div id="cart-list" class="b2b-cart-list"></div>

                    <div class="ob-summary mt-3 mb-3">
                        <div class="ob-summary__row"><div>Subtotal</div><div id="sum-subtotal">Rp 0</div></div>
                        <div class="ob-summary__row"><div>Diskon kontrak</div><div id="sum-discount">Rp 0</div></div>
                        <div class="ob-summary__row ob-summary__row--total"><div>Total</div><div id="sum-total">Rp 0</div></div>
                    </div>
                    <div id="quote-status" class="b2b-muted mb-2"></div>

                    <div class="mb-3">
                        <label for="due-date" class="form-label fw-bold">Jatuh tempo <span class="text-danger">*</span></label>
                        <input id="due-date" type="date" class="form-control form-control-lg" required
                            min="{{ today()->toDateString() }}" max="{{ today()->addDays($maxDueDays)->toDateString() }}">
                        <div class="b2b-quick-days mt-2" role="group" aria-label="Pilih cepat jatuh tempo">
                            @foreach ([7, 14, 30, 45] as $days)
                                <button type="button" data-days="{{ $days }}">+{{ $days }} hari</button>
                            @endforeach
                        </div>
                        <small id="due-date-hint" class="text-muted d-block mt-1"></small>
                    </div>

                    <div class="mb-3">
                        <span class="form-label fw-bold d-block">Pengiriman</span>
                        <div class="b2b-segment" role="radiogroup" aria-label="Metode pengiriman">
                            <label><input type="radio" name="delivery_method" value="pickup" checked> <span><i class="fas fa-store" aria-hidden="true"></i> Diambil</span></label>
                            <label><input type="radio" name="delivery_method" value="internal"> <span><i class="fas fa-truck" aria-hidden="true"></i> Diantar</span></label>
                        </div>
                    </div>
                    <div class="mb-3 d-none" id="address-field">
                        <label for="shipping-address" class="form-label fw-bold">Alamat pengiriman</label>
                        <select id="shipping-address" class="form-select"></select>
                    </div>
                    <div class="mb-3">
                        <label for="order-notes" class="form-label fw-bold">Catatan pesanan</label>
                        <textarea id="order-notes" class="form-control" rows="2" maxlength="1000" placeholder="Opsional"></textarea>
                    </div>

                    @can('create b2b/cashier')
                        <button type="button" id="submit-order" class="btn btn-primary btn-lg w-100 b2b-submit" disabled>
                            <i class="fas fa-check-circle me-1" aria-hidden="true"></i> Konfirmasi Pesanan
                        </button>
                    @else
                        <div class="ob-notice" role="note"><i class="fas fa-lock" aria-hidden="true"></i><span>Anda hanya bisa melihat harga. Hubungi admin untuk izin membuat pesanan.</span></div>
                    @endcan
                </section>
            </div>
        @endif
    </div>

    @push('css')
        <style>
            .b2b-cashier__layout { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(320px, 1fr); gap: 1rem; align-items: start; }
            .b2b-cashier__cart { position: sticky; top: 80px; }
            @media (max-width: 991.98px) {
                .b2b-cashier__layout { grid-template-columns: minmax(0, 1fr); }
                .b2b-cashier__cart { position: static; }
            }
            .b2b-search { position: relative; margin-bottom: .75rem; }
            .b2b-search i { position: absolute; left: .9rem; top: 50%; transform: translateY(-50%); color: var(--ob-muted); }
            .b2b-search input { padding-left: 2.5rem; }
            .b2b-chips { display: flex; gap: .4rem; overflow-x: auto; padding-bottom: .5rem; margin-bottom: .5rem; }
            .b2b-chips button {
                flex-shrink: 0; min-height: 40px; padding: .35rem .9rem; border-radius: 999px;
                border: 1px solid var(--ob-border-strong); background: var(--ob-surface); font-size: .85rem; font-weight: 600; color: var(--ob-text);
            }
            .b2b-chips button[aria-selected="true"] { background: var(--ob-primary); border-color: var(--ob-primary); color: #fff; }
            .b2b-product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .6rem; max-height: 62vh; overflow-y: auto; padding: 2px; }
            .b2b-product {
                display: flex; flex-direction: column; justify-content: space-between; gap: .35rem;
                min-height: 104px; padding: .7rem .75rem; text-align: left;
                border: 1px solid var(--ob-border); border-radius: var(--ob-radius-sm); background: var(--ob-surface);
                transition: border-color .15s ease, box-shadow .15s ease, transform .05s ease;
            }
            .b2b-product:hover { border-color: var(--ob-primary); box-shadow: 0 0 0 3px var(--ob-primary-soft); }
            .b2b-product:active { transform: scale(.98); }
            .b2b-product.in-cart { border-color: var(--ob-primary); background: var(--ob-primary-soft); }
            .b2b-product__name { font-weight: 600; font-size: .88rem; line-height: 1.25; color: var(--ob-text); overflow-wrap: anywhere; }
            .b2b-product__meta { display: flex; justify-content: space-between; align-items: center; gap: .4rem; font-size: .8rem; }
            .b2b-product__price { font-weight: 700; color: var(--ob-primary); font-variant-numeric: tabular-nums; }
            .b2b-stock { font-size: .72rem; font-weight: 600; color: var(--ob-muted); white-space: nowrap; }
            .b2b-stock--low { color: var(--ob-danger); }
            .b2b-cart-list { display: flex; flex-direction: column; gap: .5rem; max-height: 46vh; overflow-y: auto; }
            .b2b-cart-item { padding: .65rem .7rem; border: 1px solid var(--ob-border); border-radius: var(--ob-radius-sm); }
            .b2b-cart-item.warn { border-color: #f59e0b; background: #fffbeb; }
            .b2b-cart-item__top { display: flex; justify-content: space-between; gap: .5rem; }
            .b2b-cart-item__name { font-weight: 600; font-size: .88rem; overflow-wrap: anywhere; }
            .b2b-cart-item__price { font-size: .8rem; color: var(--ob-muted); }
            .b2b-cart-item__price s { margin-right: .25rem; }
            .b2b-cart-item__bottom { display: flex; justify-content: space-between; align-items: center; gap: .5rem; margin-top: .5rem; }
            .b2b-qty { display: inline-flex; align-items: stretch; border: 1px solid var(--ob-border-strong); border-radius: 10px; overflow: hidden; }
            .b2b-qty button { width: 42px; min-height: 42px; border: 0; background: var(--ob-surface-2, #f1f5f9); font-size: 1rem; font-weight: 700; }
            .b2b-qty input { width: 64px; border: 0; text-align: center; font-weight: 700; font-variant-numeric: tabular-nums; }
            .b2b-qty input::-webkit-outer-spin-button, .b2b-qty input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
            .b2b-line-total { font-weight: 700; font-variant-numeric: tabular-nums; }
            .b2b-cart-item__note { margin-top: .45rem; }
            .b2b-cart-item__note input { font-size: .82rem; }
            .b2b-submit { min-height: 56px; font-weight: 700; }
            .b2b-quick-days { display: flex; flex-wrap: wrap; gap: .4rem; }
            .b2b-quick-days button {
                min-height: 40px; padding: .3rem .8rem; border-radius: 999px;
                border: 1px solid var(--ob-border-strong); background: var(--ob-surface); font-size: .82rem; font-weight: 600; color: var(--ob-text);
            }
            .b2b-quick-days button[aria-pressed="true"] { background: var(--ob-primary-soft); border-color: var(--ob-primary); color: var(--ob-primary); }
            .b2b-customer-info { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
            .b2b-customer-info .badge { font-size: .78rem; }
            .b2b-cashier .select2-container .select2-selection--single { height: 48px; }
            .b2b-cashier .select2-container .select2-selection--single .select2-selection__rendered { line-height: 46px; font-size: 1rem; }
            .b2b-cashier .select2-container .select2-selection--single .select2-selection__arrow { height: 46px; }
        </style>
    @endpush

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

                const state = {
                    outletId: null,
                    products: new Map(),
                    category: '',
                    customer: null,
                    cart: new Map(),      // variantId -> { qty, notes }
                    quote: null,
                    quoteSeq: 0,
                    submitting: false,
                };

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
                    $('#product-grid').html('<div class="b2b-muted p-3">Memuat produk...</div>');
                    return $.getJSON(urls.products, { outlet_id: outletId }).done(response => {
                        state.products = new Map((response.data || []).map(p => [p.id, p]));
                        renderCategories();
                        renderProducts();
                    }).fail(() => $('#product-grid').html('<div class="text-danger p-3">Gagal memuat produk.</div>'));
                }

                function renderCategories() {
                    const categories = [...new Set([...state.products.values()].map(p => p.category).filter(Boolean))].sort();
                    const chips = [{ value: '', label: 'Semua' }, ...categories.map(c => ({ value: c, label: c }))];
                    if (!categories.includes(state.category)) state.category = '';
                    $('#category-chips').html(chips.map(c =>
                        `<button type="button" role="tab" data-category="${B2b.escape(c.value)}" aria-selected="${c.value === state.category}">${B2b.escape(c.label)}</button>`
                    ).join(''));
                }

                function renderProducts() {
                    const q = $('#product-search').val().trim().toLowerCase();
                    const list = [...state.products.values()].filter(p =>
                        (!state.category || p.category === state.category) && (!q || p.name.toLowerCase().includes(q))
                    );
                    if (!list.length) {
                        $('#product-grid').html('<div class="b2b-muted p-3">Produk tidak ditemukan.</div>');
                        return;
                    }
                    $('#product-grid').html(list.map(p => `
                        <button type="button" class="b2b-product ${state.cart.has(p.id) ? 'in-cart' : ''}" data-variant="${p.id}" aria-label="Tambah ${B2b.escape(p.name)}">
                            <span class="b2b-product__name">${B2b.escape(p.name)}</span>
                            <span class="b2b-product__meta">
                                <span class="b2b-product__price">${B2b.rupiah(p.price)}</span>
                                <span class="b2b-stock ${p.stock <= 0 ? 'b2b-stock--low' : ''}">Stok ${p.stock.toLocaleString('id-ID')}</span>
                            </span>
                        </button>`).join(''));
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
                    if (!c) { info.addClass('d-none').empty(); renderAddresses(); updateSubmit(); return; }
                    const r = c.receivables;
                    info.removeClass('d-none').html(`
                        ${c.contract
                            ? `<span class="badge badge-success"><i class="fas fa-file-signature me-1"></i>Kontrak ${B2b.escape(c.contract.number)} · s.d. ${B2b.escape(c.contract.end_date)}</span>`
                            : '<span class="badge badge-muted">Tanpa kontrak · harga normal</span>'}
                        ${r.open_invoices ? `<span class="badge badge-warning">${r.open_invoices} invoice belum lunas · ${B2b.rupiah(r.outstanding)}</span>` : ''}
                        ${r.overdue_invoices ? `<span class="badge badge-danger"><i class="fas fa-exclamation-triangle me-1"></i>${r.overdue_invoices} lewat jatuh tempo</span>` : ''}
                    `);
                    renderAddresses();
                    updateSubmit();
                }

                function renderAddresses() {
                    const select = $('#shipping-address');
                    const addresses = state.customer?.addresses || [];
                    select.html(addresses.length
                        ? addresses.map(a => `<option value="${a.id}" ${a.is_default_shipping ? 'selected' : ''}>${B2b.escape(a.label)} — ${B2b.escape(a.full_address)}</option>`).join('')
                        : '<option value="">Customer belum punya alamat</option>');
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
                    if (!state.cart.size) {
                        $('#cart-list').html('<div class="b2b-repeater-empty">Ketuk produk di katalog untuk menambahkan.</div>');
                        renderTotals();
                        return;
                    }
                    $('#cart-list').html([...state.cart.entries()].map(([id, item]) => {
                        const p = state.products.get(id);
                        if (!p) return '';
                        const q = quoteItem(id);
                        const finalPrice = q ? q.final_price : p.price;
                        const discounted = q && q.discount_per_unit > 0;
                        const stockShort = item.qty > p.stock;
                        return `
                            <div class="b2b-cart-item ${stockShort ? 'warn' : ''}" data-variant="${id}">
                                <div class="b2b-cart-item__top">
                                    <div>
                                        <div class="b2b-cart-item__name">${B2b.escape(p.name)}</div>
                                        <div class="b2b-cart-item__price">
                                            ${discounted ? `<s>${B2b.rupiah(p.price)}</s>` : ''}${B2b.rupiah(finalPrice)} / unit
                                            ${discounted ? `<span class="badge badge-success ms-1">${B2b.escape(q.discount_label)}</span>` : ''}
                                        </div>
                                        ${stockShort ? `<div class="small text-warning fw-bold"><i class="fas fa-exclamation-triangle"></i> Stok tinggal ${p.stock.toLocaleString('id-ID')}, stok akan minus</div>` : ''}
                                    </div>
                                    <button type="button" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger" data-remove title="Hapus" aria-label="Hapus ${B2b.escape(p.name)}"><i class="fas fa-times"></i></button>
                                </div>
                                <div class="b2b-cart-item__bottom">
                                    <div class="b2b-qty">
                                        <button type="button" data-step="-1" aria-label="Kurangi">−</button>
                                        <input type="number" inputmode="numeric" min="1" value="${item.qty}" aria-label="Qty ${B2b.escape(p.name)}">
                                        <button type="button" data-step="1" aria-label="Tambah">+</button>
                                    </div>
                                    <span class="b2b-line-total">${B2b.rupiah(q ? q.line_total : p.price * item.qty)}</span>
                                </div>
                                <div class="b2b-cart-item__note">
                                    <input type="text" class="form-control form-control-sm" maxlength="255" placeholder="Catatan item (opsional)" value="${B2b.escape(item.notes)}">
                                </div>
                            </div>`;
                    }).join(''));
                    renderTotals();
                }

                function renderTotals() {
                    const fallbackTotal = [...state.cart.entries()].reduce((sum, [id, item]) => sum + (state.products.get(id)?.price || 0) * item.qty, 0);
                    const quote = state.quote;
                    $('#sum-subtotal').text(B2b.rupiah(quote ? quote.subtotal : fallbackTotal));
                    $('#sum-discount').text(quote && quote.discount_total ? '− ' + B2b.rupiah(quote.discount_total) : 'Rp 0');
                    $('#sum-total').text(B2b.rupiah(quote ? quote.grand_total : fallbackTotal));
                    updateSubmit();
                }

                // ---------- Harga dari server ----------
                let quoteTimer = null;
                function cartPayload() {
                    return [...state.cart.entries()].map(([id, item]) => ({ variant_id: id, qty: item.qty, notes: item.notes || null }));
                }

                function requote() {
                    clearTimeout(quoteTimer);
                    state.quote = null;
                    if (!state.customer || !state.cart.size) {
                        $('#quote-status').text(state.cart.size && !state.customer ? 'Pilih customer untuk menghitung harga kontrak.' : '');
                        renderCart();
                        return;
                    }
                    $('#quote-status').html('<span class="spinner-border spinner-border-sm me-1"></span>Menghitung harga...');
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
                            $('#quote-status').text(state.quote.contract ? `Harga mengikuti kontrak ${state.quote.contract.number}.` : 'Harga normal (customer tanpa kontrak berlaku).');
                            renderCart();
                        }).fail(xhr => {
                            if (seq !== state.quoteSeq) return;
                            const errors = xhr.responseJSON?.errors;
                            $('#quote-status').html(`<span class="text-danger">${B2b.escape(errors ? Object.values(errors)[0][0] : 'Gagal menghitung harga.')}</span>`);
                            updateSubmit();
                        });
                    }, 300);
                }

                function updateSubmit() {
                    $('#submit-order').prop('disabled', state.submitting || !state.customer || !state.cart.size || !state.quote);
                }

                // ---------- Simpan pesanan ----------
                function submitOrder() {
                    const dueDate = $('#due-date').val();
                    if (!dueDate || dueDate < TODAY || daysFromToday(dueDate) > MAX_DUE_DAYS) {
                        showToast('error', `Pilih jatuh tempo antara hari ini dan ${MAX_DUE_DAYS} hari ke depan.`);
                        $('#due-date').trigger('focus');
                        return;
                    }
                    const delivery = $('input[name="delivery_method"]:checked').val();
                    if (delivery === 'internal' && !$('#shipping-address').val()) {
                        showToast('error', 'Pilih alamat pengiriman atau gunakan "Diambil".');
                        return;
                    }
                    const shortItems = state.quote.items.filter(i => i.stock_insufficient).length;
                    Swal.fire({
                        title: 'Konfirmasi pesanan?',
                        html: `<div class="text-start">
                                <div><strong>${B2b.escape(state.customer.name)}</strong></div>
                                <div>${state.cart.size} item · Total <strong>${B2b.rupiah(state.quote.grand_total)}</strong></div>
                                <div>Jatuh tempo <strong>${B2b.escape(formatDate(dueDate))}</strong> (${daysFromToday(dueDate)} hari)</div>
                                ${shortItems ? `<div class="text-warning mt-2"><i class="fas fa-exclamation-triangle"></i> ${shortItems} item melebihi stok. Stok akan menjadi minus.</div>` : ''}
                               </div>`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, simpan pesanan',
                        cancelButtonText: 'Periksa lagi',
                    }).then(result => {
                        if (!result.isConfirmed) return;
                        state.submitting = true;
                        updateSubmit();
                        $('#submit-order').html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...');
                        $.ajax({
                            url: urls.store, method: 'POST', contentType: 'application/json',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                            data: JSON.stringify({
                                outlet_id: state.outletId,
                                b2b_customer_id: state.customer.id,
                                items: cartPayload(),
                                delivery_method: delivery,
                                due_date: dueDate,
                                shipping_address_id: delivery === 'internal' ? Number($('#shipping-address').val()) : null,
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
                            $('#submit-order').html('<i class="fas fa-check-circle me-1"></i> Konfirmasi Pesanan');
                            updateSubmit();
                        });
                    });
                }

                function resetOrder() {
                    state.cart.clear();
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
                    Swal.fire({ title: 'Ganti outlet?', text: 'Keranjang akan dikosongkan karena produk berbeda per outlet.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, ganti', cancelButtonText: 'Batal' })
                        .then(result => result.isConfirmed ? apply() : $('#outlet').val(state.outletId));
                });
                $('#product-search').on('input', renderProducts);
                $('#category-chips').on('click', 'button', function () { state.category = this.dataset.category; renderCategories(); renderProducts(); });
                $('#product-grid').on('click', '.b2b-product', function () { addToCart(Number(this.dataset.variant), 1); });
                $('#cart-list')
                    .on('click', '[data-step]', function () { addToCart(Number($(this).closest('.b2b-cart-item').data('variant')), Number(this.dataset.step)); })
                    .on('click', '[data-remove]', function () { setQty(Number($(this).closest('.b2b-cart-item').data('variant')), 0); })
                    .on('change', '.b2b-qty input', function () { setQty(Number($(this).closest('.b2b-cart-item').data('variant')), this.value); })
                    .on('keydown', '.b2b-qty input', function (event) { if (['-', '+', 'e', 'E', '.', ','].includes(event.key)) event.preventDefault(); })
                    .on('change', '.b2b-cart-item__note input', function () {
                        const id = Number($(this).closest('.b2b-cart-item').data('variant'));
                        const item = state.cart.get(id);
                        if (item) { item.notes = this.value.trim(); saveDraft(); }
                    });
                $('#clear-cart').on('click', function () {
                    if (!state.cart.size) return;
                    Swal.fire({ title: 'Kosongkan keranjang?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, kosongkan', cancelButtonText: 'Batal' })
                        .then(result => { if (result.isConfirmed) { state.cart.clear(); cartChanged(); } });
                });
                $('input[name="delivery_method"]').on('change', function () {
                    $('#address-field').toggleClass('d-none', $('input[name="delivery_method"]:checked').val() !== 'internal');
                });
                $('#submit-order').on('click', submitOrder);

                // ---------- Mulai ----------
                setDueDays(DEFAULT_TERM_DAYS);
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
                        if (state.cart.size) showToast('success', 'Keranjang sebelumnya dipulihkan');
                    }
                    renderCart();
                    renderProducts();
                    requote();
                });
            });
        </script>
    @endpush
@endsection
