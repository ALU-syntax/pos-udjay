{{-- Styling bersama halaman report (Open Bill, Transactions). Semua aturan di-scope ke .ob-page --}}
<style>
    /* ===== Report UI — design tokens bersama (Open Bill, Transactions) ===== */
    .ob-page {
        --ob-bg: #f4f6fa;
        --ob-surface: #ffffff;
        --ob-surface-2: #f8fafc;
        --ob-border: #e2e8f0;
        --ob-border-strong: #cbd5e1;
        --ob-text: #0f172a;
        --ob-text-2: #475569;
        --ob-muted: #64748b;
        --ob-primary: #1572e8;
        --ob-primary-soft: #e8f1fd;
        --ob-success: #15803d;
        --ob-success-soft: #dcfce7;
        --ob-danger: #b91c1c;
        --ob-danger-soft: #fee2e2;
        --ob-radius: 14px;
        --ob-radius-sm: 10px;
        --ob-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 4px 16px rgba(15, 23, 42, .05);
        color: var(--ob-text);
    }

    .ob-page .ob-card {
        background: var(--ob-surface);
        border: 1px solid var(--ob-border);
        border-radius: var(--ob-radius);
        box-shadow: var(--ob-shadow);
        margin-bottom: 1.25rem;
    }

    /* ----- Header ----- */
    .ob-header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .ob-header__eyebrow {
        font-size: .75rem;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--ob-muted);
        margin-bottom: .25rem;
    }

    .ob-header__title {
        display: flex;
        align-items: center;
        gap: .75rem;
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
        color: var(--ob-text);
    }

    .ob-header__icon {
        display: inline-grid;
        place-items: center;
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: var(--ob-primary-soft);
        color: var(--ob-primary);
        font-size: 1rem;
    }

    .ob-header__desc {
        margin: .35rem 0 0;
        color: var(--ob-text-2);
        font-size: .875rem;
    }

    /* ----- Filter toolbar ----- */
    .ob-toolbar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        padding: 1rem 1.25rem;
    }

    .ob-field label {
        display: flex;
        align-items: center;
        gap: .4rem;
        font-size: .75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: var(--ob-muted);
        margin-bottom: .4rem;
    }

    .ob-page .select2-container {
        width: 100% !important;
    }

    .ob-page .select2-container--default .select2-selection--single {
        height: 42px;
        border: 1px solid var(--ob-border-strong);
        border-radius: var(--ob-radius-sm);
        background: var(--ob-surface);
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .ob-page .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 40px;
        padding-left: .85rem;
        color: var(--ob-text);
        font-size: .875rem;
    }

    .ob-page .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
        right: 8px;
    }

    .ob-page .select2-container--default .select2-selection--single .select2-selection__clear {
        margin-right: 1.5rem;
        color: var(--ob-muted);
    }

    .ob-page .select2-container--default.select2-container--focus .select2-selection--single,
    .ob-page .select2-container--default.select2-container--open .select2-selection--single {
        border-color: var(--ob-primary);
        box-shadow: 0 0 0 3px rgba(21, 114, 232, .15);
    }

    /* ----- Tabs (segmented) ----- */
    .ob-tabs-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--ob-border);
    }

    .ob-tabs {
        display: inline-flex;
        gap: .25rem;
        padding: .25rem;
        background: var(--ob-surface-2);
        border: 1px solid var(--ob-border);
        border-radius: 12px;
        margin: 0;
    }

    .ob-tabs .nav-link {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        min-height: 38px;
        padding: .45rem .9rem;
        border: 0;
        border-radius: 9px;
        font-size: .875rem;
        font-weight: 600;
        color: var(--ob-text-2);
        background: transparent;
        cursor: pointer;
        transition: background-color .15s ease, color .15s ease, box-shadow .15s ease;
    }

    .ob-tabs .nav-link:hover {
        color: var(--ob-text);
    }

    .ob-tabs .nav-link.active {
        background: var(--ob-surface);
        color: var(--ob-primary);
        box-shadow: 0 1px 3px rgba(15, 23, 42, .12);
    }

    .ob-tabs .nav-link:focus-visible,
    .ob-detail button:focus-visible,
    .ob-detail a:focus-visible {
        outline: 2px solid var(--ob-primary);
        outline-offset: 2px;
    }

    .ob-count {
        display: inline-grid;
        place-items: center;
        min-width: 24px;
        height: 20px;
        padding: 0 .4rem;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 700;
        background: var(--ob-border);
        color: var(--ob-text-2);
        font-variant-numeric: tabular-nums;
    }

    .ob-tabs .nav-link.active .ob-count {
        background: var(--ob-primary-soft);
        color: var(--ob-primary);
    }

    .ob-hint {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        font-size: .8rem;
        color: var(--ob-muted);
    }

    /* ----- DataTables ----- */
    .ob-table-wrap {
        padding: 1rem 1.25rem 1.5rem;
    }

    .ob-page .dataTables_wrapper>.row {
        align-items: center;
        margin: 0;
    }

    .ob-page .dataTables_wrapper>.row>[class*="col-"] {
        padding-left: 0;
        padding-right: 0;
    }

    .ob-page .dataTables_wrapper>.row:last-child {
        padding: .5rem 0 .25rem;
    }

    .ob-page .dataTables_wrapper .dataTables_length,
    .ob-page .dataTables_wrapper .dataTables_filter {
        margin-bottom: .85rem;
        font-size: .85rem;
        color: var(--ob-text-2);
    }

    .ob-page .dataTables_wrapper .dataTables_length select,
    .ob-page .dataTables_wrapper .dataTables_filter input {
        height: 38px;
        border: 1px solid var(--ob-border-strong);
        border-radius: var(--ob-radius-sm);
        /* !important: menimpa .form-control-sm Kaiadmin (padding .25rem .5rem !important) */
        padding: 0 .75rem !important;
        font-size: .875rem !important;
        background-color: var(--ob-surface);
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .ob-page .dataTables_wrapper .dataTables_length select {
        padding-right: 2rem !important;
        background-position: right .6rem center;
    }

    .ob-page .dataTables_wrapper .dataTables_filter input {
        min-width: 240px;
        padding-left: 2.25rem !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' viewBox='0 0 24 24'%3E%3Ccircle cx='11' cy='11' r='7'/%3E%3Cpath d='m20 20-3.5-3.5'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: .7rem center;
    }

    .ob-page .dataTables_wrapper .dataTables_length select:focus,
    .ob-page .dataTables_wrapper .dataTables_filter input:focus {
        outline: none;
        border-color: var(--ob-primary);
        box-shadow: 0 0 0 3px rgba(21, 114, 232, .15);
    }

    /* Wrapper tabel dari opsi dom DataTable: hanya tabel yang scroll horizontal,
       show entries / search / pagination tetap di tempat */
    .ob-page .ob-table-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .ob-page table.dataTable {
        margin: 0 !important;
        border-collapse: separate !important;
        border-spacing: 0;
        border: 1px solid var(--ob-border) !important;
        border-radius: var(--ob-radius-sm);
        width: 100% !important;
    }

    .ob-page table.dataTable thead th:first-child {
        border-top-left-radius: var(--ob-radius-sm);
    }

    .ob-page table.dataTable thead th:last-child {
        border-top-right-radius: var(--ob-radius-sm);
    }

    /* Ikon sorting: caret kecil, center vertikal, proporsional dengan teks header */
    .ob-page table.dataTable thead th.sorting,
    .ob-page table.dataTable thead th.sorting_asc,
    .ob-page table.dataTable thead th.sorting_desc {
        padding-right: 1.9rem !important;
    }

    .ob-page table.dataTable thead .sorting:before,
    .ob-page table.dataTable thead .sorting:after,
    .ob-page table.dataTable thead .sorting_asc:before,
    .ob-page table.dataTable thead .sorting_asc:after,
    .ob-page table.dataTable thead .sorting_desc:before,
    .ob-page table.dataTable thead .sorting_desc:after {
        right: .8rem;
        bottom: auto;
        top: 50%;
        font-size: 7px;
        line-height: 1;
        opacity: .35;
        color: var(--ob-muted);
    }

    .ob-page table.dataTable thead .sorting:before,
    .ob-page table.dataTable thead .sorting_asc:before,
    .ob-page table.dataTable thead .sorting_desc:before {
        content: "\25B2";
        transform: translateY(calc(-100% - 1px));
    }

    .ob-page table.dataTable thead .sorting:after,
    .ob-page table.dataTable thead .sorting_asc:after,
    .ob-page table.dataTable thead .sorting_desc:after {
        content: "\25BC";
        transform: translateY(1px);
    }

    .ob-page table.dataTable thead .sorting_asc:before,
    .ob-page table.dataTable thead .sorting_desc:after {
        opacity: 1;
        color: var(--ob-primary);
    }

    .ob-page table.dataTable thead th.sorting_asc,
    .ob-page table.dataTable thead th.sorting_desc {
        color: var(--ob-text) !important;
    }

    .ob-page table.dataTable thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: var(--ob-surface-2) !important;
        color: var(--ob-muted) !important;
        font-size: .72rem !important;
        font-weight: 700 !important;
        letter-spacing: .06em;
        text-transform: uppercase;
        padding: .8rem 1rem !important;
        border: 0 !important;
        border-bottom: 1px solid var(--ob-border) !important;
        white-space: nowrap;
    }

    .ob-page table.dataTable tbody td {
        padding: .85rem 1rem !important;
        font-size: .875rem;
        color: var(--ob-text);
        vertical-align: middle;
        border: 0 !important;
        border-bottom: 1px solid var(--ob-border) !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .ob-page table.dataTable tbody tr:last-child td {
        border-bottom: 0 !important;
    }

    /* Kolom (class dari DataTable PHP) */
    .ob-page table.dataTable tbody td.ob-col-name {
        font-weight: 600;
    }

    .ob-page table.dataTable tbody td.ob-col-time {
        color: var(--ob-text-2);
        font-variant-numeric: tabular-nums;
    }

    .ob-page table.dataTable tbody td.ob-col-items {
        max-width: 280px;
        color: var(--ob-text-2);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ob-page table.dataTable tbody td.ob-col-total {
        font-weight: 700;
        white-space: nowrap;
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .ob-page table.dataTable thead th.ob-col-total {
        text-align: right;
    }

    /* Kolom checkbox bulk delete */
    .ob-page table.dataTable th.ob-col-check,
    .ob-page table.dataTable td.ob-col-check {
        width: 44px !important;
        min-width: 44px !important;
        max-width: 44px !important;
        padding: 0 0 0 .25rem !important;
        text-align: center;
        vertical-align: middle;
    }

    /* Rapatkan jarak checkbox ke kolom berikutnya */
    .ob-page table.dataTable th.ob-col-check+th,
    .ob-page table.dataTable td.ob-col-check+td {
        padding-left: .5rem !important;
    }

    /* <br> dari kolom status server membuat badge tidak center vertikal */
    .ob-page table.dataTable tbody td br {
        display: none;
    }

    .ob-check {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        padding: .85rem 0;
        margin: 0 !important;
        line-height: 1;
        cursor: pointer;
    }

    /* Checkbox custom: 16px, sejajar dengan teks 14px & badge */
    .ob-page input.ob-row-check,
    .ob-page #ob-check-all {
        appearance: none;
        -webkit-appearance: none;
        flex-shrink: 0;
        display: inline-grid;
        place-content: center;
        width: 16px;
        height: 16px;
        margin: 0;
        border: 1.5px solid var(--ob-border-strong);
        border-radius: 4px;
        background: var(--ob-surface);
        cursor: pointer;
        vertical-align: middle;
        transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
    }

    .ob-page input.ob-row-check:hover,
    .ob-page #ob-check-all:hover {
        border-color: var(--ob-primary);
    }

    .ob-page input.ob-row-check:focus-visible,
    .ob-page #ob-check-all:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px rgba(21, 114, 232, .25);
    }

    .ob-page input.ob-row-check:checked,
    .ob-page #ob-check-all:checked,
    .ob-page #ob-check-all:indeterminate {
        background: var(--ob-primary);
        border-color: var(--ob-primary);
    }

    .ob-page input.ob-row-check:checked::after,
    .ob-page #ob-check-all:checked::after {
        content: "";
        width: 8px;
        height: 4.5px;
        border-left: 2px solid #fff;
        border-bottom: 2px solid #fff;
        transform: translateY(-1px) rotate(-45deg);
    }

    .ob-page #ob-check-all:indeterminate::after {
        content: "";
        width: 8px;
        height: 2px;
        border-radius: 1px;
        background: #fff;
    }

    .ob-page table.dataTable tbody tr.ob-row-checked td {
        background: #f1f6fe !important;
    }

    /* Action bar bulk delete */
    .ob-bulkbar {
        display: none;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin: 0 1.25rem;
        margin-top: 1rem;
        padding: .6rem .6rem .6rem 1rem;
        border: 1px solid #fecaca;
        border-radius: var(--ob-radius-sm);
        background: #fff7f7;
    }

    .ob-bulkbar.is-visible {
        display: flex;
    }

    .ob-bulkbar__info {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .875rem;
        font-weight: 600;
        color: var(--ob-text);
    }

    .ob-bulkbar__info i {
        color: var(--ob-danger);
    }

    .ob-bulkbar__actions {
        display: flex;
        gap: .5rem;
    }

    /* Baris yang bisa diklik (punya onclick dari setRowAttr DataTable) */
    .ob-page table.dataTable tbody tr[onclick] {
        cursor: pointer;
        transition: background-color .15s ease;
    }

    .ob-page table.dataTable tbody tr[onclick]:hover td {
        background: var(--ob-surface-2) !important;
    }

    .ob-page table.dataTable tbody tr.ob-row-active td {
        background: var(--ob-primary-soft) !important;
    }

    .ob-page table.dataTable tbody tr.ob-row-active td:first-child {
        box-shadow: inset 3px 0 0 var(--ob-primary) !important;
    }

    .ob-page .dataTables_wrapper .dataTables_info {
        padding-top: 1rem !important;
        font-size: .8rem;
        color: var(--ob-muted);
    }

    .ob-page .dataTables_wrapper .dataTables_paginate {
        padding-top: .85rem !important;
    }

    .ob-page .dataTables_wrapper .pagination {
        gap: .25rem;
        margin: 0;
    }

    .ob-page .dataTables_wrapper .page-link {
        min-width: 36px;
        height: 36px;
        display: grid;
        place-items: center;
        border-radius: 9px !important;
        border: 1px solid var(--ob-border) !important;
        color: var(--ob-text-2);
        font-size: .8rem;
        font-weight: 600;
    }

    .ob-page .dataTables_wrapper .page-item.active .page-link {
        background: var(--ob-primary) !important;
        border-color: var(--ob-primary) !important;
        color: #fff;
    }

    .ob-page .dataTables_wrapper .page-item.disabled .page-link {
        opacity: .5;
    }

    .ob-page .dataTables_wrapper .dataTables_processing {
        border: 1px solid var(--ob-border);
        border-radius: var(--ob-radius-sm);
        box-shadow: var(--ob-shadow);
    }

    /* ----- Soft badges (dipakai oleh server & JS) ----- */
    .ob-page .badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .65rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 600;
        line-height: 1;
        white-space: nowrap;
        border: 0;
    }

    .ob-page .badge-success {
        background: var(--ob-success-soft) !important;
        color: var(--ob-success) !important;
    }

    .ob-page .badge-danger {
        background: var(--ob-danger-soft) !important;
        color: var(--ob-danger) !important;
    }

    .ob-page .badge-primary {
        background: var(--ob-primary-soft) !important;
        color: var(--ob-primary) !important;
    }

    .ob-page .badge-warning {
        background: #fef3c7 !important;
        color: #b45309 !important;
    }

    .ob-page .badge-muted {
        background: var(--ob-border) !important;
        color: var(--ob-text-2) !important;
    }

    .ob-page .badge-success::before,
    .ob-page .badge-danger::before,
    .ob-page .badge-warning::before,
    .ob-page .badge-muted::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    .ob-page .badge.rounded-pill::before {
        display: none;
    }

    /* ----- Layout tabel + panel detail (lebar dikontrol oleh JS) ----- */
    .animated {
        transition: width 0.5s ease, flex 0.5s ease;
    }

    #container-order-details {
        display: none;
        opacity: 0;
        transition: opacity 0.5s ease;
    }

    @media (min-width: 992px) {
        .ob-detail {
            position: sticky;
            top: 90px;
        }
    }

    @media (max-width: 991.98px) {

        /* Di layar kecil, tabel & detail ditumpuk penuh */
        .ob-page #tableContainer,
        .ob-page #container-order-details {
            width: 100% !important;
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }

        .ob-page .dataTables_wrapper .dataTables_filter input {
            min-width: 0;
            width: 100%;
        }
    }

    /* ----- Panel detail ----- */
    .ob-detail {
        overflow: hidden;
    }

    .ob-detail__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.1rem 1.25rem;
        border-bottom: 1px solid var(--ob-border);
        background: linear-gradient(180deg, var(--ob-surface-2), var(--ob-surface));
    }

    .ob-detail__label {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--ob-muted);
    }

    .ob-detail__name {
        margin: .2rem 0 .5rem;
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--ob-text);
        word-break: break-word;
    }

    .ob-detail__name:empty::before {
        content: "—";
        color: var(--ob-muted);
    }

    .ob-icon-btn {
        display: inline-grid;
        place-items: center;
        width: 40px;
        height: 40px;
        flex-shrink: 0;
        border-radius: 10px;
        border: 1px solid var(--ob-border);
        background: var(--ob-surface);
        color: var(--ob-text-2);
        cursor: pointer;
        transition: background-color .15s ease, color .15s ease;
    }

    .ob-icon-btn:hover {
        background: var(--ob-surface-2);
        color: var(--ob-text);
    }

    .ob-detail__body {
        padding: 1.1rem 1.25rem;
    }

    .ob-section-title {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--ob-muted);
        margin: 0 0 .65rem;
    }

    .ob-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .5rem;
        margin: 0 0 1.25rem;
    }

    .ob-meta__item {
        padding: .65rem .8rem;
        border: 1px solid var(--ob-border);
        border-radius: var(--ob-radius-sm);
        background: var(--ob-surface-2);
        min-width: 0;
    }

    .ob-meta__item dt {
        font-size: .7rem;
        font-weight: 600;
        color: var(--ob-muted);
        margin-bottom: .2rem;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .ob-meta__item dd {
        margin: 0;
        font-size: .875rem;
        font-weight: 600;
        color: var(--ob-text);
        overflow-wrap: anywhere;
    }

    .ob-meta__item dd:empty::before,
    .ob-meta__item dd span:empty::before {
        content: "—";
        color: var(--ob-muted);
        font-weight: 400;
    }

    .ob-summary {
        border: 1px solid var(--ob-border);
        border-radius: var(--ob-radius-sm);
        margin-bottom: 1.25rem;
        overflow: hidden;
    }

    .ob-summary__row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: .6rem .9rem;
        font-size: .875rem;
        color: var(--ob-text-2);
    }

    .ob-summary__row+.ob-summary__row {
        border-top: 1px dashed var(--ob-border);
    }

    .ob-summary__row>div:last-child {
        color: var(--ob-text);
        font-weight: 600;
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .ob-summary__row--total {
        background: var(--ob-primary-soft);
        color: var(--ob-text);
        font-weight: 700;
    }

    .ob-summary__row.ob-summary__row--total>div:last-child {
        color: var(--ob-primary);
        font-size: 1rem;
        font-weight: 700;
    }

    .ob-items {
        border: 1px solid var(--ob-border);
        border-radius: var(--ob-radius-sm);
        overflow: auto;
        max-height: 380px;
    }

    #table-list-item {
        margin: 0;
    }

    #table-list-item thead th {
        position: sticky;
        top: 0;
        background: var(--ob-surface-2);
        color: var(--ob-muted);
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        padding: .65rem .85rem;
        border-bottom: 1px solid var(--ob-border);
        border-top: 0;
    }

    #table-list-item tbody td {
        padding: .7rem .85rem;
        font-size: .85rem;
        vertical-align: top;
        border-color: var(--ob-border);
    }

    #table-list-item tbody td:nth-child(2) {
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    #table-list-item tbody td:nth-child(3) {
        color: var(--ob-muted);
        font-size: .8rem;
        white-space: nowrap;
    }

    .ob-detail__foot {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        padding: 1rem 1.25rem;
        border-top: 1px solid var(--ob-border);
        background: var(--ob-surface-2);
    }

    .ob-detail__foot .ob-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .ob-page .ob-btn {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        min-height: 40px;
        padding: .5rem 1rem;
        border-radius: 10px !important;
        font-size: .85rem;
        font-weight: 600;
        transition: transform .12s ease, box-shadow .15s ease, background-color .15s ease;
    }

    .ob-page .ob-btn:hover {
        transform: translateY(-1px);
    }

    .ob-page .ob-btn-ghost {
        background: var(--ob-surface) !important;
        border: 1px solid var(--ob-border-strong) !important;
        color: var(--ob-text-2) !important;
    }

    .ob-page .ob-btn-danger {
        background: var(--ob-danger-soft) !important;
        border: 1px solid transparent !important;
        color: var(--ob-danger) !important;
    }

    .ob-page .ob-btn-danger:hover {
        background: var(--ob-danger) !important;
        color: #fff !important;
    }

    @media (prefers-reduced-motion: reduce) {

        .ob-page *,
        .ob-page *::before,
        .ob-page *::after {
            transition: none !important;
        }

        .ob-page .ob-btn:hover {
            transform: none;
        }
    }

    /* ----- KPI stat cards ----- */
    .ob-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .ob-stat {
        display: flex;
        align-items: center;
        gap: .9rem;
        padding: 1rem 1.15rem;
        margin-bottom: 0;
    }

    .ob-stat__icon {
        display: inline-grid;
        place-items: center;
        width: 44px;
        height: 44px;
        flex-shrink: 0;
        border-radius: 12px;
        font-size: 1.05rem;
    }

    .ob-stat__icon--primary {
        background: var(--ob-primary-soft);
        color: var(--ob-primary);
    }

    .ob-stat__icon--success {
        background: var(--ob-success-soft);
        color: var(--ob-success);
    }

    .ob-stat__icon--warning {
        background: #fef3c7;
        color: #b45309;
    }

    .ob-stat__label {
        font-size: .75rem;
        font-weight: 600;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: var(--ob-muted);
        margin: 0 0 .15rem;
    }

    .ob-stat__value {
        margin: 0;
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--ob-text);
        font-variant-numeric: tabular-nums;
        overflow-wrap: anywhere;
    }

    /* ----- Date range stepper (‹ tanggal ›) ----- */
    .ob-daterange {
        display: flex;
        align-items: stretch;
        height: 42px;
        border: 1px solid var(--ob-border-strong);
        border-radius: var(--ob-radius-sm);
        background: var(--ob-surface);
        overflow: hidden;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .ob-daterange:focus-within {
        border-color: var(--ob-primary);
        box-shadow: 0 0 0 3px rgba(21, 114, 232, .15);
    }

    .ob-daterange__btn {
        display: grid;
        place-items: center;
        width: 42px;
        flex-shrink: 0;
        border: 0;
        background: var(--ob-surface-2);
        color: var(--ob-text-2);
        cursor: pointer;
        transition: background-color .15s ease, color .15s ease;
    }

    .ob-daterange__btn:hover {
        background: var(--ob-primary-soft);
        color: var(--ob-primary);
    }

    .ob-daterange__btn:focus-visible {
        outline: 2px solid var(--ob-primary);
        outline-offset: -2px;
    }

    .ob-daterange__btn:first-child {
        border-right: 1px solid var(--ob-border);
    }

    .ob-daterange__btn:last-child {
        border-left: 1px solid var(--ob-border);
    }

    .ob-page .ob-daterange input.form-control {
        flex: 1;
        min-width: 0;
        height: 100% !important;
        border: 0 !important;
        border-radius: 0;
        padding: 0 .75rem !important;
        font-size: .875rem !important;
        text-align: center;
        font-variant-numeric: tabular-nums;
        background: transparent;
        box-shadow: none !important;
        cursor: pointer;
    }

    .ob-field--action {
        display: flex;
        align-items: flex-end;
        justify-content: flex-end;
    }

    .ob-page .ob-btn-primary {
        min-height: 42px;
        background: var(--ob-primary) !important;
        border: 1px solid var(--ob-primary) !important;
        color: #fff !important;
        box-shadow: 0 4px 12px rgba(21, 114, 232, .25);
    }

    .ob-page .ob-btn-outline {
        background: var(--ob-surface) !important;
        border: 1px solid var(--ob-primary) !important;
        color: var(--ob-primary) !important;
    }

    .ob-page .ob-btn-outline:hover {
        background: var(--ob-primary-soft) !important;
    }

    .ob-page .ob-btn-sm {
        min-height: 32px;
        padding: .3rem .7rem;
        font-size: .78rem;
    }

    .ob-meta__item--wide {
        grid-column: 1 / -1;
    }

    .ob-meta__head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        margin-bottom: .35rem;
    }

    .ob-meta__head dt {
        margin-bottom: 0;
    }

    .ob-meta__head .ob-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .35rem;
    }

    @media (max-width: 575.98px) {
        .ob-field--action {
            justify-content: stretch;
        }

        .ob-field--action .ob-btn {
            width: 100%;
            justify-content: center;
        }
    }

    /* ----- Notice (peringatan lembut) ----- */
    .ob-notice {
        display: flex;
        align-items: center;
        gap: .65rem;
        margin-bottom: 1.25rem;
        padding: .8rem 1rem;
        border: 1px solid #fde68a;
        border-radius: var(--ob-radius-sm);
        background: #fffbeb;
        color: #92400e;
        font-size: .875rem;
    }

    /* ----- Modal form: komponen modal dengan addStyle "ob-page ob-modal" ----- */
    .ob-modal .modal-content {
        border: 1px solid var(--ob-border);
        border-radius: var(--ob-radius);
        box-shadow: 0 24px 64px rgba(15, 23, 42, .18);
        overflow: hidden;
    }

    .ob-modal .modal-header {
        padding: 1.1rem 1.5rem;
        border-bottom: 1px solid var(--ob-border) !important;
        background: linear-gradient(180deg, var(--ob-surface-2), var(--ob-surface));
    }

    .ob-modal .modal-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--ob-text);
    }

    .ob-modal .modal-body {
        padding: 1.25rem 1.5rem .5rem;
    }

    .ob-modal__desc {
        margin: 0 0 1rem;
        color: var(--ob-text-2);
        font-size: .875rem;
    }

    .ob-modal .ob-notice {
        margin-bottom: 1rem;
    }

    .ob-modal .ob-form-heading {
        margin: .25rem 0 1rem;
        padding-top: 1.1rem;
        border-top: 1px solid var(--ob-border);
    }

    .ob-modal .ob-form-heading--first {
        margin-top: 0;
        padding-top: 0;
        border-top: 0;
    }

    .ob-modal .ob-form-heading .ob-section-title {
        margin: 0;
    }

    .ob-modal .form-label {
        font-size: .75rem;
        font-weight: 600;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: var(--ob-muted);
        margin-bottom: .4rem;
    }

    .ob-modal .form-control,
    .ob-modal .form-select {
        min-height: 42px;
        border: 1px solid var(--ob-border-strong);
        border-radius: var(--ob-radius-sm);
        background-color: var(--ob-surface);
        color: var(--ob-text);
        font-size: .875rem !important;
        box-shadow: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .ob-modal .form-control:focus,
    .ob-modal .form-select:focus {
        border-color: var(--ob-primary);
        box-shadow: 0 0 0 3px rgba(21, 114, 232, .15);
    }

    .ob-modal .form-control[readonly] {
        background-color: var(--ob-surface-2);
        color: var(--ob-text-2);
    }

    .ob-modal small.text-muted {
        display: block;
        margin-top: .35rem;
        font-size: .78rem;
        color: var(--ob-muted) !important;
    }

    .ob-modal .form-check-label {
        font-size: .85rem;
        color: var(--ob-text-2);
    }

    .ob-modal .ob-preview {
        display: grid;
        place-items: center;
        aspect-ratio: 16 / 9;
        width: 100%;
        border: 1px dashed var(--ob-border-strong);
        border-radius: var(--ob-radius-sm);
        background: var(--ob-surface-2);
        color: var(--ob-muted);
        font-size: .85rem;
        overflow: hidden;
    }

    .ob-modal .ob-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .ob-modal .modal-footer {
        gap: .5rem;
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--ob-border) !important;
        background: var(--ob-surface-2);
    }

    .ob-modal .modal-footer .btn {
        min-height: 40px;
        margin: 0;
        padding: .5rem 1.1rem;
        border-radius: 10px !important;
        font-size: .85rem;
        font-weight: 600;
    }

    /* Tombol simpan di kanan, tutup sebagai tombol sekunder di kirinya */
    .ob-modal .modal-footer .btn-primary {
        order: 2;
        background: var(--ob-primary) !important;
        border: 1px solid var(--ob-primary) !important;
        color: #fff !important;
        box-shadow: 0 4px 12px rgba(21, 114, 232, .25);
    }

    .ob-modal .modal-footer .btn-danger {
        order: 1;
        background: var(--ob-surface) !important;
        border: 1px solid var(--ob-border-strong) !important;
        color: var(--ob-text-2) !important;
    }

    .ob-modal .modal-footer .btn-danger:hover {
        background: var(--ob-surface-2) !important;
        color: var(--ob-text) !important;
    }

    .ob-modal .input-group-text {
        min-width: 44px;
        justify-content: center;
        border: 1px solid var(--ob-border-strong);
        border-radius: var(--ob-radius-sm) 0 0 var(--ob-radius-sm);
        background: var(--ob-surface-2);
        color: var(--ob-text-2);
        font-size: .85rem;
        font-weight: 600;
    }

    .ob-modal .input-group > .form-control {
        border-radius: 0 var(--ob-radius-sm) var(--ob-radius-sm) 0;
    }

    /* ----- Halaman daftar Order Table (banner, voucher, ...) ----- */
    /* Opt-in: badge-primary sengaja tanpa titik karena dipakai Open Bill untuk nama outlet */
    .ob-page .badge.ob-badge-dot::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    .ob-list-filter {
        width: 260px;
        max-width: 100%;
    }

    .ob-row-actions {
        display: inline-flex;
        gap: .35rem;
    }

    .ob-page .ob-icon-btn--sm {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        font-size: .8rem;
        text-decoration: none;
    }

    .ob-page .ob-icon-btn--sm:hover {
        background: var(--ob-primary-soft);
        color: var(--ob-primary);
    }

    .ob-page .ob-icon-btn--danger:hover {
        background: var(--ob-danger-soft);
        color: var(--ob-danger);
    }

    .ob-page .ob-icon-btn--success:hover {
        background: var(--ob-success-soft);
        color: var(--ob-success);
    }

    .ob-page .ob-icon-btn:focus-visible {
        outline: 2px solid var(--ob-primary);
        outline-offset: 2px;
    }

    @media (max-width: 575.98px) {
        .ob-header > .ob-btn,
        .ob-list-filter {
            width: 100%;
            justify-content: center;
        }
    }
</style>
