{{-- Gaya khusus menu Kontrak B2B. Include setelah layouts.b2b.partials.page-styles. --}}
@push('css')
    <style>
        /* ----- Periode + bilah progres ----- */
        .b2b-period { display: flex; flex-direction: column; gap: .35rem; min-width: 190px; }
        .b2b-period__dates { display: flex; align-items: center; gap: .45rem; font-size: .84rem; font-weight: 600; color: var(--ob-text); white-space: nowrap; }
        .b2b-period__dates i { font-size: .62rem; color: var(--ob-muted); }
        .b2b-period__bar { position: relative; height: 6px; overflow: hidden; border-radius: 999px; background: var(--ob-border); }
        .b2b-period__bar > span { display: block; height: 100%; border-radius: inherit; background: var(--ob-success); }
        .b2b-period__bar--primary > span { background: var(--ob-primary); }
        .b2b-period__bar--muted > span { background: var(--ob-border-strong); }
        .b2b-period__bar--danger > span {
            background: repeating-linear-gradient(-45deg, var(--ob-danger-soft) 0 6px, rgba(220, 38, 38, .25) 6px 12px);
        }
        .b2b-period__hint { font-size: .75rem; color: var(--ob-muted); }
        .b2b-period__hint.is-warning { font-weight: 600; color: #b45309; }
        .b2b-period--lg .b2b-period__dates { font-size: 1rem; }
        .b2b-period--lg .b2b-period__bar { height: 8px; }
        .b2b-period--lg .b2b-period__hint { font-size: .8rem; }

        /* ----- Tawaran salin harga dari kontrak terakhir (form kontrak baru) ----- */
        .b2b-offer { display: flex; align-items: flex-start; gap: .65rem; margin-top: .6rem; padding: .7rem .85rem; border: 1px solid var(--ob-border); border-radius: var(--ob-radius-sm); background: var(--ob-primary-soft); font-size: .84rem; color: var(--ob-text); }
        .b2b-offer__icon { margin-top: .15rem; color: var(--ob-primary); }
        .b2b-offer__body { display: flex; flex-direction: column; gap: .3rem; min-width: 0; }
        .b2b-offer__meta { font-size: .78rem; color: var(--ob-muted); }
        .b2b-offer__actions { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem .85rem; margin-top: .2rem; }

        /* ----- Tabel harga (halaman detail) ----- */
        .b2b-price-table td { vertical-align: top; }
        .b2b-price-table .b2b-price-table__product { font-weight: 600; color: var(--ob-text); }
        .b2b-price-table tbody tr.is-group-start td { border-top: 1px solid var(--ob-border); }
        .b2b-price-table tbody tr:first-child td { border-top: 0; }
        .b2b-price-table tbody tr:not(.is-group-start) td { border-top: 0; }
        .b2b-tiers { display: flex; flex-direction: column; gap: .3rem; }
        .b2b-tier { display: flex; align-items: center; justify-content: flex-end; gap: .6rem; white-space: nowrap; }
        .b2b-tier__qty { font-size: .74rem; font-weight: 600; color: var(--ob-muted); }
        .b2b-tier__price { min-width: 92px; font-weight: 700; color: var(--ob-text); text-align: right; font-variant-numeric: tabular-nums; }
        .b2b-delta { display: inline-block; min-width: 64px; font-size: .72rem; font-weight: 600; text-align: right; font-variant-numeric: tabular-nums; }
        .b2b-delta.is-down { color: var(--ob-success); }
        .b2b-delta.is-up { color: #b45309; }
        .b2b-delta.is-same { color: var(--ob-muted); }

        /* ----- Tabel pilih produk (form) ----- */
        .b2b-outlet-tabs { display: flex; gap: .4rem; overflow-x: auto; padding: .1rem .1rem .6rem; margin-bottom: .9rem; border-bottom: 1px solid var(--ob-border); scrollbar-width: thin; }
        .b2b-outlet-tab {
            display: inline-flex; align-items: center; gap: .45rem; flex-shrink: 0; min-height: 36px; padding: .35rem .85rem;
            border: 1px solid var(--ob-border-strong); border-radius: 999px; background: var(--ob-surface);
            color: var(--ob-text-2); font-size: .8rem; font-weight: 600; white-space: nowrap; cursor: pointer;
            transition: border-color .15s ease, background-color .15s ease, color .15s ease;
        }
        .b2b-outlet-tab:hover { border-color: var(--ob-primary); color: var(--ob-primary); }
        .b2b-outlet-tab.is-active { border-color: var(--ob-primary); background: var(--ob-primary); color: #fff; }
        .b2b-outlet-tab__count { display: inline-grid; place-items: center; min-width: 1.3rem; height: 1.3rem; padding: 0 .35rem; border-radius: 999px; background: var(--ob-primary-soft); color: var(--ob-primary); font-size: .68rem; font-weight: 700; }
        .b2b-outlet-tab.is-active .b2b-outlet-tab__count { background: rgba(255, 255, 255, .25); color: #fff; }
        .b2b-outlet-tab__count:empty { display: none; }

        .b2b-pick-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; margin-bottom: .75rem; }
        .b2b-pick-search { position: relative; flex: 1 1 240px; }
        .b2b-pick-search i { position: absolute; top: 50%; left: .85rem; transform: translateY(-50%); font-size: .8rem; color: var(--ob-muted); }
        .b2b-pick-search .form-control { padding-left: 2.2rem; }
        .b2b-pick-toolbar .form-select { flex: 0 1 200px; min-height: 42px; border-color: var(--ob-border-strong); border-radius: var(--ob-radius-sm); font-size: .85rem; }
        .b2b-pick-toolbar .b2b-chip span { min-height: 42px; border-radius: var(--ob-radius-sm); }

        .b2b-pick-wrap { max-height: 640px; overflow: auto; border: 1px solid var(--ob-border); border-radius: 12px; background: var(--ob-surface); }
        .b2b-pick-table { width: 100%; margin: 0; font-size: .85rem; border-collapse: separate; border-spacing: 0; }
        .b2b-pick-table thead th {
            position: sticky; top: 0; z-index: 2; padding: .65rem .75rem; border-bottom: 1px solid var(--ob-border); background: var(--ob-surface-2);
            font-size: .7rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--ob-muted); white-space: nowrap;
        }
        .b2b-pick-table td { padding: .55rem .75rem; border-bottom: 1px solid var(--ob-border); vertical-align: middle; }
        .b2b-pick-table tbody tr:last-child td { border-bottom: 0; }
        .b2b-pick-table .col-check { width: 44px; text-align: center; }
        .b2b-pick-table .col-normal { width: 120px; }
        .b2b-pick-table .col-qty { width: 104px; }
        .b2b-pick-table .col-price { width: 170px; }
        .b2b-pick-table .item-price { text-align: right; font-weight: 600; font-variant-numeric: tabular-nums; }
        .b2b-pick-table .col-delta { width: 96px; }
        .b2b-pick-table .col-action { width: 52px; text-align: right; }
        .b2b-pick-row { cursor: pointer; transition: background-color .12s ease; }
        .b2b-pick-row:hover { background: var(--ob-surface-2); }
        .b2b-pick-row.is-selected, .b2b-pick-tier { background: #f5f9ff; }
        .b2b-pick-row.is-selected td:first-child { box-shadow: inset 3px 0 0 var(--ob-primary); }
        .b2b-pick-tier td:first-child { box-shadow: inset 3px 0 0 var(--ob-primary); }
        .b2b-pick-row .form-check-input { width: 1.1rem; height: 1.1rem; margin: 0; cursor: pointer; }
        .b2b-pick-name { font-weight: 600; color: var(--ob-text); }
        .b2b-pick-meta { display: flex; flex-wrap: wrap; gap: .2rem .5rem; margin-top: .1rem; font-size: .74rem; color: var(--ob-muted); }
        .b2b-pick-table .form-control { min-height: 36px; padding: .35rem .6rem; font-size: .84rem; font-variant-numeric: tabular-nums; }
        .b2b-pick-table .input-group-text { padding: 0 .55rem; border-color: var(--ob-border-strong); background: var(--ob-surface-2); color: var(--ob-muted); font-size: .76rem; }
        .b2b-pick-table .form-control:disabled { background: transparent; border-color: transparent; color: var(--ob-muted); }
        .b2b-pick-row:not(.is-selected) .input-group-text { border-color: transparent; background: transparent; }
        .b2b-pick-row:not(.is-selected) .item-price { border-color: var(--ob-border); background: var(--ob-surface); }
        .b2b-pick-table .form-control.is-invalid { border-color: var(--ob-danger); }
        .b2b-pick-tier__label { display: flex; align-items: center; gap: .45rem; padding-left: .25rem; font-size: .76rem; font-weight: 600; color: var(--ob-primary); }
        .b2b-pick-tier__label::before { content: ""; width: .7rem; height: .9rem; margin-top: -.5rem; border-left: 2px solid var(--ob-border-strong); border-bottom: 2px solid var(--ob-border-strong); border-bottom-left-radius: 6px; }
        .b2b-pick-error { margin-top: .2rem; font-size: .72rem; color: var(--ob-danger); }
        .b2b-pick-state td { padding: 2.25rem 1rem; text-align: center; color: var(--ob-muted); cursor: default; }
        .b2b-pick-state:hover { background: transparent; }
        .b2b-pick-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; margin-top: .65rem; font-size: .78rem; color: var(--ob-muted); }
        .b2b-pick-foot strong { color: var(--ob-text); }

        .b2b-outlet-breakdown { list-style: none; margin: 1rem 0 0; padding: 0; display: flex; flex-direction: column; gap: .35rem; }
        .b2b-outlet-breakdown button {
            display: flex; width: 100%; align-items: center; justify-content: space-between; gap: .5rem; padding: .45rem .6rem;
            border: 0; border-radius: 8px; background: transparent; color: var(--ob-text-2); font-size: .8rem; text-align: left; cursor: pointer;
        }
        .b2b-outlet-breakdown button:hover { background: var(--ob-surface-2); color: var(--ob-primary); }
        .b2b-outlet-breakdown strong { font-variant-numeric: tabular-nums; color: var(--ob-text); }

        /* ----- Ringkasan samping (form) ----- */
        /* Form: tiga kartu di atas, tabel harga selebar halaman di bawah. */
        .b2b-contract-top { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr) minmax(0, 1fr); gap: 1.25rem; margin-bottom: 1.25rem; align-items: stretch; }
        .b2b-contract-top > .ob-card { margin-bottom: 0; }
        @media (max-width: 1199.98px) {
            .b2b-contract-top { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
            .b2b-contract-top > :last-child { grid-column: 1 / -1; }
        }
        @media (max-width: 767.98px) { .b2b-contract-top { grid-template-columns: minmax(0, 1fr); } }
        .b2b-summary__list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; margin: 0; }
        .b2b-summary__item { padding: .75rem .85rem; border: 1px solid var(--ob-border); border-radius: 12px; background: var(--ob-surface-2); }
        .b2b-summary__item dt { font-size: .72rem; font-weight: 600; color: var(--ob-muted); }
        .b2b-summary__item dd { margin: .15rem 0 0; font-size: 1.15rem; font-weight: 700; color: var(--ob-text); font-variant-numeric: tabular-nums; }
        .b2b-summary__item--wide { grid-column: 1 / -1; }
        .b2b-summary__item--wide dd { font-size: .9rem; }
        .b2b-checklist { list-style: none; margin: 1rem 0 0; padding: 0; display: flex; flex-direction: column; gap: .5rem; font-size: .8rem; color: var(--ob-text-2); }
        .b2b-checklist li { display: flex; gap: .5rem; align-items: flex-start; }
        .b2b-checklist i { margin-top: .2rem; font-size: .75rem; color: var(--ob-muted); }
        .b2b-checklist li.is-done i { color: var(--ob-success); }

        /* Unggah file kontrak */
        .b2b-file { display: flex; align-items: center; gap: .75rem; padding: .75rem .85rem; border: 1px dashed var(--ob-border-strong); border-radius: 12px; background: var(--ob-surface-2); }
        .b2b-file__icon { display: inline-grid; place-items: center; flex-shrink: 0; width: 36px; height: 36px; border-radius: 10px; background: var(--ob-surface); color: var(--ob-primary); }
        .b2b-file__text { flex: 1; min-width: 0; font-size: .8rem; color: var(--ob-muted); }
        .b2b-file__text strong { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .85rem; color: var(--ob-text); }
        .b2b-file input[type=file] { position: absolute; width: 1px; height: 1px; opacity: 0; }
        .b2b-file { position: relative; cursor: pointer; transition: border-color .15s, background-color .15s; }
        .b2b-file:hover { border-color: var(--ob-primary); }
        .b2b-file #attachment-hint { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .b2b-file.is-new, .b2b-file.is-saved { border-style: solid; border-color: var(--ob-border); background: var(--ob-surface); }
        .b2b-file.is-new .b2b-file__icon, .b2b-file.is-saved .b2b-file__icon { background: var(--ob-primary-soft); }
        .b2b-file.is-new #attachment-hint { color: var(--ob-primary); }
        .b2b-file.is-removed { border-color: var(--ob-danger); background: var(--ob-danger-soft); }
        .b2b-file.is-removed .b2b-file__icon { color: var(--ob-danger); }
        .b2b-file.is-removed #attachment-name { text-decoration: line-through; color: var(--ob-text-2); }
        .b2b-file.is-removed #attachment-hint { color: var(--ob-danger); font-weight: 600; }
        .b2b-file #attachment-clear { flex-shrink: 0; }

        /* ----- Kepala halaman detail ----- */
        .b2b-hero-period { padding: 1.1rem 1.25rem; }
        .b2b-terminated { display: flex; gap: .75rem; padding: 1rem 1.25rem; border: 1px solid #fecaca; border-radius: var(--ob-radius); background: var(--ob-danger-soft); color: #991b1b; font-size: .85rem; line-height: 1.5; }
        .b2b-terminated > i { margin-top: .2rem; }
        .b2b-terminated strong { display: block; color: #7f1d1d; }
        .ob-page .ob-stat__value small { font-size: .75rem; font-weight: 600; color: var(--ob-muted); }

        /* Aksi baris tabel */
        .ob-row-actions { justify-content: flex-end; }
    </style>
@endpush
