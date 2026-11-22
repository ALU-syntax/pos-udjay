{{-- Gaya bersama halaman Laporan B2B dan kartu piutang customer: filter, kartu KPI, kartu bagian, tabel ringkas, tooltip chart. --}}
@push('css')
    <style>
        /* Filter menempel di bawah navbar Kaiadmin saat digulir. */
        .b2b-report-filter { position: sticky; top: calc(var(--b2b-navbar-height, 70px) + .5rem); z-index: 20; margin-bottom: 1.25rem; overflow: visible; }
        .b2b-report-filter .b2b-filter-row { border-bottom: 0; border-radius: var(--ob-radius); }
        .b2b-report-filter__row { grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr) minmax(0, 1.2fr) auto; align-items: end; }
        .b2b-report-filter__actions { display: flex; align-items: center; min-height: 42px; }
        @media (max-width: 1199.98px) {
            .b2b-report-filter { position: static; }
            .b2b-report-filter__row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 575.98px) { .b2b-report-filter__row { grid-template-columns: minmax(0, 1fr); } }
        .b2b-report .b2b-range .form-control[readonly] { padding-right: .85rem; }

        .b2b-report .ob-stat__value { font-size: 1.15rem; font-variant-numeric: tabular-nums; }
        .b2b-report .ob-stat__label { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; }
        .b2b-report-asof { padding: .05rem .4rem; border-radius: 999px; background: var(--ob-border); font-size: .62rem; font-weight: 600; letter-spacing: .02em; color: var(--ob-text-2); text-transform: none; }
        .b2b-stat__icon--muted { background: var(--ob-border); color: var(--ob-text-2); }
        /* .ob-page di depan karena ui-styles (.ob-stats auto-fit) dicetak setelah stack css. */
        .ob-page .b2b-report-kpis { position: relative; margin-bottom: 1.25rem; grid-template-columns: repeat(3, minmax(0, 1fr)); }
        @media (max-width: 767.98px) { .ob-page .b2b-report-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        .b2b-report-kpis[aria-busy="true"] .ob-stat__value,
        .b2b-report-kpis[aria-busy="true"] .b2b-stat__sub { opacity: .45; }

        .b2b-skeleton { display: inline-block; width: 8rem; height: 1.1rem; border-radius: 6px; vertical-align: middle;
            background: linear-gradient(90deg, var(--ob-border) 25%, var(--ob-surface-2) 50%, var(--ob-border) 75%); background-size: 200% 100%;
            animation: b2b-skeleton 1.2s ease-in-out infinite; }
        @keyframes b2b-skeleton { from { background-position: 200% 0; } to { background-position: -200% 0; } }
        @media (prefers-reduced-motion: reduce) { .b2b-skeleton { animation: none; } }

        .b2b-report-error { grid-column: 1 / -1; display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; padding: .75rem 1rem;
            border: 1px solid var(--ob-danger-soft); border-radius: var(--ob-radius-sm); background: var(--ob-danger-soft); color: var(--ob-danger); font-size: .85rem; }
        .b2b-report-error[hidden] { display: none; }

        /* Kartu KPI yang bisa diklik */
        .ob-page .b2b-report-kpi-link { position: relative; width: 100%; border: 1px solid var(--ob-border); text-align: left; cursor: pointer; transition: border-color .15s ease, box-shadow .15s ease; }
        .ob-page .b2b-report-kpi-link:hover { border-color: var(--ob-primary); }
        .ob-page .b2b-report-kpi-link:focus-visible { outline: 2px solid var(--ob-primary); outline-offset: 2px; }
        .b2b-report-kpi-link__arrow { position: absolute; top: 1rem; right: 1rem; font-size: .7rem; color: var(--ob-muted); }
        .b2b-report-kpi-link:hover .b2b-report-kpi-link__arrow { color: var(--ob-primary); }

        /* Kartu bagian laporan (chart) */
        .b2b-report-card { margin-bottom: 1.25rem; }
        .b2b-report-card[aria-busy="true"] .b2b-report-chart { opacity: .45; }
        .b2b-report-card .b2b-report-error { margin-top: .75rem; }
        .b2b-report-chart { min-height: 220px; transition: opacity .15s ease; }
        .b2b-report-empty { display: grid; place-items: center; min-height: 240px; padding: 1rem; border: 1px dashed var(--ob-border-strong); border-radius: var(--ob-radius-sm); color: var(--ob-muted); font-size: .875rem; text-align: center; }
        .b2b-report-empty[hidden] { display: none; }
        .b2b-report-empty--short { min-height: 120px; }
        /* Breadcrumb drilldown */
        .b2b-report-crumbs { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; margin-top: .25rem; font-size: .8rem; color: var(--ob-muted); }
        .b2b-report-crumbs[hidden] { display: none; }
        .b2b-report-crumbs i { font-size: .6rem; }
        .b2b-report-crumbs button { padding: 0; border: 0; background: none; color: var(--ob-primary); font-weight: 600; }
        .b2b-report-crumbs button:hover { text-decoration: underline; }
        .b2b-report-crumbs strong { color: var(--ob-text); }
        /* Pilihan pengelompokan (Hari / Minggu / Bulan) */
        .b2b-report-toggle { display: inline-flex; padding: 3px; border: 1px solid var(--ob-border-strong); border-radius: 10px; background: var(--ob-surface-2); }
        .b2b-report-toggle[hidden] { display: none; }
        .b2b-report-toggle button { min-height: 32px; padding: 0 .85rem; border: 0; border-radius: 7px; background: transparent; color: var(--ob-text-2); font-size: .78rem; font-weight: 600; }
        .b2b-report-toggle button[aria-pressed="true"],
        .b2b-report-toggle button[aria-selected="true"] { background: var(--ob-surface); color: var(--ob-primary); box-shadow: 0 1px 3px rgba(15, 23, 42, .12); }
        .b2b-report-toggle button:disabled { opacity: .4; cursor: not-allowed; }
        /* Tabel ringkas di dalam kartu bagian (rekap kas, kontrak) */
        .b2b-report-table-wrap { overflow-x: auto; }
        .b2b-report-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
        .b2b-report-table th { padding: .45rem .6rem; border-bottom: 1px solid var(--ob-border); font-size: .68rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--ob-muted); white-space: nowrap; }
        .b2b-report-table td { padding: .5rem .6rem; border-bottom: 1px solid var(--ob-border); vertical-align: middle; }
        .b2b-report-table .text-end { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .b2b-report-table tfoot td { border-bottom: 0; font-weight: 700; color: var(--ob-text); }
        .b2b-report-table .ob-cell-meta { display: block; font-size: .74rem; color: var(--ob-muted); white-space: nowrap; }
        /* Tooltip chart */
        /* Latar sendiri: tooltip pie/donut ApexCharts bisa transparan / berwarna irisan. */
        .b2b-report-tip { padding: .55rem .7rem; border: 1px solid var(--ob-border); border-radius: 8px; background: var(--ob-surface, #fff); box-shadow: 0 6px 18px rgba(15, 23, 42, .14); color: var(--ob-text-2); font-size: .78rem; line-height: 1.5; }
        .b2b-report .apexcharts-tooltip:has(.b2b-report-tip) { border: 0 !important; background: transparent !important; box-shadow: none !important; }
        .b2b-report-tip strong { display: block; color: var(--ob-text); }
        .b2b-report-tip span { color: var(--ob-muted); }
    </style>
@endpush
