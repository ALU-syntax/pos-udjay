{{--
    Gaya halaman Kasir B2B (mode POS). Include setelah layouts.b2b.partials.page-styles.
    Layar ≥ 992px: halaman pas satu layar (tanpa scroll halaman), yang di-scroll hanya isi kartu.
    Layar < 992px: kartu bertumpuk dan halaman di-scroll seperti biasa.
--}}
@push('css')
    <style>
        .b2b-cashier, .b2b-checkout { --cs-warning: #b45309; --cs-warning-soft: #fef3c7; --cs-gap: 14px; }

        /* ----- Mode POS: layar penuh tanpa scroll halaman ----- */
        @media (min-width: 992px) {
            body.b2b-pos-mode { height: 100vh; overflow: hidden; }
            .b2b-pos-mode .footer { display: none; }
            .b2b-pos-mode .main-panel > .container { min-height: 0; }
            .b2b-pos-mode .main-panel > .container > .page-inner { padding: var(--cs-gap, 14px) 18px; }
            .b2b-cashier { display: flex; flex-direction: column; height: calc(100vh - var(--cs-offset, 83px) - 14px); }
            .b2b-pos { flex: 1 1 auto; min-height: 0; display: grid; grid-template-columns: minmax(0, 1fr) 390px; gap: var(--cs-gap); }
            .b2b-pos > .ob-card { display: flex; flex-direction: column; min-height: 0; height: 100%; margin-bottom: 0; }
            .b2b-product-grid { flex: 1 1 auto; min-height: 0; overflow-y: auto; }
            .b2b-order__scroll { flex: 1 1 auto; min-height: 0; overflow-y: auto; overscroll-behavior: contain; }
        }
        @media (min-width: 1400px) { .b2b-pos { grid-template-columns: minmax(0, 1fr) 420px; } }
        @media (max-width: 991.98px) {
            .b2b-pos { display: flex; flex-direction: column; gap: 1rem; }
            .b2b-pos > .ob-card { margin-bottom: 0; }
            .b2b-cashier { padding-bottom: 84px; }
        }

        /* Kaiadmin memaksa label 14px abu-abu dan .form-control { height: inherit } (keduanya !important) */
        .b2b-cashier .b2b-mini-label { display: block; margin: 0 0 .35rem; font-size: .68rem !important; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--ob-muted) !important; }

        /* ----- Kartu katalog ----- */
        .b2b-catalog__bar { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; padding: .85rem 1rem .75rem; }
        .b2b-select-icon { position: relative; flex: 0 1 230px; min-width: 170px; }
        .b2b-select-icon > i { position: absolute; top: 50%; left: .85rem; transform: translateY(-50%); font-size: .8rem; color: var(--ob-primary); pointer-events: none; }
        .b2b-cashier .b2b-select-icon .form-select {
            height: 42px; padding: 0 2.1rem 0 2.3rem; border: 1px solid var(--ob-border-strong); border-radius: var(--ob-radius-sm);
            background-color: var(--ob-surface); color: var(--ob-text); font-size: .85rem; font-weight: 600; cursor: pointer;
        }
        .b2b-cashier .b2b-select-icon .form-select:focus { border-color: var(--ob-primary); box-shadow: 0 0 0 3px rgba(21, 114, 232, .15); }
        .b2b-catalog-search { position: relative; flex: 1 1 220px; }
        .b2b-catalog-search > i { position: absolute; top: 50%; left: .85rem; transform: translateY(-50%); font-size: .8rem; color: var(--ob-muted); pointer-events: none; }
        .b2b-cashier .b2b-catalog-search .form-control {
            min-height: 42px; padding: 0 2.4rem 0 2.25rem; border: 1px solid var(--ob-border-strong); border-radius: var(--ob-radius-sm);
            background: var(--ob-surface); font-size: .85rem; line-height: 40px;
        }
        .b2b-cashier .b2b-catalog-search .form-control:focus { border-color: var(--ob-primary); box-shadow: 0 0 0 3px rgba(21, 114, 232, .15); }
        .b2b-kbd {
            position: absolute; top: 50%; right: .6rem; transform: translateY(-50%); display: inline-grid; place-items: center; min-width: 22px; height: 22px; padding: 0 .3rem;
            border: 1px solid var(--ob-border-strong); border-bottom-width: 2px; border-radius: 6px; background: var(--ob-surface-2); color: var(--ob-muted); font: 600 .7rem/1 inherit; pointer-events: none;
        }
        .b2b-catalog-search .form-control:focus ~ .b2b-kbd, .b2b-catalog-search .form-control:not(:placeholder-shown) ~ .b2b-kbd { display: none; }
        @media (hover: none) { .b2b-kbd { display: none; } }
        .b2b-catalog__count { flex-shrink: 0; font-size: .76rem; font-weight: 600; color: var(--ob-muted); white-space: nowrap; }

        .b2b-cat-tabs { display: flex; flex-shrink: 0; gap: .35rem; overflow-x: auto; padding: 0 1rem .75rem; border-bottom: 1px solid var(--ob-border); scrollbar-width: none; }
        .b2b-cat-tabs::-webkit-scrollbar { display: none; }
        .b2b-cat-tab {
            display: inline-flex; align-items: center; gap: .4rem; flex-shrink: 0; min-height: 34px; padding: .25rem .8rem;
            border: 1px solid var(--ob-border-strong); border-radius: 999px; background: var(--ob-surface);
            color: var(--ob-text-2); font-size: .78rem; font-weight: 600; white-space: nowrap;
            transition: border-color .15s ease, background-color .15s ease, color .15s ease;
        }
        .b2b-cat-tab:hover { border-color: var(--ob-primary); color: var(--ob-primary); }
        .b2b-cat-tab[aria-selected="true"] { border-color: var(--ob-primary); background: var(--ob-primary); color: #fff; }
        .b2b-cat-tab__count { display: inline-grid; place-items: center; min-width: 1.25rem; height: 1.25rem; padding: 0 .3rem; border-radius: 999px; background: var(--ob-surface-2); color: var(--ob-muted); font-size: .66rem; font-weight: 700; }
        .b2b-cat-tab[aria-selected="true"] .b2b-cat-tab__count { background: rgba(255, 255, 255, .25); color: #fff; }

        .b2b-product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(158px, 1fr)); gap: .6rem; padding: .85rem 1rem 1rem; align-content: start; }
        @media (max-width: 575.98px) { .b2b-product-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); padding: .75rem; } }
        .b2b-product {
            position: relative; display: flex; flex-direction: column; gap: .15rem; min-height: 104px; padding: .7rem .75rem .65rem;
            border: 1px solid var(--ob-border); border-radius: 12px; background: var(--ob-surface); text-align: left;
            transition: border-color .15s ease, box-shadow .15s ease, transform .06s ease;
        }
        .b2b-product:hover { border-color: var(--ob-primary); box-shadow: 0 4px 14px rgba(21, 114, 232, .1); }
        .b2b-product:active { transform: scale(.98); }
        .b2b-product:focus-visible { outline: 2px solid var(--ob-primary); outline-offset: 2px; }
        .b2b-product.in-cart { border-color: var(--ob-primary); box-shadow: 0 0 0 1px var(--ob-primary); background: #f8fbff; }
        .b2b-product__cat { padding-right: 1.8rem; overflow: hidden; font-size: .62rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--ob-muted); text-overflow: ellipsis; white-space: nowrap; }
        .b2b-product__name { font-size: .84rem; font-weight: 600; line-height: 1.3; color: var(--ob-text); overflow-wrap: anywhere; }
        .b2b-product__foot { display: flex; align-items: flex-end; justify-content: space-between; gap: .4rem; margin-top: auto; padding-top: .45rem; }
        .b2b-product__price { font-size: .88rem; font-weight: 700; color: var(--ob-text); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .b2b-product__qty {
            position: absolute; top: .5rem; right: .5rem; display: inline-grid; place-items: center; min-width: 24px; height: 24px; padding: 0 .4rem;
            border-radius: 999px; background: var(--ob-primary); color: #fff; font-size: .72rem; font-weight: 700; font-variant-numeric: tabular-nums;
        }
        .b2b-stock { display: inline-flex; align-items: center; gap: .3rem; font-size: .68rem; font-weight: 600; color: var(--ob-muted); white-space: nowrap; }
        .b2b-stock::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #22c55e; }
        .b2b-stock--low { color: var(--cs-warning); }
        .b2b-stock--low::before { background: #f59e0b; }
        .b2b-stock--out { color: var(--ob-danger); }
        .b2b-stock--out::before { background: var(--ob-danger); }
        .b2b-product-grid .b2b-empty { grid-column: 1 / -1; }
        .b2b-skeleton { min-height: 104px; border-radius: 12px; background: linear-gradient(90deg, var(--ob-surface-2) 25%, var(--ob-border) 50%, var(--ob-surface-2) 75%) 0 0 / 200% 100%; animation: b2b-shimmer 1.2s infinite linear; }
        @keyframes b2b-shimmer { to { background-position: -200% 0; } }
        @media (prefers-reduced-motion: reduce) { .b2b-skeleton { animation: none; } .b2b-product { transition: none; } }

        /* ----- Panel pesanan ----- */
        .b2b-order__customer { flex-shrink: 0; padding: .85rem 1rem .8rem; border-bottom: 1px solid var(--ob-border); }
        .b2b-cashier .select2-container--default .select2-selection--single { height: 42px; }
        .b2b-cashier .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 40px; font-size: .86rem; font-weight: 600; }
        .b2b-cashier .select2-container--default .select2-selection--single .select2-selection__placeholder { font-weight: 400; color: #94a3b8; }
        .b2b-cashier .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; }
        .b2b-cust-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .55rem; }
        .b2b-cust-chips:empty { display: none; }
        .b2b-cust-hint { font-size: .74rem; color: var(--ob-muted); }
        .b2b-info-chip {
            display: inline-flex; align-items: center; gap: .35rem; max-width: 100%; padding: .2rem .55rem; border: 1px solid var(--ob-border); border-radius: 7px;
            background: var(--ob-surface-2); color: var(--ob-text-2); font-size: .72rem; font-weight: 600; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .b2b-info-chip i { font-size: .66rem; }
        a.b2b-info-chip:hover { border-color: currentColor; }
        .b2b-info-chip.is-success { border-color: transparent; background: var(--ob-success-soft); color: var(--ob-success); }
        .b2b-info-chip.is-warning { border-color: transparent; background: var(--cs-warning-soft); color: var(--cs-warning); }
        .b2b-info-chip.is-danger { border-color: transparent; background: var(--ob-danger-soft); color: var(--ob-danger); }

        .b2b-order__head { display: flex; flex-shrink: 0; align-items: center; justify-content: space-between; gap: .5rem; padding: .65rem 1rem; border-bottom: 1px solid var(--ob-border); }
        .b2b-order__title { display: flex; align-items: center; gap: .45rem; margin: 0; font-size: .85rem; font-weight: 700; color: var(--ob-text); }
        .b2b-order__title > i { font-size: .8rem; color: var(--ob-primary); }
        .b2b-link-btn { padding: .3rem .1rem; border: 0; background: none; font-size: .76rem; font-weight: 600; color: var(--ob-muted); }
        .b2b-link-btn:hover { color: var(--ob-danger); }

        .b2b-cart { display: flex; flex-direction: column; }
        .b2b-cart .b2b-empty { padding: 2rem 1.25rem; }
        .b2b-line { padding: .7rem 1rem; border-bottom: 1px solid var(--ob-border); }
        .b2b-line.warn { background: #fffbeb; }
        .b2b-line__main { display: flex; align-items: flex-start; gap: .5rem; }
        .b2b-line__info { flex: 1; min-width: 0; }
        .b2b-line__name { font-size: .84rem; font-weight: 600; line-height: 1.3; color: var(--ob-text); overflow-wrap: anywhere; }
        .b2b-line__price { display: flex; flex-wrap: wrap; align-items: center; gap: .1rem .4rem; margin-top: .15rem; font-size: .74rem; color: var(--ob-muted); font-variant-numeric: tabular-nums; }
        .b2b-line__price s { color: #94a3b8; }
        .b2b-line__tag { display: inline-flex; align-items: center; gap: .25rem; padding: .05rem .4rem; border-radius: 6px; background: var(--ob-success-soft); color: var(--ob-success); font-size: .66rem; font-weight: 700; white-space: nowrap; }
        .b2b-line__warn { display: flex; align-items: center; gap: .35rem; margin-top: .25rem; font-size: .72rem; font-weight: 600; color: var(--cs-warning); }
        .b2b-line__remove { display: inline-grid; place-items: center; flex-shrink: 0; width: 30px; height: 30px; margin: -.3rem -.4rem 0 0; border: 0; border-radius: 8px; background: none; color: var(--ob-muted); }
        .b2b-line__remove:hover { background: var(--ob-danger-soft); color: var(--ob-danger); }
        .b2b-line__foot { display: flex; align-items: center; gap: .55rem; margin-top: .5rem; }
        .b2b-line__total { margin-left: auto; font-size: .88rem; font-weight: 700; color: var(--ob-text); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .b2b-line__note-toggle { padding: .25rem 0; border: 0; background: none; font-size: .72rem; font-weight: 600; color: var(--ob-primary); white-space: nowrap; }
        .b2b-cashier .b2b-line__note { width: 100%; height: 34px; margin-top: .5rem; padding: 0 .65rem; border: 1px solid var(--ob-border-strong); border-radius: 8px; font-size: .78rem; }
        .b2b-cashier .b2b-line__note:focus { border-color: var(--ob-primary); box-shadow: 0 0 0 3px rgba(21, 114, 232, .15); outline: none; }

        .b2b-qty { display: inline-flex; align-items: stretch; overflow: hidden; border: 1px solid var(--ob-border-strong); border-radius: 10px; background: var(--ob-surface); }
        .b2b-qty button { width: 38px; min-height: 38px; border: 0; background: var(--ob-surface-2); color: var(--ob-text-2); font-size: .75rem; }
        .b2b-qty button:hover { background: var(--ob-primary-soft); color: var(--ob-primary); }
        .b2b-qty input { width: 54px; border: 0; border-inline: 1px solid var(--ob-border); text-align: center; font-size: .88rem; font-weight: 700; color: var(--ob-text); font-variant-numeric: tabular-nums; -moz-appearance: textfield; }
        .b2b-qty input:focus { outline: none; background: var(--ob-primary-soft); }
        .b2b-qty input::-webkit-outer-spin-button, .b2b-qty input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

        /* Ringkasan + tombol */
        .b2b-order__summary { flex-shrink: 0; padding: .75rem 1rem .9rem; border-top: 1px solid var(--ob-border); border-radius: 0 0 var(--ob-radius) var(--ob-radius); background: var(--ob-surface-2); }
        .b2b-sum-row { display: flex; justify-content: space-between; gap: 1rem; padding: .1rem 0; font-size: .78rem; color: var(--ob-text-2); font-variant-numeric: tabular-nums; }
        .b2b-sum-row.is-down span:last-child { font-weight: 600; color: var(--ob-success); }
        .b2b-sum-row.is-up span:last-child { font-weight: 600; color: var(--cs-warning); }
        .b2b-sum-total { display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; margin-top: .3rem; padding-top: .45rem; border-top: 1px dashed var(--ob-border-strong); }
        .b2b-sum-total span { font-size: .82rem; font-weight: 600; color: var(--ob-text); }
        .b2b-sum-total strong { font-size: 1.3rem; font-weight: 800; letter-spacing: -.01em; color: var(--ob-text); font-variant-numeric: tabular-nums; }
        .b2b-quote-status { display: flex; align-items: center; gap: .4rem; min-height: 1.1rem; margin: .35rem 0 .6rem; font-size: .72rem; color: var(--ob-muted); }
        .b2b-quote-status.is-error { color: var(--ob-danger); }
        .b2b-quote-status.is-contract { color: var(--ob-success); }
        .b2b-submit {
            display: flex; align-items: center; justify-content: space-between; gap: .75rem; width: 100%; min-height: 50px; padding: .5rem 1rem;
            border: 0; border-radius: 12px; background: var(--ob-primary); color: #fff; font-size: .92rem; font-weight: 700;
            box-shadow: 0 6px 16px rgba(21, 114, 232, .25); transition: background-color .15s ease, box-shadow .15s ease;
        }
        .b2b-submit:hover:not(:disabled) { background: #0f5fc6; }
        .b2b-submit:focus-visible { outline: 3px solid rgba(21, 114, 232, .35); outline-offset: 2px; }
        .b2b-submit:disabled { background: var(--ob-border-strong); box-shadow: none; cursor: not-allowed; }
        .b2b-submit__total { font-variant-numeric: tabular-nums; }

        /* ----- Dialog selesaikan pesanan (gaya dasar dialog di layouts.b2b.partials.assets: .b2b-modal) ----- */
        .b2b-checkout .modal-footer .b2b-submit { flex: 1; width: auto; }
        .b2b-address { display: flex; gap: .6rem; margin-top: .6rem; padding: .65rem .8rem; border: 1px solid var(--ob-border); border-radius: var(--ob-radius-sm); background: var(--ob-surface-2); font-size: .8rem; line-height: 1.5; color: var(--ob-text-2); }
        .b2b-address > i { margin-top: .2rem; color: var(--ob-muted); }
        .b2b-address.is-missing { border-color: #fde68a; background: #fffbeb; color: var(--cs-warning); }
        .b2b-address.is-missing > i { color: var(--cs-warning); }
        .b2b-stock-alert { display: flex; gap: .6rem; padding: .65rem .8rem; border-radius: var(--ob-radius-sm); background: #fffbeb; color: var(--cs-warning); font-size: .8rem; font-weight: 600; }

        /* Bayar langsung */
        .b2b-pay { display: flex; flex-direction: column; gap: 1rem; }
        .b2b-pay[hidden], .b2b-change[hidden] { display: none; }
        .b2b-pay .b2b-segment:not(.b2b-segment--3) { grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); }
        .b2b-checkout .input-group-text { min-height: 44px; border-color: var(--ob-border-strong); background: var(--ob-surface-2); color: var(--ob-muted); font-weight: 600; }
        .b2b-checkout #cash-received { min-height: 44px; font-size: 1rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        .b2b-change {
            display: flex; align-items: center; justify-content: space-between; margin-top: .6rem; padding: .65rem .85rem;
            border-radius: var(--ob-radius-sm); background: var(--ob-success-soft); color: var(--ob-success); font-size: .82rem; font-weight: 600;
        }
        .b2b-change strong { font-size: 1.1rem; font-variant-numeric: tabular-nums; }
        .b2b-change.is-short { background: var(--ob-danger-soft); color: var(--ob-danger); }
        .b2b-swal-change { margin-top: .9rem; padding: .7rem 1rem; border-radius: 10px; background: #ecfdf5; color: #047857; font-size: 1rem; }
        .b2b-swal-change strong { display: block; font-size: 1.5rem; }

        /* Bilah bawah tablet/HP: total + lompat ke panel pesanan */
        .b2b-mobile-bar { display: none; }
        @media (max-width: 991.98px) {
            .b2b-mobile-bar:not([hidden]) {
                position: fixed; right: 12px; bottom: 12px; left: 12px; z-index: 1030;
                display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .6rem .6rem .6rem 1rem;
                border-radius: 14px; background: #0f172a; color: #fff; box-shadow: 0 10px 30px rgba(15, 23, 42, .3);
            }
            .b2b-mobile-bar small { display: block; font-size: .72rem; color: #cbd5e1; }
            .b2b-mobile-bar strong { font-size: 1.05rem; font-variant-numeric: tabular-nums; }
            .b2b-mobile-bar .btn { min-height: 42px; border-radius: 10px; }
        }
    </style>
@endpush
