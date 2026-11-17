{{-- Gaya halaman Kasir B2B. Include setelah layouts.b2b.partials.page-styles. Ramah layar sentuh (target sentuh ≥ 40px). --}}
@push('css')
    <style>
        .b2b-cashier { --cs-warning: #b45309; --cs-warning-soft: #fef3c7; --cs-sticky-top: 88px; }

        /* ----- Langkah 1: outlet & customer ----- */
        .b2b-setup__grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 2fr); gap: 1rem; padding: 1.1rem 1.25rem; }
        @media (max-width: 767.98px) { .b2b-setup__grid { grid-template-columns: minmax(0, 1fr); } }
        /* Kaiadmin memaksa label 14px abu-abu (!important) */
        .b2b-cashier .b2b-setup__label { display: flex; align-items: center; gap: .5rem; margin: 0 0 .45rem; font-size: .8rem !important; font-weight: 600; color: var(--ob-text-2) !important; }
        .b2b-step {
            display: inline-grid; place-items: center; width: 20px; height: 20px; border-radius: 999px;
            background: var(--ob-primary-soft); color: var(--ob-primary); font-size: .68rem; font-weight: 700;
        }
        .b2b-select-icon { position: relative; }
        .b2b-select-icon > i { position: absolute; top: 50%; left: .9rem; transform: translateY(-50%); font-size: .85rem; color: var(--ob-muted); pointer-events: none; }
        .b2b-cashier .b2b-select-icon .form-select {
            height: 46px; padding: 0 2.25rem 0 2.5rem; border: 1px solid var(--ob-border-strong); border-radius: var(--ob-radius-sm);
            background-color: var(--ob-surface); color: var(--ob-text); font-size: .9rem; font-weight: 600; cursor: pointer;
        }
        .b2b-cashier .b2b-select-icon .form-select:focus { border-color: var(--ob-primary); box-shadow: 0 0 0 3px rgba(21, 114, 232, .15); }
        .b2b-cashier .select2-container--default .select2-selection--single { height: 46px; }
        .b2b-cashier .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 44px; font-size: .9rem; font-weight: 600; }
        .b2b-cashier .select2-container--default .select2-selection--single .select2-selection__placeholder { font-weight: 400; color: #94a3b8; }
        .b2b-cashier .select2-container--default .select2-selection--single .select2-selection__arrow { height: 44px; }

        /* Ringkasan customer terpilih */
        .b2b-cust-panel { border-top: 1px solid var(--ob-border); }
        .b2b-cust-panel__empty { display: flex; align-items: center; gap: .6rem; padding: .85rem 1.25rem; font-size: .82rem; color: var(--ob-muted); }
        .b2b-cust-panel__empty i { color: var(--ob-border-strong); }
        .b2b-cust-panel__body { display: flex; flex-wrap: wrap; align-items: center; gap: .9rem 1.5rem; padding: .9rem 1.25rem; }
        .b2b-cust-panel__id { display: flex; align-items: center; gap: .75rem; min-width: 0; flex: 1 1 220px; }
        .b2b-cust-panel__id strong { display: block; font-size: .95rem; font-weight: 700; color: var(--ob-text); overflow-wrap: anywhere; }
        .b2b-cust-panel__id small { display: block; font-size: .78rem; color: var(--ob-muted); }
        .b2b-cust-panel__facts { display: flex; flex-wrap: wrap; gap: .6rem; }
        .b2b-fact {
            display: flex; align-items: center; gap: .65rem; min-width: 0; padding: .5rem .8rem .5rem .55rem;
            border: 1px solid var(--ob-border); border-radius: 12px; background: var(--ob-surface-2); text-decoration: none;
        }
        a.b2b-fact:hover { border-color: var(--ob-primary); }
        .b2b-fact__icon { display: inline-grid; place-items: center; flex-shrink: 0; width: 32px; height: 32px; border-radius: 9px; background: var(--ob-border); color: var(--ob-muted); font-size: .8rem; }
        .b2b-fact__text { min-width: 0; line-height: 1.3; }
        .b2b-fact__text strong { display: block; font-size: .82rem; font-weight: 700; color: var(--ob-text); white-space: nowrap; }
        .b2b-fact__text small { display: block; font-size: .72rem; color: var(--ob-muted); white-space: nowrap; }
        .b2b-fact--success .b2b-fact__icon { background: var(--ob-success-soft); color: var(--ob-success); }
        .b2b-fact--warning .b2b-fact__icon { background: var(--cs-warning-soft); color: var(--cs-warning); }
        .b2b-fact--danger { border-color: #fecaca; background: #fef2f2; }
        .b2b-fact--danger .b2b-fact__icon { background: var(--ob-danger-soft); color: var(--ob-danger); }
        .b2b-fact--danger .b2b-fact__text small { color: var(--ob-danger); font-weight: 600; }

        /* ----- Tata letak: katalog + panel pesanan ----- */
        .b2b-cashier__layout { display: grid; grid-template-columns: minmax(0, 1fr) 400px; gap: 1.25rem; align-items: start; }
        @media (max-width: 1199.98px) { .b2b-cashier__layout { grid-template-columns: minmax(0, 1fr) 360px; } }
        @media (max-width: 991.98px) { .b2b-cashier__layout { grid-template-columns: minmax(0, 1fr); } }

        /* ----- Katalog ----- */
        .b2b-catalog .b2b-card__head { padding-bottom: .9rem; }
        .b2b-catalog-search { position: relative; flex: 1 1 260px; max-width: 340px; }
        @media (max-width: 767.98px) { .b2b-catalog-search { max-width: none; } }
        .b2b-catalog-search > i { position: absolute; top: 50%; left: .9rem; transform: translateY(-50%); font-size: .82rem; color: var(--ob-muted); pointer-events: none; }
        .b2b-cashier .b2b-catalog-search .form-control {
            /* Kaiadmin: .form-control { height: inherit !important }, jadi pakai min-height */
            min-height: 42px; padding: 0 .85rem 0 2.35rem; border: 1px solid var(--ob-border-strong); border-radius: var(--ob-radius-sm);
            background: var(--ob-surface); font-size: .875rem; line-height: 40px;
        }
        .b2b-cashier .b2b-catalog-search .form-control:focus { border-color: var(--ob-primary); box-shadow: 0 0 0 3px rgba(21, 114, 232, .15); }

        .b2b-cat-tabs { display: flex; gap: .4rem; overflow-x: auto; padding: .75rem 1.25rem; border-bottom: 1px solid var(--ob-border); scrollbar-width: thin; }
        .b2b-cat-tab {
            display: inline-flex; align-items: center; gap: .45rem; flex-shrink: 0; min-height: 38px; padding: .35rem .9rem;
            border: 1px solid var(--ob-border-strong); border-radius: 999px; background: var(--ob-surface);
            color: var(--ob-text-2); font-size: .8rem; font-weight: 600; white-space: nowrap;
            transition: border-color .15s ease, background-color .15s ease, color .15s ease;
        }
        .b2b-cat-tab:hover { border-color: var(--ob-primary); color: var(--ob-primary); }
        .b2b-cat-tab[aria-selected="true"] { border-color: var(--ob-primary); background: var(--ob-primary); color: #fff; }
        .b2b-cat-tab__count { display: inline-grid; place-items: center; min-width: 1.3rem; height: 1.3rem; padding: 0 .35rem; border-radius: 999px; background: var(--ob-surface-2); color: var(--ob-muted); font-size: .68rem; font-weight: 700; }
        .b2b-cat-tab[aria-selected="true"] .b2b-cat-tab__count { background: rgba(255, 255, 255, .25); color: #fff; }

        .b2b-product-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(168px, 1fr)); gap: .75rem;
            max-height: calc(100vh - 330px); min-height: 280px; overflow-y: auto; padding: 1.1rem 1.25rem 1.25rem; align-content: start;
        }
        @media (max-width: 991.98px) { .b2b-product-grid { max-height: none; } }
        @media (max-width: 575.98px) { .b2b-product-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; padding: .9rem; } }
        .b2b-product {
            position: relative; display: flex; flex-direction: column; gap: .2rem; min-height: 116px; padding: .8rem .85rem .75rem;
            border: 1px solid var(--ob-border); border-radius: 12px; background: var(--ob-surface); text-align: left;
            transition: border-color .15s ease, box-shadow .15s ease, transform .06s ease;
        }
        .b2b-product:hover { border-color: var(--ob-primary); box-shadow: 0 4px 14px rgba(21, 114, 232, .1); }
        .b2b-product:active { transform: scale(.98); }
        .b2b-product:focus-visible { outline: 2px solid var(--ob-primary); outline-offset: 2px; }
        .b2b-product.in-cart { border-color: var(--ob-primary); box-shadow: 0 0 0 1px var(--ob-primary); }
        .b2b-product__cat { font-size: .66rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--ob-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding-right: 1.8rem; }
        .b2b-product__name { font-size: .86rem; font-weight: 600; line-height: 1.3; color: var(--ob-text); overflow-wrap: anywhere; }
        .b2b-product__foot { display: flex; align-items: flex-end; justify-content: space-between; gap: .4rem; margin-top: auto; padding-top: .5rem; }
        .b2b-product__price { font-size: .9rem; font-weight: 700; color: var(--ob-text); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .b2b-product__qty {
            position: absolute; top: .55rem; right: .55rem; display: inline-grid; place-items: center; min-width: 24px; height: 24px; padding: 0 .4rem;
            border-radius: 999px; background: var(--ob-primary); color: #fff; font-size: .72rem; font-weight: 700; font-variant-numeric: tabular-nums;
        }
        .b2b-stock { display: inline-flex; align-items: center; gap: .3rem; font-size: .7rem; font-weight: 600; color: var(--ob-muted); white-space: nowrap; }
        .b2b-stock::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #22c55e; }
        .b2b-stock--low { color: var(--cs-warning); }
        .b2b-stock--low::before { background: #f59e0b; }
        .b2b-stock--out { color: var(--ob-danger); }
        .b2b-stock--out::before { background: var(--ob-danger); }
        .b2b-product-grid .b2b-empty { grid-column: 1 / -1; }
        .b2b-skeleton { min-height: 116px; border-radius: 12px; background: linear-gradient(90deg, var(--ob-surface-2) 25%, var(--ob-border) 50%, var(--ob-surface-2) 75%) 0 0 / 200% 100%; animation: b2b-shimmer 1.2s infinite linear; }
        @keyframes b2b-shimmer { to { background-position: -200% 0; } }
        @media (prefers-reduced-motion: reduce) { .b2b-skeleton { animation: none; } .b2b-product { transition: none; } }

        /* ----- Panel pesanan ----- */
        .b2b-order { position: sticky; top: var(--cs-sticky-top); display: flex; flex-direction: column; max-height: calc(100vh - var(--cs-sticky-top) - 16px); }
        @media (max-width: 991.98px) { .b2b-order { position: static; max-height: none; } }
        .b2b-order__head { flex-shrink: 0; padding-top: .85rem; padding-bottom: .85rem; }
        .b2b-order__scroll { flex: 1 1 auto; min-height: 0; overflow-y: auto; overscroll-behavior: contain; }
        .b2b-link-btn { padding: .3rem .1rem; border: 0; background: none; font-size: .8rem; font-weight: 600; color: var(--ob-muted); }
        .b2b-link-btn:hover { color: var(--ob-danger); }

        .b2b-cart { display: flex; flex-direction: column; }
        .b2b-cart .b2b-empty { padding: 1.75rem 1.25rem; }
        .b2b-line { padding: .85rem 1.25rem; border-bottom: 1px solid var(--ob-border); }
        .b2b-line.warn { background: #fffbeb; }
        .b2b-line__main { display: flex; align-items: flex-start; gap: .5rem; }
        .b2b-line__info { flex: 1; min-width: 0; }
        .b2b-line__name { font-size: .875rem; font-weight: 600; line-height: 1.3; color: var(--ob-text); overflow-wrap: anywhere; }
        .b2b-line__price { display: flex; flex-wrap: wrap; align-items: center; gap: .15rem .4rem; margin-top: .2rem; font-size: .76rem; color: var(--ob-muted); font-variant-numeric: tabular-nums; }
        .b2b-line__price s { color: #94a3b8; }
        .b2b-line__tag { display: inline-flex; align-items: center; gap: .25rem; padding: .05rem .45rem; border-radius: 6px; background: var(--ob-success-soft); color: var(--ob-success); font-size: .68rem; font-weight: 700; white-space: nowrap; }
        .b2b-line__warn { display: flex; align-items: center; gap: .35rem; margin-top: .3rem; font-size: .74rem; font-weight: 600; color: var(--cs-warning); }
        .b2b-line__remove { display: inline-grid; place-items: center; flex-shrink: 0; width: 32px; height: 32px; margin: -.3rem -.4rem 0 0; border: 0; border-radius: 8px; background: none; color: var(--ob-muted); }
        .b2b-line__remove:hover { background: var(--ob-danger-soft); color: var(--ob-danger); }
        .b2b-line__foot { display: flex; align-items: center; gap: .6rem; margin-top: .6rem; }
        .b2b-line__total { margin-left: auto; font-size: .92rem; font-weight: 700; color: var(--ob-text); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .b2b-line__note-toggle { padding: .25rem 0; border: 0; background: none; font-size: .75rem; font-weight: 600; color: var(--ob-primary); white-space: nowrap; }
        .b2b-cashier .b2b-line__note { width: 100%; height: 36px; margin-top: .55rem; padding: 0 .7rem; border: 1px solid var(--ob-border-strong); border-radius: 8px; font-size: .8rem; }
        .b2b-cashier .b2b-line__note:focus { border-color: var(--ob-primary); box-shadow: 0 0 0 3px rgba(21, 114, 232, .15); outline: none; }

        .b2b-qty { display: inline-flex; align-items: stretch; overflow: hidden; border: 1px solid var(--ob-border-strong); border-radius: 10px; background: var(--ob-surface); }
        .b2b-qty button { width: 40px; min-height: 40px; border: 0; background: var(--ob-surface-2); color: var(--ob-text-2); font-size: .8rem; }
        .b2b-qty button:hover { background: var(--ob-primary-soft); color: var(--ob-primary); }
        .b2b-qty input { width: 56px; border: 0; border-inline: 1px solid var(--ob-border); text-align: center; font-size: .9rem; font-weight: 700; color: var(--ob-text); font-variant-numeric: tabular-nums; -moz-appearance: textfield; }
        .b2b-qty input:focus { outline: none; background: var(--ob-primary-soft); }
        .b2b-qty input::-webkit-outer-spin-button, .b2b-qty input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

        /* Detail pesanan */
        .b2b-order__details { display: flex; flex-direction: column; gap: 1rem; padding: 1rem 1.25rem 1.1rem; border-top: 1px solid var(--ob-border); background: var(--ob-surface-2); }
        .b2b-order__details .form-label { margin-bottom: .4rem; }
        .b2b-quick-days { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .5rem; }
        .b2b-quick-days button {
            min-height: 34px; padding: .2rem .75rem; border: 1px solid var(--ob-border-strong); border-radius: 999px;
            background: var(--ob-surface); color: var(--ob-text-2); font-size: .76rem; font-weight: 600;
        }
        .b2b-quick-days button:hover { border-color: var(--ob-primary); color: var(--ob-primary); }
        .b2b-quick-days button[aria-pressed="true"] { border-color: var(--ob-primary); background: var(--ob-primary); color: #fff; }
        .b2b-cashier .b2b-segment span { min-height: 42px; background: var(--ob-surface); font-size: .85rem; }
        .b2b-address { display: flex; gap: .6rem; margin-top: .6rem; padding: .65rem .8rem; border: 1px solid var(--ob-border); border-radius: var(--ob-radius-sm); background: var(--ob-surface); font-size: .8rem; line-height: 1.5; color: var(--ob-text-2); }
        .b2b-address > i { margin-top: .2rem; color: var(--ob-muted); }
        .b2b-address.is-missing { border-color: #fde68a; background: #fffbeb; color: var(--cs-warning); }
        .b2b-address.is-missing > i { color: var(--cs-warning); }

        /* Ringkasan + tombol simpan */
        .b2b-order__summary { flex-shrink: 0; padding: .9rem 1.25rem 1.1rem; border-top: 1px solid var(--ob-border); border-radius: 0 0 var(--ob-radius) var(--ob-radius); background: var(--ob-surface); }
        .b2b-sum-row { display: flex; justify-content: space-between; gap: 1rem; padding: .15rem 0; font-size: .82rem; color: var(--ob-text-2); font-variant-numeric: tabular-nums; }
        .b2b-sum-row.is-down span:last-child { font-weight: 600; color: var(--ob-success); }
        .b2b-sum-row.is-up span:last-child { font-weight: 600; color: var(--cs-warning); }
        .b2b-sum-total { display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; margin-top: .35rem; padding-top: .55rem; border-top: 1px dashed var(--ob-border-strong); }
        .b2b-sum-total span { font-size: .85rem; font-weight: 600; color: var(--ob-text); }
        .b2b-sum-total strong { font-size: 1.4rem; font-weight: 800; letter-spacing: -.01em; color: var(--ob-text); font-variant-numeric: tabular-nums; }
        .b2b-quote-status { display: flex; align-items: center; gap: .4rem; min-height: 1.2rem; margin: .45rem 0 .75rem; font-size: .75rem; color: var(--ob-muted); }
        .b2b-quote-status.is-error { color: var(--ob-danger); }
        .b2b-quote-status.is-contract { color: var(--ob-success); }
        .b2b-submit {
            display: flex; align-items: center; justify-content: space-between; gap: .75rem; width: 100%; min-height: 54px; padding: .5rem 1.1rem;
            border: 0; border-radius: 12px; background: var(--ob-primary); color: #fff; font-size: .95rem; font-weight: 700;
            box-shadow: 0 6px 16px rgba(21, 114, 232, .25); transition: background-color .15s ease, box-shadow .15s ease;
        }
        .b2b-submit:hover:not(:disabled) { background: #0f5fc6; }
        .b2b-submit:focus-visible { outline: 3px solid rgba(21, 114, 232, .35); outline-offset: 2px; }
        .b2b-submit:disabled { background: var(--ob-border-strong); box-shadow: none; cursor: not-allowed; }
        .b2b-submit__total { font-variant-numeric: tabular-nums; opacity: .95; }

        /* Bilah bawah di tablet/HP: total + lompat ke panel pesanan */
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
            .b2b-cashier { padding-bottom: 84px; }
        }
    </style>
@endpush
