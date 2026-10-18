@extends('layouts.app')
@section('content')
    <style>
        /* ===== Open Bill — scoped design tokens ===== */
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

        #openbill-table tbody tr,
        #openbilldeleted-table tbody tr {
            cursor: pointer;
            transition: background-color .15s ease;
        }

        #openbill-table tbody tr:hover td,
        #openbilldeleted-table tbody tr:hover td {
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

        .ob-page .badge-success::before,
        .ob-page .badge-danger::before {
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
    </style>

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Laporan</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-receipt"></i></span>
                    Open Bill
                </h1>
                <p class="ob-header__desc">Pantau bill yang sedang berjalan maupun yang sudah dibayar di setiap outlet.</p>
            </div>
        </div>

        {{-- Filter --}}
        <div class="ob-card">
            <div class="ob-toolbar">
                <div class="ob-field">
                    <label for="filter-outlet"><i class="fas fa-store" aria-hidden="true"></i> Outlet</label>
                    <select id="filter-outlet" class="form-control select2">
                        <option value="all" selected>-- Semua Outlet --</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ob-field">
                    <label for="status-bill"><i class="fas fa-filter" aria-hidden="true"></i> Status Pembayaran</label>
                    <select id="status-bill" class="w-100" data-style="btn-default">
                        <option value="" selected disabled>Pilih Status</option>
                        <option value="1">Sudah Dibayar</option>
                        <option value="0">Belum Dibayar</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Tabel --}}
            <div class="col-12 animated" id="tableContainer">
                <div class="ob-card">
                    <div class="ob-tabs-bar">
                        <ul class="nav ob-tabs" id="line-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" id="open-bill-tab" data-bs-toggle="pill" href="#open-bill"
                                    role="tab" aria-controls="open-bill" aria-selected="true">
                                    <i class="fas fa-list-ul" aria-hidden="true"></i> List Bill
                                    <span class="ob-count" id="count-open-bill" aria-label="Jumlah bill">0</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="open-bill-deleted" data-bs-toggle="pill" href="#openbill-deleted"
                                    role="tab" aria-controls="openbill-deleted" aria-selected="false" tabindex="-1">
                                    <i class="fas fa-trash-alt" aria-hidden="true"></i> Open Bill Deleted
                                    <span class="ob-count" id="count-openbill-deleted" aria-label="Jumlah bill terhapus">0</span>
                                </a>
                            </li>
                        </ul>
                        <span class="ob-hint"><i class="fas fa-mouse-pointer" aria-hidden="true"></i> Klik baris untuk melihat detail</span>
                    </div>

                    @can('delete report/openbill')
                        <div class="ob-bulkbar" id="ob-bulkbar" role="region" aria-live="polite" aria-label="Aksi bill terpilih">
                            <div class="ob-bulkbar__info">
                                <i class="fas fa-check-square" aria-hidden="true"></i>
                                <span><span id="ob-selected-count">0</span> bill dipilih</span>
                            </div>
                            <div class="ob-bulkbar__actions">
                                <button type="button" class="btn ob-btn ob-btn-ghost" id="btn-clear-selection">Batal</button>
                                <button type="button" class="btn ob-btn ob-btn-danger" id="btn-bulk-delete-openbill">
                                    <i class="fas fa-trash-alt" aria-hidden="true"></i> Hapus Terpilih
                                </button>
                            </div>
                        </div>
                    @endcan

                    <div class="tab-content ob-table-wrap" id="line-tabContent">
                        <div class="tab-pane fade show active" id="open-bill" role="tabpanel"
                            aria-labelledby="open-bill-tab">
                            <div class="table-responsive">
                                {!! $dataTable->table() !!}
                            </div>
                        </div>
                        <div class="tab-pane fade" id="openbill-deleted" role="tabpanel"
                            aria-labelledby="open-bill-deleted">
                            <div class="table-responsive">
                                {!! $openBillDeletedDataTable->table(['class' => 'table table-bordered table-striped w-100'], true) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Panel detail --}}
            <div class="col-6" id="container-order-details">
                <div class="ob-card ob-detail">
                    <div class="ob-detail__head">
                        <div>
                            <div class="ob-detail__label">Order Details</div>
                            <div class="ob-detail__name" id="nama-open-bill"></div>
                            <div id="status-open-bill"></div>
                        </div>
                        <button type="button" class="ob-icon-btn" id="btn-close-detail" aria-label="Tutup detail">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="ob-detail__body">
                        <h2 class="ob-section-title"><i class="fas fa-info-circle" aria-hidden="true"></i> Informasi</h2>
                        <dl class="ob-meta">
                            <div class="ob-meta__item">
                                <dt>Tanggal Pembuatan</dt>
                                <dd><span id="created-time"></span></dd>
                            </div>
                            <div class="ob-meta__item">
                                <dt>Collected By</dt>
                                <dd id="collected-by"></dd>
                            </div>
                            <div class="ob-meta__item">
                                <dt>Served by</dt>
                                <dd id="served-by"></dd>
                            </div>
                            <div class="ob-meta__item">
                                <dt>Payment Method</dt>
                                <dd id="payment-method"></dd>
                            </div>
                            <div class="ob-meta__item">
                                <dt>Order Id</dt>
                                <dd></dd>
                            </div>
                            <div class="ob-meta__item">
                                <dt>Table</dt>
                                <dd></dd>
                            </div>
                            <div class="ob-meta__item">
                                <dt>Duration</dt>
                                <dd></dd>
                            </div>
                        </dl>

                        <h2 class="ob-section-title"><i class="fas fa-calculator" aria-hidden="true"></i> Ringkasan</h2>
                        <div class="ob-summary">
                            <div class="ob-summary__row">
                                <div>Amount Item</div>
                                <div id="total-amount"></div>
                            </div>
                            <div class="ob-summary__row">
                                <div>Tax</div>
                                <div id="tax"></div>
                            </div>
                            <div class="ob-summary__row ob-summary__row--total">
                                <div>Total <small class="fw-normal text-muted">(terbayar)</small></div>
                                <div id="total"></div>
                            </div>
                        </div>

                        <h2 class="ob-section-title"><i class="fas fa-utensils" aria-hidden="true"></i> Ordered Items</h2>
                        <div class="ob-items">
                            <table class="table" id="table-list-item">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Ditambahkan Pada</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="ob-detail__foot">
                        <div class="ob-actions">
                            @can('delete report/openbill')
                                <a id="btn-delete-openbill" class="btn ob-btn ob-btn-danger" href="">
                                    <i class="fas fa-trash-alt" aria-hidden="true"></i> Hapus Open Bill
                                </a>
                            @endcan
                            <button class="btn btn-primary ob-btn d-none" id="btn-restore-openbill">
                                <i class="fas fa-undo" aria-hidden="true"></i> Restore Openbill
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}
        {!! $openBillDeletedDataTable->scripts() !!}
        <script>
            var dataTransaksi = @json($data);
            let totalTransaksi = 0;
            dataTransaksi.forEach(function(item) {
                totalTransaksi += item.total;
            });
            var stateLastId = 0;
            const tableContainer = $('#tableContainer');
            const detailCard = $('#container-order-details');

            const idOpenBillDatatable = 'openbill-table';
            const idOpenBillDeletedDatatable = 'openbilldeleted-table';

            const openBillTable = $(`#${idOpenBillDatatable}`);
            const openBillDeletedDatatable = $(`#${idOpenBillDeletedDatatable}`);

            // Badge jumlah data pada tab (hanya tampilan)
            openBillTable.on('xhr.dt', function(e, settings, json) {
                if (json) $('#count-open-bill').text(json.recordsFiltered ?? 0);
            });
            openBillDeletedDatatable.on('xhr.dt', function(e, settings, json) {
                if (json) $('#count-openbill-deleted').text(json.recordsFiltered ?? 0);
            });

            // Tandai baris yang sedang dibuka di panel detail (hanya tampilan)
            function markActiveRow(idOpenBill) {
                $('.ob-page tr.ob-row-active').removeClass('ob-row-active');
                if (idOpenBill) $(`#${idOpenBillDatatable} tr[id="${idOpenBill}"], #${idOpenBillDeletedDatatable} tr[id="${idOpenBill}"]`).addClass('ob-row-active');
            }
            openBillTable.add(openBillDeletedDatatable).on('draw.dt', function() {
                markActiveRow(stateLastId);
            });

            function getNewOpenBillData() {
                let outletTerpilih = $('#filter-outlet').val();

                $.ajax({
                    url: `{{ route('report/openbill/getOpenBillData') }}`, // URL endpoint Laravel
                    type: 'GET',
                    data: {
                        idOutlet: outletTerpilih, // Kirim data array ke server
                    },
                    success: function(response) {

                    },
                    error: function(xhr, status, error) {
                        console.error("Terjadi kesalahan:", error);
                    }
                });
            }

            function fetchDetailOpenBill(idOpenBill, idOutlet) {
                $.ajax({
                    url: `{{ route('report/openbill/getOpenBillDataDetail') }}`,
                    method: 'GET',
                    data: {
                        idOpenBill: idOpenBill,
                        idOutlet: idOutlet,
                    },
                    beforeSend: function() {
                        showLoader();
                    },
                    complete: function() {
                        showLoader(false);
                    },
                    success: function(res) {
                        // console.log(res)
                        let total = 0;
                        let pajak = res.pajak.value;
                        let totalNominalPajak = 0;
                        let totalTerbayar = 0;

                        if (res.data.delete_permanen) {
                            $('#btn-delete-openbill').addClass('d-none')
                            $('#btn-restore-openbill').removeClass('d-none');
                        } else {
                            $('#btn-restore-openbill').addClass('d-none');
                            $('#btn-delete-openbill').removeClass('d-none')
                        }

                        res.data.transactions.forEach(function(transaction){
                            totalTerbayar += parseInt(transaction.total);
                        });

                        res.data.item.forEach(function(item) {
                            // console.log(item);
                            let harga = item.harga;
                            let itemTerbayar = item.qty_terbayar ? item.qty_terbayar : 0;
                            let qty = parseInt(item.quantity) + itemTerbayar;

                            let listModifier = [];
                            try{
                                listModifier = JSON.parse(item.modifier);
                            }catch(e){
                                listModifier = [];
                            }

                            let listHargaModifier = listModifier.map((val) => val.harga);
                            let totalHargaModifier = listHargaModifier.reduce((sum, value) => sum + value, 0);
                            let hargaAkhirModifier = totalHargaModifier * qty;
                            
                            let hargaAkhirItem = harga * qty;

                            let hargaAkhir = hargaAkhirItem + hargaAkhirModifier;

                            let pajakItem = hargaAkhir * pajak / 100;

                            totalNominalPajak += pajakItem;

                            total += hargaAkhir;
                        });

                        let totalHarga = total + totalNominalPajak;
                        let status = res.data.deleted_at ?
                            '<span class="badge badge-success">Sudah dibayar</span>' :
                            '<span class="badge badge-danger">Belum dibayar</span>';
                        $('#nama-open-bill').text(res.data.name);
                        $('#created-time').text(res.data.create_formated);
                        $('#collected-by').text(res.data.user.name);
                        $('#total-amount').text(formatRupiah(total.toString(), "Rp. "));
                        $('#payment-method').text(res.data.nama_tipe_pembayaran);
                        $('#status-open-bill').html(status);
                        $('#tax').text(formatRupiah(totalNominalPajak.toString(), "Rp. "));
                        $('#total').text(`${formatRupiah(totalHarga.toString(), "Rp. ")} (${formatRupiah(totalTerbayar.toString(), "Rp. ")})`);
                        // $('#total').html(`<span>${formatRupiah(totalHarga.toString(), "Rp. ")}</span> (<span class="badge badge-success">${formatRupiah(totalTerbayar.toString(), "Rp. ")}</span>)`);

                        var urlDestroyOpenBill = "{{ route('report/openbill/deleteOpenBill', ':id') }}".replace(
                            ':id', res.data.id);
                        $('#btn-delete-openbill').attr('href', urlDestroyOpenBill);

                        // Mengosongkan tabel sebelum mengisi data baru
                        const tbody = $('#table-list-item tbody');
                        tbody.empty(); // Menghapus semua baris yang ada

                        // Mengisi tabel dengan data item_transaction
                        res.data.item.forEach(item => {
                            // console.log(item);
                            let qtyTerbayar = item.qty_terbayar ? item.qty_terbayar : 0;
                            let qtyPesanan = parseInt(item.quantity) + qtyTerbayar;

                            let modifiers = [];
                            try {
                                modifiers = JSON.parse(item.modifier) ?? [];
                            } catch (e) {
                                modifiers = [];
                            }

                            let modifierHtml = '';
                            if (modifiers.length > 0) {
                                modifierHtml = `
                                    <ul class="mb-0 ps-3 small text-muted">
                                        ${modifiers.map(m => `<li>${m.nama ?? "modifier tidak ditemukan"}</li>`).join('')}
                                    </ul>
                                `;
                            }
                            

                            let qtyText = qtyTerbayar > 0 && item.deleted_at == null ?
                                `${qtyPesanan} <span class="badge rounded-pill badge-success">Paid ${qtyTerbayar}</span>` : (item.deleted_at == null ? qtyPesanan : `${qtyPesanan} <span class="badge rounded-pill badge-success">Paid ${qtyPesanan}</span>`);
                            if (item.product) {
                                let namaProduct = item.variant.name == item.product.name ? item
                                    .product.name :
                                    `${item.product.name} - ${item.variant.name}`

                                tbody.append(`
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold">${namaProduct}</div>
                                                        ${modifierHtml}
                                                    </td>
                                                    <td>${qtyText}</td>
                                                    <td>${item.create_formated}</td>
                                                </tr>
                                            `);
                            } else {
                                tbody.append(`
                                                <tr>
                                                    <td>custom</td>
                                                    <td>${qtyText}</td>
                                                    <td>${item.create_formated}</td>
                                                </tr>
                                            `);
                            }
                        });

                    },
                    error: function(xhr) {
                        console.error(xhr);
                    }
                });
            }

            function removeDetailCard(detailCard, container, openBillTable) {
                markActiveRow(0);
                detailCard.hide(1000, function(a) {
                    const cardDetail = $(this);
                    cardDetail.css('display', 'none').animate({
                        opacity: 0
                    });
                    openBillTable.animate({
                        width: '100%'
                    });

                    container.removeClass('col-6').addClass('col-12');
                    container.animate({
                        width: '100%'
                    }, 500);
                });
            }

            function handleClickRowOpenBill(idOpenBill) {
                let outletTerpilih = $('#filter-outlet').val();
                if (tableContainer.hasClass('col-12')) {
                    tableContainer.removeClass('col-12').addClass('col-6');
                    tableContainer.animate({
                        width: '50%'
                    }, 500, function() {

                        // Setelah animasi selesai, tampilkan detail card
                        detailCard.css('display', 'block').animate({
                            opacity: 1
                        }, 500);
                    });

                    fetchDetailOpenBill(idOpenBill, outletTerpilih);
                } else {
                    if (stateLastId != idOpenBill) {
                        fetchDetailOpenBill(idOpenBill, outletTerpilih)
                    } else {
                        // Jika ingin mengembalikan ke ukuran asal
                        removeDetailCard(detailCard, tableContainer, openBillTable);
                        stateLastId = 0;
                        return
                    }
                }

                stateLastId = idOpenBill;
                markActiveRow(idOpenBill);
            }

            function restoreOpenBill(id) {
                Swal.fire({
                    title: 'Kamu yakin?',
                    text: "ingin mengembalikan Open Bill ini?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, restore it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `{{ route('report/openbill/restoreOpenBill') }}`,
                            method: 'POST',
                            data: {
                                idOpenBill: id,
                            },
                            beforeSend: function() {
                                // showLoader();
                            },
                            complete: function() {
                                // showLoader(false);
                            },
                            success: function(res) {
                                showToast('success', res.message);
                                window.LaravelDataTables[idOpenBillDeletedDatatable].ajax.reload(null,
                                    false)
                            },
                            error: function(xhr) {
                                console.error(xhr);
                            }
                        });

                    }

                })
            }

            function checkActiveTab() {
                let activeTab = $('a.nav-link.active').attr('href');
                let outlet = $('#filter-outlet').val();
                let status = $('#status-bill').val();
                // console.log(status);

                if (activeTab === "#open-bill") { // Use your tab's href id accordingly
                    window.LaravelDataTables[idOpenBillDatatable].ajax.reload(null, false);
                    openBillTable.DataTable().ajax.url("{{ route('report/openbill') }}?outlet=" + outlet +
                        "&datatable=openbill" + "&status=" + status).load();
                    $('#btn-restore-openbill').addClass('d-none');
                } else if (activeTab === "#openbill-deleted") {
                    window.LaravelDataTables[idOpenBillDeletedDatatable].ajax.reload(null, false)
                    openBillDeletedDatatable.DataTable().ajax.url("{{ route('report/openbill') }}?outlet=" + outlet +
                        "&datatable=openbill-deleted"  + "&status=" + status).load();
                    $('#btn-restore-openbill').removeClass('d-none');
                }
            }


            $(document).ready(function() {
                $('#btn-close-detail').off().on('click', function() {
                    removeDetailCard(detailCard, tableContainer, openBillTable);
                });
                $("#total-collected").text(formatRupiah(totalTransaksi.toString(), "Rp. "));
                $("#net-sales").text(formatRupiah(totalTransaksi.toString(), "Rp. "));

                $("#filter-outlet").select2();
                var success = "{{ session('success') }}";

                $('#status-bill').select2({
                    placeholder: "Pilih Status Bill ",
                    allowClear: true
                });


                $(".select2InsideModal").select2({
                    dropdownParent: $("#modal_action")
                });

                handleAction(idOpenBillDatatable);

                $('#filter-outlet').on('change', function() {
                    checkActiveTab();
                    // getNewOpenBillData()
                });

                $('#status-bill').on('change', function(){
                    checkActiveTab();
                })

                $('#btn-delete-openbill').on('click', function(e) {
                    e.preventDefault();

                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            handleAjax(this.href, 'post').onSuccess(function(res) {
                                // showToast(res.status, res.message)
                                window.LaravelDataTables[idOpenBillDatatable].ajax.reload(null,
                                    false)
                            }, false).excute();

                            showToast('success', "Data berhasil dihapus");
                            removeDetailCard(detailCard, tableContainer, openBillTable);
                        }
                    })

                });

                $('#openbill-table tbody tr').on('click', function() {
                    if (tableContainer.hasClass('col-12')) {
                        tableContainer.removeClass('col-12').addClass('col-6');
                    } else {
                        tableContainer.removeClass('col-6').addClass('col-12');
                    }
                });

                // On Bootstrap tab show event, reload table data via AJAX
                $('a[data-bs-toggle="pill"]').off().on('shown.bs.tab', function(e) {
                    checkActiveTab();
                });

                $('#btn-restore-openbill').off().on('click', function(e) {
                    e.preventDefault();
                    let idOpenBill = stateLastId;
                    restoreOpenBill(idOpenBill);
                });

            });

            // ===== Bulk delete open bill =====
            // Pilihan disimpan lintas halaman pagination, dikosongkan saat filter/tab berubah.
            const selectedOpenBills = new Map(); // id => nama bill

            function escapeHtml(text) {
                return $('<div>').text(text ?? '').html();
            }

            function syncBulkSelectionUi() {
                const jumlah = selectedOpenBills.size;
                $('#ob-selected-count').text(jumlah);
                $('#ob-bulkbar').toggleClass('is-visible', jumlah > 0);

                const rowChecks = openBillTable.find('tbody input.ob-row-check');
                rowChecks.each(function() {
                    const checked = selectedOpenBills.has(String(this.value));
                    this.checked = checked;
                    $(this).closest('tr').toggleClass('ob-row-checked', checked);
                });

                const totalDiHalaman = rowChecks.length;
                const terpilihDiHalaman = rowChecks.filter(':checked').length;
                const checkAll = $('#ob-check-all')[0];
                if (checkAll) {
                    checkAll.checked = totalDiHalaman > 0 && terpilihDiHalaman === totalDiHalaman;
                    checkAll.indeterminate = terpilihDiHalaman > 0 && terpilihDiHalaman < totalDiHalaman;
                }
            }

            function clearBulkSelection() {
                selectedOpenBills.clear();
                syncBulkSelectionUi();
            }

            function bulkDeleteOpenBill() {
                const ids = Array.from(selectedOpenBills.keys());
                if (!ids.length) return;

                const names = Array.from(selectedOpenBills.values());
                const maxTampil = 5;
                let listHtml = names.slice(0, maxTampil).map(n => `<li>${escapeHtml(n)}</li>`).join('');
                if (names.length > maxTampil) {
                    listHtml += `<li><em>+${names.length - maxTampil} lainnya</em></li>`;
                }

                Swal.fire({
                    title: `Hapus ${ids.length} Open Bill?`,
                    html: `<ul class="text-start mb-0">${listHtml}</ul>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, hapus semua',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: `{{ route('report/openbill/bulkDeleteOpenBill') }}`,
                        method: 'POST',
                        data: {
                            ids: ids,
                        },
                        beforeSend: function() {
                            showLoader();
                            $('#btn-bulk-delete-openbill').prop('disabled', true);
                        },
                        complete: function() {
                            showLoader(false);
                            $('#btn-bulk-delete-openbill').prop('disabled', false);
                        },
                        success: function(res) {
                            showToast('success', res.message);

                            if (stateLastId && selectedOpenBills.has(String(stateLastId))) {
                                removeDetailCard(detailCard, tableContainer, openBillTable);
                                stateLastId = 0;
                            }

                            clearBulkSelection();
                            window.LaravelDataTables[idOpenBillDatatable].ajax.reload(null, false);
                            window.LaravelDataTables[idOpenBillDeletedDatatable].ajax.reload(null, false);
                        },
                        error: function(xhr) {
                            console.error(xhr);
                            Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Open Bill gagal dihapus', 'error');
                        }
                    });
                });
            }

            $(document).ready(function() {
                openBillTable.on('change', 'tbody input.ob-row-check', function() {
                    if (this.checked) {
                        selectedOpenBills.set(String(this.value), $(this).data('name'));
                    } else {
                        selectedOpenBills.delete(String(this.value));
                    }
                    syncBulkSelectionUi();
                });

                $(document).on('change', '#ob-check-all', function() {
                    const checked = this.checked;
                    openBillTable.find('tbody input.ob-row-check').each(function() {
                        if (checked) {
                            selectedOpenBills.set(String(this.value), $(this).data('name'));
                        } else {
                            selectedOpenBills.delete(String(this.value));
                        }
                    });
                    syncBulkSelectionUi();
                });

                openBillTable.on('draw.dt', syncBulkSelectionUi);

                // Filter / tab berubah -> data berbeda, kosongkan pilihan agar tidak salah hapus
                $('#filter-outlet, #status-bill').on('change', clearBulkSelection);
                $('a[data-bs-toggle="pill"]').on('shown.bs.tab', clearBulkSelection);

                $('#btn-clear-selection').on('click', clearBulkSelection);
                $('#btn-bulk-delete-openbill').on('click', bulkDeleteOpenBill);
            });
        </script>
    @endpush
@endsection
