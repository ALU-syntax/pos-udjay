{{-- Gaya bersama halaman detail & modal warehouse (Supplier, Kategori, Bahan Baku).
     Dipakai bersama layouts.reports.partials.ui-styles: token --ob-* berasal dari .ob-page. --}}
<style>
    /* Kaiadmin memberi .badge margin-left:auto, yang mendorong badge ke kanan di dalam flex */
    .ob-page .badge { margin-left: 0; }

    /* ----- Kartu: kepala, judul, isi ----- */
    .wh-card__head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem 1rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--ob-border); }
    .wh-card__title { display: flex; align-items: center; gap: .55rem; margin: 0; font-size: .95rem; font-weight: 700; color: var(--ob-text); }
    .wh-card__title > i { width: 1.1rem; text-align: center; font-size: .85rem; color: var(--ob-primary); }
    .wh-card__desc { margin: .2rem 0 0; font-size: .8rem; line-height: 1.45; color: var(--ob-muted); }
    .wh-card__body { padding: 1rem 1.25rem; }
    .wh-count { display: inline-grid; place-items: center; min-width: 1.35rem; height: 1.35rem; padding: 0 .4rem; border-radius: 999px; background: var(--ob-border); color: var(--ob-text-2); font-size: .7rem; font-weight: 700; }

    /* ----- Tabel di dalam kartu ----- */
    .wh-table { width: 100%; margin: 0; border-collapse: collapse; font-size: .85rem; }
    .wh-table th { padding: .65rem 1rem; background: var(--ob-surface-2); border-bottom: 1px solid var(--ob-border); font-size: .7rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--ob-muted); white-space: nowrap; }
    .wh-table td { padding: .8rem 1rem; border-bottom: 1px solid var(--ob-border); color: var(--ob-text-2); vertical-align: middle; }
    .wh-table th:first-child, .wh-table td:first-child { padding-left: 1.25rem; }
    .wh-table th:last-child, .wh-table td:last-child { padding-right: 1.25rem; }
    .wh-table tbody tr:last-child td { border-bottom: 0; }
    .wh-table tbody tr:hover td { background: var(--ob-surface-2); }
    .wh-row-inactive { opacity: .6; }

    /* ----- Isi sel ----- */
    .wh-cell-stack { display: flex; flex-direction: column; align-items: flex-start; gap: .2rem; }
    .wh-cell-stack--end { align-items: flex-end; }
    .wh-strong { font-weight: 600; color: var(--ob-text); }
    .wh-num { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .wh-truncate { display: inline-block; max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    /* ----- Keadaan kosong ----- */
    .wh-empty { display: flex; flex-direction: column; align-items: center; gap: .15rem; padding: 2rem 1.25rem; text-align: center; }
    .wh-empty__icon { display: inline-grid; place-items: center; width: 44px; height: 44px; margin-bottom: .5rem; border-radius: 12px; background: var(--ob-surface-2); border: 1px solid var(--ob-border); color: var(--ob-muted); }
    .wh-empty__title { margin: 0; font-size: .9rem; font-weight: 600; color: var(--ob-text); }
    .wh-empty__text { margin: 0; max-width: 46ch; font-size: .8rem; line-height: 1.5; color: var(--ob-muted); }
    .wh-side-empty { margin: 0; padding: 1rem 1.25rem; font-size: .8rem; color: var(--ob-muted); }

    /* ----- Halaman detail: header, ringkasan, grid utama + samping ----- */
    .wh-crumb-sep { margin: 0 .3rem; color: var(--ob-border-strong); }
    .ob-header__eyebrow a { color: inherit; text-decoration: none; }
    .ob-header__eyebrow a:hover { color: var(--ob-primary); }
    .wh-profile-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem .6rem; margin-top: .45rem; font-size: .8rem; color: var(--ob-muted); }
    .wh-profile-meta a { color: inherit; }
    .wh-profile-meta a:hover { color: var(--ob-primary); }
    .wh-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
    .wh-stat-sub { margin: .1rem 0 0; font-size: .76rem; color: var(--ob-muted); }
    .wh-detail-grid { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 1.25rem; align-items: start; margin-top: 1.25rem; }
    .wh-detail-grid .ob-card { margin-bottom: 1.25rem; }
    @media (max-width: 1199.98px) { .wh-detail-grid { grid-template-columns: minmax(0, 1fr); } }

    /* ----- Kolom samping: daftar info & daftar item ----- */
    .wh-dl { margin: 0; }
    .wh-dl__row { display: grid; grid-template-columns: 90px minmax(0, 1fr); gap: .75rem; padding: .6rem 0; border-top: 1px solid var(--ob-border); }
    .wh-dl__row:first-child { border-top: 0; padding-top: 0; }
    .wh-dl__row:last-child { padding-bottom: 0; }
    .wh-dl dt { font-size: .8rem; font-weight: 500; color: var(--ob-muted); }
    .wh-dl dd { margin: 0; font-size: .85rem; font-weight: 500; color: var(--ob-text); overflow-wrap: anywhere; }
    .wh-dl dd:empty::before { content: "—"; font-weight: 400; color: var(--ob-muted); }
    .wh-list { list-style: none; margin: 0; padding: 0; }
    .wh-list__item { display: flex; flex-direction: column; gap: .2rem; padding: .8rem 1.25rem; border-top: 1px solid var(--ob-border); font-size: .82rem; }
    .wh-list__item:first-child { border-top: 0; }
    .wh-list__top { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; }
</style>
