@extends('layouts.app')
@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

    @include('layouts.reports.partials.ui-styles')

    <style>
        /* ===== Sales — layout: navigasi laporan di kiri, konten di kanan ===== */
        .ob-sales {
            display: grid;
            grid-template-columns: 240px minmax(0, 1fr);
            align-items: start;
        }

        .ob-sidenav {
            gap: .15rem;
            padding: 1rem .75rem;
            border-right: 1px solid var(--ob-border);
            min-height: 100%;
        }

        .ob-sidenav__label {
            padding: 0 .65rem .5rem;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--ob-muted);
        }

        .ob-sidenav .nav-link {
            display: flex;
            align-items: center;
            gap: .65rem;
            min-height: 40px;
            padding: .5rem .65rem;
            border: 0;
            border-radius: 9px;
            font-size: .875rem;
            font-weight: 500;
            color: var(--ob-text-2);
            background: transparent;
            cursor: pointer;
            transition: background-color .15s ease, color .15s ease;
        }

        .ob-sidenav .nav-link i {
            width: 18px;
            text-align: center;
            color: var(--ob-muted);
            transition: color .15s ease;
        }

        .ob-sidenav .nav-link:hover {
            background: var(--ob-surface-2);
            color: var(--ob-text);
        }

        .ob-sidenav .nav-link.active {
            background: var(--ob-primary-soft);
            color: var(--ob-primary);
            font-weight: 600;
        }

        .ob-sidenav .nav-link.active i {
            color: var(--ob-primary);
        }

        .ob-sidenav .nav-link:focus-visible {
            outline: 2px solid var(--ob-primary);
            outline-offset: 2px;
        }

        .ob-sales__content {
            min-width: 0;
            padding: 1.25rem;
        }

        @media (max-width: 991.98px) {
            .ob-sales {
                grid-template-columns: minmax(0, 1fr);
            }

            /* Navigasi jadi baris horizontal yang bisa di-scroll */
            .ob-sidenav {
                flex-direction: row !important;
                flex-wrap: nowrap;
                overflow-x: auto;
                border-right: 0;
                border-bottom: 1px solid var(--ob-border);
                padding: .75rem;
                scrollbar-width: thin;
            }

            .ob-sidenav__label {
                display: none;
            }

            .ob-sidenav .nav-link {
                white-space: nowrap;
                flex-shrink: 0;
            }

            .ob-sales__content {
                padding: 1rem;
            }
        }

        /* ----- Header tiap tab ----- */
        .ob-pane-head {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: 1rem;
        }

        .ob-pane-head__title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--ob-text);
        }

        .ob-pane-head__desc {
            margin: .2rem 0 0;
            font-size: .82rem;
            color: var(--ob-muted);
        }

        /* ----- Callout info ----- */
        .ob-callout {
            display: flex;
            gap: .75rem;
            padding: .85rem 1rem;
            margin-bottom: 1rem;
            border: 1px solid #bfdbfe;
            border-radius: var(--ob-radius-sm);
            background: #eff6ff;
            color: #1e3a8a;
            font-size: .85rem;
            line-height: 1.5;
        }

        .ob-callout i {
            margin-top: .15rem;
            color: var(--ob-primary);
        }

        .ob-callout p {
            margin: 0 0 .15rem;
            font-weight: 700;
            font-size: .85rem;
        }

        /* ----- Laporan bentuk statement (Sales Summary & Gross Profit) ----- */
        .ob-statement {
            border: 1px solid var(--ob-border);
            border-radius: var(--ob-radius-sm);
            overflow: hidden;
            max-width: 720px;
        }

        .ob-statement__head {
            display: flex;
            justify-content: space-between;
            padding: .7rem 1rem;
            background: var(--ob-surface-2);
            border-bottom: 1px solid var(--ob-border);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--ob-muted);
        }

        .ob-statement__row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .8rem 1rem;
            font-size: .9rem;
            color: var(--ob-text-2);
        }

        .ob-statement__row+.ob-statement__row {
            border-top: 1px solid var(--ob-border);
        }

        .ob-statement__row>div:last-child {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .5rem;
            text-align: right;
            color: var(--ob-text);
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .ob-statement__row--indent>div:first-child {
            padding-left: 1rem;
        }

        .ob-statement__row--subtotal {
            background: var(--ob-surface-2);
            color: var(--ob-text);
            font-weight: 700;
            border-top: 1px solid var(--ob-border-strong) !important;
        }

        .ob-statement__row--grand {
            background: var(--ob-primary-soft);
            color: var(--ob-text);
            font-weight: 700;
            border-top: 2px solid var(--ob-primary) !important;
        }

        .ob-statement__row--grand>div:last-child {
            color: var(--ob-primary);
            font-size: 1.05rem;
        }

        .ob-statement strong {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-weight: inherit;
        }

        /* ----- Tabel biasa (Payment Method) ----- */
        .ob-plain-table {
            border: 1px solid var(--ob-border);
            border-radius: var(--ob-radius-sm);
            overflow: auto;
        }

        .ob-plain-table table {
            margin: 0;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .ob-plain-table thead th {
            padding: .8rem 1rem !important;
            background: var(--ob-surface-2);
            border: 0 !important;
            border-bottom: 1px solid var(--ob-border) !important;
            color: var(--ob-muted);
            font-size: .72rem !important;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .ob-plain-table thead th b {
            font-weight: inherit;
        }

        .ob-plain-table tbody td {
            padding: .75rem 1rem !important;
            border: 0 !important;
            border-bottom: 1px solid var(--ob-border) !important;
            font-size: .875rem;
            color: var(--ob-text);
            font-variant-numeric: tabular-nums;
        }

        .ob-plain-table tbody tr:hover td {
            background: var(--ob-surface-2);
        }

        .ob-plain-table th:nth-child(n+2),
        .ob-plain-table td:nth-child(n+2) {
            text-align: right;
        }

        /* Baris terakhir = Total */
        .ob-plain-table tbody tr:last-child td {
            border-bottom: 0 !important;
            border-top: 2px solid var(--ob-border-strong) !important;
            background: var(--ob-surface-2);
            font-weight: 700;
        }

        /* ----- DataTables di halaman Sales ----- */
        .ob-sales table.dataTable td,
        .ob-sales table.dataTable th {
            font-variant-numeric: tabular-nums;
        }

        .ob-sales table.dataTable thead th b,
        .ob-sales table.dataTable tfoot th b {
            font-weight: inherit;
        }

        .ob-sales .text-col-right,
        .ob-sales table.dataTable .text-end {
            text-align: right !important;
        }

        /* Footer total */
        .ob-page table.dataTable tfoot th,
        .ob-page table.dataTable tfoot td {
            padding: .8rem 1rem !important;
            background: var(--ob-surface-2) !important;
            border: 0 !important;
            border-top: 2px solid var(--ob-border-strong) !important;
            color: var(--ob-text);
            font-size: .85rem;
            font-weight: 700;
            white-space: nowrap;
        }

        /* scrollX memecah tabel jadi head/body/foot terpisah: border di container saja */
        .ob-page .dataTables_scroll,
        .ob-page .dt-scroll {
            border: 1px solid var(--ob-border);
            border-radius: var(--ob-radius-sm);
            overflow: hidden;
        }

        .ob-page .dataTables_scroll table.dataTable,
        .ob-page .dt-scroll table.dataTable {
            border: 0 !important;
            border-radius: 0;
        }

        .ob-page .dataTables_scrollHead,
        .ob-page .dt-scroll-head {
            background: var(--ob-surface-2);
        }

        .ob-page .dataTables_scrollBody,
        .ob-page .dt-scroll-body {
            border-bottom: 0 !important;
        }

        /* FixedColumns: kolom beku harus punya latar solid agar isi kolom lain tidak tembus */
        .ob-page table.dataTable tbody td.dtfc-fixed-left,
        .ob-page table.dataTable tbody td.dtfc-fixed-start {
            background: var(--ob-surface) !important;
            box-shadow: inset -1px 0 0 var(--ob-border) !important;
        }

        .ob-page table.dataTable thead th.dtfc-fixed-left,
        .ob-page table.dataTable thead th.dtfc-fixed-start,
        .ob-page table.dataTable tfoot th.dtfc-fixed-left,
        .ob-page table.dataTable tfoot th.dtfc-fixed-start {
            background: var(--ob-surface-2) !important;
            box-shadow: inset -1px 0 0 var(--ob-border) !important;
            z-index: 2;
        }

        .ob-page table.dataTable tbody tr:hover td.dtfc-fixed-left,
        .ob-page table.dataTable tbody tr:hover td.dtfc-fixed-start {
            background: var(--ob-surface-2) !important;
        }

        /* Baris yang membuka modal detail */
        #item-sales tbody tr,
        #category-sales tbody tr {
            cursor: pointer;
            transition: background-color .15s ease;
        }

        #item-sales tbody tr:hover td,
        #category-sales tbody tr:hover td {
            background: var(--ob-surface-2) !important;
        }

        /* Kolom Name boleh membungkus dan punya lebar minimal (sesuai columnDefs JS) */
        #item-sales td:nth-child(1),
        #item-sales th:nth-child(1) {
            white-space: normal;
            min-width: 200px;
        }

        #item-sales td:nth-child(1) .text-truncate {
            overflow: visible;
            text-overflow: unset;
            white-space: normal;
        }

        /* Modifier: baris induk & anak */
        #modifier-sales tbody tr.modifier-parent-row td {
            background-color: var(--ob-surface-2) !important;
            border-top: 1px solid var(--ob-border-strong) !important;
            font-weight: 700;
        }

        #modifier-sales tbody tr.modifier-child-row td:first-child {
            position: relative;
            padding-left: 2.5rem !important;
            font-weight: 500;
            color: var(--ob-text-2);
        }

        #modifier-sales tbody tr.modifier-child-row td:first-child::before {
            position: absolute;
            top: 50%;
            left: 1.25rem;
            width: 0.75rem;
            border-top: 1px solid var(--ob-border-strong);
            content: '';
        }

        /* ----- Tombol export (dibuat oleh JS DataTables Buttons & tombol Sales Summary) ----- */
        .ob-page .dataTables_wrapper .dt-buttons {
            position: relative;
            z-index: 5;
            float: none;
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            margin-bottom: .85rem;
        }

        .ob-page .btn-modern,
        .ob-page .dt-buttons .btn-modern.dt-button {
            display: inline-flex !important;
            align-items: center;
            gap: .45rem;
            min-height: 40px;
            margin: 0 !important;
            padding: .5rem 1rem !important;
            border: 1px solid #15803d !important;
            border-radius: 10px !important;
            background: #15803d !important;
            background-image: none !important;
            color: #fff !important;
            font-size: .85rem !important;
            font-weight: 600 !important;
            line-height: 1.2;
            box-shadow: 0 4px 12px rgba(21, 128, 61, .22) !important;
            transition: transform .12s ease, box-shadow .15s ease, background-color .15s ease;
        }

        .ob-page .btn-modern:hover,
        .ob-page .dt-buttons .btn-modern.dt-button:hover:not(.disabled) {
            background: #166534 !important;
            border-color: #166534 !important;
            transform: translateY(-1px);
        }

        .ob-page .btn-modern:disabled {
            opacity: .7;
            transform: none;
        }

        /* Ikon Excel konsisten (FontAwesome), dipakai juga bila MDI tidak termuat */
        .ob-page .btn-modern .mdi.mdi-file-excel::before {
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            content: "\f1c3";
            font-size: .95rem;
        }

        .ob-page .btn-modern .mdi {
            margin: 0;
            font-size: .95rem;
            line-height: 1;
        }

        /* ----- Toolbar khusus Item Sales (layout dom dari JS) ----- */
        #item-sales_wrapper .item-sales-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 14px;
        }

        #item-sales_wrapper .item-sales-export-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 12px;
        }

        #item-sales_wrapper .item-sales-export-row .dt-buttons {
            margin-bottom: 0;
        }

        #item-sales_wrapper .item-sales-export-row__left,
        #item-sales_wrapper .item-sales-export-row__right {
            display: flex;
            align-items: center;
        }

        #item-sales_wrapper .item-sales-export-row__right {
            justify-content: flex-end;
        }

        #item-sales_wrapper .item-sales-toolbar__left,
        #item-sales_wrapper .item-sales-toolbar__right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        #item-sales_wrapper .item-sales-toolbar__left {
            flex: 1 1 auto;
            flex-wrap: wrap;
        }

        #item-sales_wrapper .item-sales-toolbar__right {
            flex: 0 1 320px;
            min-width: 240px;
            justify-content: flex-end;
        }

        #item-sales_wrapper .item-sales-toolbar__right .dataTables_filter,
        #item-sales_wrapper .item-sales-toolbar__right .dt-search,
        #item-sales_wrapper .item-sales-toolbar__right label {
            width: 100%;
        }

        #item-sales_wrapper .item-sales-toolbar__right input {
            min-width: 0;
            width: 100%;
        }

        #item-sales_wrapper .dataTables_length,
        #item-sales_wrapper .dt-length,
        #item-sales_wrapper .dataTables_filter,
        #item-sales_wrapper .dt-search {
            float: none;
            margin: 0;
        }

        #item-sales_wrapper .dataTables_length label,
        #item-sales_wrapper .dt-length,
        #item-sales_wrapper .dataTables_filter label,
        #item-sales_wrapper .dt-search {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            white-space: nowrap;
        }

        #item-sales_wrapper .item-sales-zero-filter {
            display: flex;
            align-items: center;
            min-height: 40px;
            padding: 0 .85rem;
            border: 1px solid var(--ob-border);
            border-radius: var(--ob-radius-sm);
            background: var(--ob-surface-2);
            white-space: nowrap;
        }

        #item-sales_wrapper .item-sales-zero-filter .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: auto;
            margin: 0;
            padding-left: 0;
        }

        #item-sales_wrapper .item-sales-zero-filter .form-check-input {
            float: none;
            margin: 0;
            cursor: pointer;
        }

        #item-sales_wrapper .item-sales-zero-filter .form-check-input:checked {
            background-color: var(--ob-primary);
            border-color: var(--ob-primary);
        }

        #item-sales_wrapper .item-sales-zero-filter .form-check-label {
            margin: 0;
            line-height: 1.2;
            font-size: .82rem !important;
            color: var(--ob-text-2) !important;
            cursor: pointer;
        }

        @media (max-width: 991.98px) {

            #item-sales_wrapper .item-sales-export-row,
            #item-sales_wrapper .item-sales-toolbar,
            #item-sales_wrapper .item-sales-toolbar__left,
            #item-sales_wrapper .item-sales-toolbar__right {
                align-items: stretch;
            }

            #item-sales_wrapper .item-sales-toolbar {
                flex-direction: column;
            }

            #item-sales_wrapper .item-sales-toolbar__right,
            #item-sales_wrapper .dataTables_filter,
            #item-sales_wrapper .dt-search,
            #item-sales_wrapper .dataTables_filter label,
            #item-sales_wrapper .dt-search label,
            #item-sales_wrapper .dataTables_filter input,
            #item-sales_wrapper .dt-search input {
                width: 100%;
            }

            #item-sales_wrapper .item-sales-toolbar__right {
                min-width: 0;
            }
        }

        @media (max-width: 575.98px) {
            #item-sales_wrapper .item-sales-export-row {
                align-items: stretch;
                flex-direction: column;
            }

            #item-sales_wrapper .item-sales-export-row__left,
            #item-sales_wrapper .item-sales-export-row__right,
            #item-sales_wrapper .item-sales-zero-filter {
                width: 100%;
            }

            #item-sales_wrapper .item-sales-export-row__right {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Laporan</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-chart-bar"></i></span>
                    Sales
                </h1>
                <p class="ob-header__desc">Ringkasan penjualan, metode pembayaran, item, kategori, diskon, pajak, dan kasir.</p>
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
                    <label for="date_range_transaction"><i class="far fa-calendar-alt" aria-hidden="true"></i> Rentang Tanggal</label>
                    <div class="ob-daterange">
                        <button class="ob-daterange__btn" type="button" id="prevDate" aria-label="Mundur satu hari">
                            <i class="fas fa-chevron-left" aria-hidden="true"></i>
                        </button>
                        <input type="text" id="date_range_transaction" name="date_range_transaction"
                            class="form-control" autocomplete="off">
                        <button class="ob-daterange__btn" type="button" id="nextDate" aria-label="Maju satu hari">
                            <i class="fas fa-chevron-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="ob-card">
            <div class="ob-sales">
                {{-- Navigasi laporan --}}
                <div class="nav flex-column ob-sidenav" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <div class="ob-sidenav__label">Jenis Laporan</div>
                    <a class="nav-link active" id="sales-summary-tab-nobd" data-bs-toggle="pill"
                        href="#sales-summary-nobd" role="tab" aria-controls="sales-summary-nobd"
                        aria-selected="true"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i> Sales Summary</a>
                    <a class="nav-link" id="gross-profit-tab-nobd" data-bs-toggle="pill" href="#gross-profit-nobd"
                        role="tab" aria-controls="gross-profit-nobd" aria-selected="false"><i class="fas fa-chart-line" aria-hidden="true"></i> Gross Profit</a>
                    <a class="nav-link" id="payment-method-tab-nobd" data-bs-toggle="pill"
                        href="#payment-method-nobd" role="tab" aria-controls="payment-method-nobd"
                        aria-selected="false"><i class="fas fa-credit-card" aria-hidden="true"></i> Payment Method</a>
                    <a class="nav-link" id="payment-method-merchant-tab-nobd" data-bs-toggle="pill"
                        href="#payment-method-merchant-nobd" role="tab" aria-controls="payment-method-merchant-nobd"
                        aria-selected="false"><i class="fas fa-store-alt" aria-hidden="true"></i> Payment Method (Merchant)</a>
                    <a class="nav-link" id="sales-type-tab-nobd" data-bs-toggle="pill" href="#sales-type-nobd"
                        role="tab" aria-controls="sales-type-nobd" aria-selected="false"><i class="fas fa-concierge-bell" aria-hidden="true"></i> Sales Type</a>
                    <a class="nav-link" id="item-sales-tab-nobd" data-bs-toggle="pill" href="#item-sales-nobd"
                        role="tab" aria-controls="item-sales-nobd" aria-selected="false"><i class="fas fa-hamburger" aria-hidden="true"></i> Item Sales</a>
                    <a class="nav-link" id="category-sales-tab-nobd" data-bs-toggle="pill"
                        href="#category-sales-nobd" role="tab" aria-controls="category-sales-nobd"
                        aria-selected="false"><i class="fas fa-tags" aria-hidden="true"></i> Category Sales</a>
                    <a class="nav-link" id="modifier-sales-tab-nobd" data-bs-toggle="pill"
                        href="#modifier-sales-nobd" role="tab" aria-controls="modifier-sales-nobd"
                        aria-selected="false"><i class="fas fa-sliders-h" aria-hidden="true"></i> Modifier Sales</a>
                    <a class="nav-link" id="discount-tab-nobd" data-bs-toggle="pill" href="#discount-nobd"
                        role="tab" aria-controls="discount-nobd" aria-selected="false"><i class="fas fa-percent" aria-hidden="true"></i> Discounts</a>
                    <a class="nav-link" id="taxes-tab-nobd" data-bs-toggle="pill" href="#taxes-nobd" role="tab"
                        aria-controls="taxes-nobd" aria-selected="false"><i class="fas fa-landmark" aria-hidden="true"></i> Taxes</a>
                    <a class="nav-link" id="collected-by-tab-nobd" data-bs-toggle="pill"
                        href="#collected-by-nobd" role="tab" aria-controls="collected-by-nobd"
                        aria-selected="false"><i class="fas fa-user-check" aria-hidden="true"></i> Collected By</a>
                </div>

                {{-- Konten laporan --}}
                <div class="ob-sales__content">
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="sales-summary-nobd" role="tabpanel"
                            aria-labelledby="sales-summary-tab-nobd">
                            <div class="ob-pane-head">
                                <div>
                                    <h2 class="ob-pane-head__title">Sales Summary</h2>
                                    <p class="ob-pane-head__desc">Ringkasan penjualan kotor hingga total yang diterima.</p>
                                </div>
                                <button id="btn-export-sales-summary" class="btn btn-sm btn-success btn-modern">
                                    <i class="fas fa-file-excel" aria-hidden="true"></i> Export Excel
                                </button>
                            </div>

                            <div class="ob-statement">
                                <div class="ob-statement__head">
                                    <span>Keterangan</span>
                                    <span>Jumlah</span>
                                </div>
                                <div class="ob-statement__row">
                                    <div>Gross Sales</div>
                                    <div id="gross-sales-summary">Rp. 0</div>
                                </div>
                                <div class="ob-statement__row ob-statement__row--indent">
                                    <div>Discounts</div>
                                    <div id="discount-sales-summary">Rp. 0</div>
                                </div>
                                {{-- <div class="ob-statement__row ob-statement__row--indent">
                                    <div>Refunds</div>
                                    <div>Rp. 0</div>
                                </div> --}}
                                <div class="ob-statement__row ob-statement__row--subtotal">
                                    <div>Net Sales</div>
                                    <div><strong id="net-sales-summary">Rp. 0</strong></div>
                                </div>
                                <div class="ob-statement__row ob-statement__row--indent">
                                    <div>Tax</div>
                                    <div id="tax-sales-summary">Rp. 0</div>
                                </div>
                                <div class="ob-statement__row ob-statement__row--indent">
                                    <div>Rounding</div>
                                    <div id="rounding-sales-summary">Rp. 0</div>
                                </div>
                                <div class="ob-statement__row ob-statement__row--grand">
                                    <div>Total Collected</div>
                                    <div><strong id="total-collected-sales-summary">Rp. 0</strong></div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="gross-profit-nobd" role="tabpanel"
                            aria-labelledby="gross-profit-tab-nobd">
                            <div class="ob-pane-head">
                                <div>
                                    <h2 class="ob-pane-head__title">Gross Profit</h2>
                                    <p class="ob-pane-head__desc">Net Sales dikurangi Cost of Goods Sold (COGS).</p>
                                </div>
                            </div>

                            <div class="ob-callout" role="note">
                                <i class="fas fa-info-circle" aria-hidden="true"></i>
                                <div>
                                    <p>Gross Profit</p>
                                    Gross Profit is your Net sales minus cost of Goods Sold (COGS). <b>To Report Gross
                                        Profit Accurately, please make sure all items have a COGS</b>
                                </div>
                            </div>

                            <div class="ob-statement">
                                <div class="ob-statement__head">
                                    <span>Keterangan</span>
                                    <span>Jumlah</span>
                                </div>
                                <div class="ob-statement__row">
                                    <div>Gross Sales</div>
                                    <div id="gross-sales-gross-profit">Rp. 0</div>
                                </div>
                                <div class="ob-statement__row ob-statement__row--indent">
                                    <div>Discounts</div>
                                    <div id="discount-gross-profit">Rp. 0</div>
                                </div>
                                <div class="ob-statement__row ob-statement__row--subtotal">
                                    <div>Net Sales</div>
                                    <div><strong id="net-sales-gross-profit">Rp. 0 <span
                                                class="badge badge-success">100%</span></strong></div>
                                </div>
                                <div class="ob-statement__row">
                                    <div>Cost Of Goods Sold (COGS)</div>
                                    <div><strong id="cogs-gross-profit">Rp. 0 <span
                                                class="badge badge-danger">0%</span></strong></div>
                                </div>
                                <div class="ob-statement__row ob-statement__row--grand">
                                    <div>Gross Profit</div>
                                    <div><strong id="gross-profit">Rp. 0 <span
                                                class="badge badge-success">100%</span></strong></div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="payment-method-nobd" role="tabpanel"
                            aria-labelledby="payment-method-tab-nobd">
                            <div class="ob-pane-head">
                                <div>
                                    <h2 class="ob-pane-head__title">Payment Method</h2>
                                    <p class="ob-pane-head__desc">Jumlah transaksi dan total yang diterima per metode pembayaran.</p>
                                </div>
                            </div>
                            <div class="ob-plain-table">
                                <table id="payment-method-table" class="table">
                                    <thead>
                                        <tr>
                                            <th><b>Payment Method</b></th>
                                            <th><b>Number of Transactions</b></th>
                                            <th><b>Total Collected</b></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data akan dimuat di sini -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="payment-method-merchant-nobd" role="tabpanel"
                            aria-labelledby="payment-method-merchant-tab-nobd">
                            <div class="ob-pane-head">
                                <div>
                                    <h2 class="ob-pane-head__title">Payment Method (Merchant)</h2>
                                    <p class="ob-pane-head__desc">Rincian metode pembayaran per merchant.</p>
                                </div>
                            </div>
                            <div class="ob-plain-table">
                                <table id="payment-method-merchant-table" class="table">
                                    <thead>
                                        <tr>
                                            <th><b>Payment Method</b></th>
                                            <th><b>Number of Transactions</b></th>
                                            <th><b>Total Collected</b></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data akan dimuat di sini -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="sales-type-nobd" role="tabpanel"
                            aria-labelledby="sales-type-tab-nobd">
                            <div class="ob-pane-head">
                                <div>
                                    <h2 class="ob-pane-head__title">Sales Type</h2>
                                    <p class="ob-pane-head__desc">Penjualan berdasarkan tipe penjualan.</p>
                                </div>
                            </div>
                            <table id="sales-type" class="display table table-bordered" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Sales Type</th>
                                        <th>Count</th>
                                        <th>Total Collected</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="item-sales-nobd" role="tabpanel"
                            aria-labelledby="item-sales-tab-nobd">
                            <div class="ob-pane-head">
                                <div>
                                    <h2 class="ob-pane-head__title">Item Sales</h2>
                                    <p class="ob-pane-head__desc">Penjualan per item. Klik baris untuk melihat detail.</p>
                                </div>
                            </div>
                            <table id="item-sales" class="table display row-border order-column " style="width:100%">
                                <thead id="head-item-sales">
                                    <tr>
                                        <th><b>Name</b></th>
                                        <th><b>Category</b></th>
                                        <th><b>Item Sold</b></th>
                                        <th><b>Gross Sales</b></th>
                                        <th><b>Discounts</b></th>
                                        <th><b>Net Sales</b></th>
                                        <th><b>Gross Profit</b></th>
                                        <th><b>Gross Margin</b></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th colspan="1">Total</th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="category-sales-nobd" role="tabpanel"
                            aria-labelledby="category-sales-tab-nobd">
                            <div class="ob-pane-head">
                                <div>
                                    <h2 class="ob-pane-head__title">Category Sales</h2>
                                    <p class="ob-pane-head__desc">Penjualan per kategori. Klik baris untuk melihat detail.</p>
                                </div>
                            </div>

                            <div class="ob-callout" role="note">
                                <i class="fas fa-info-circle" aria-hidden="true"></i>
                                <div>
                                    <p>Gross Profit</p>
                                    To Report Gross Profit Accurately, please make sure all items have a COGS
                                </div>
                            </div>

                            <table id="category-sales" class="table display row-border order-column"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th><b>Category</b></th>
                                        <th><b>Item Sold</b></th>
                                        <th><b>Gross Sales</b></th>
                                        <th><b>Discounts</b></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th colspan="1">Total</th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="modifier-sales-nobd" role="tabpanel"
                            aria-labelledby="modifier-sales-tab-nobd">
                            <div class="ob-pane-head">
                                <div>
                                    <h2 class="ob-pane-head__title">Modifier Sales</h2>
                                    <p class="ob-pane-head__desc">Penjualan modifier, dikelompokkan per induk modifier.</p>
                                </div>
                            </div>
                            <table id="modifier-sales" class="table display row-border order-column"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th><b>Name</b></th>
                                        <th><b>Quantity Sold</b></th>
                                        <th><b>Gross Sales</b></th>
                                        <th><b>Discounts</b></th>
                                        <th><b>Net Sales</b></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th colspan="1">Total</th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="discount-nobd" role="tabpanel"
                            aria-labelledby="discount-tab-nobd">
                            <div class="ob-pane-head">
                                <div>
                                    <h2 class="ob-pane-head__title">Discounts</h2>
                                    <p class="ob-pane-head__desc">Pemakaian diskon dan total potongan.</p>
                                </div>
                            </div>
                            <table id="discount-sales" class="table display row-border order-column"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th><b>Name</b></th>
                                        <th><b>Discount Amount</b></th>
                                        <th><b>Count</b></th>
                                        <th><b>Discount Total</b></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th colspan="1">Total</th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="taxes-nobd" role="tabpanel" aria-labelledby="taxes-tab-nobd">
                            <div class="ob-pane-head">
                                <div>
                                    <h2 class="ob-pane-head__title">Taxes</h2>
                                    <p class="ob-pane-head__desc">Pajak yang dikenakan dan total pajak terkumpul.</p>
                                </div>
                            </div>
                            <table id="tax-sales" class="table display row-border order-column" style="width:100%">
                                <thead>
                                    <tr>
                                        <th><b>Name</b></th>
                                        <th><b>Tax Rate</b></th>
                                        <th><b>Taxable Amount</b></th>
                                        <th><b>Tax Collected</b></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th colspan="1">Total</th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="collected-by-nobd" role="tabpanel"
                            aria-labelledby="collected-by-tab-nobd">
                            <div class="ob-pane-head">
                                <div>
                                    <h2 class="ob-pane-head__title">Collected By</h2>
                                    <p class="ob-pane-head__desc">Jumlah transaksi dan total yang diterima per kasir.</p>
                                </div>
                            </div>
                            <table id="collected-by-sales" class="table display row-border order-column"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th><b>Name</b></th>
                                        <th><b>Title</b></th>
                                        <th><b>Number Of Tranasctions</b></th>
                                        <th><b>Total Collected</b></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th colspan="1">Total</th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_detail" tabindex="-1" role="dialog" aria-hidden="true">
    </div>


    @push('js')
        <script src="https://cdn.datatables.net/2.2.1/js/dataTables.js"></script>
        <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

        <!-- FixedColumns 4.x (untuk DT 1.x) -->
        <script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>

        {{-- <script src="https://cdn.datatables.net/fixedcolumns/5.0.4/js/dataTables.fixedColumns.js"></script>
        <script src="https://cdn.datatables.net/fixedcolumns/5.0.4/js/fixedColumns.dataTables.js"></script> --}}


        <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
        <script>
            var outlet = $('#filter-outlet').val();
            var date = $('#date_range_transaction').val();

            function parseDateRange(val) {
                if (!val) return null;
                const parts = String(val).split(' - ');
                const from = toIsoYmd(parts[0]);
                const to = toIsoYmd(parts[1] || parts[0]);
                if (!from || !to) return null;
                return {
                    from,
                    to
                };
            }

            function fmt(dt) {
                const y = dt.getFullYear();
                const m = String(dt.getMonth() + 1).padStart(2, '0');
                const d = String(dt.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            }

            function toIsoYmd(str) {
                if (!str) return null;
                str = str.trim();
                // dd/mm/yyyy
                if (/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(str)) {
                    const [d, m, y] = str.split('/');
                    return `${y}-${m.padStart(2,'0')}-${d.padStart(2,'0')}`;
                }
                // yyyy-mm-dd
                if (/^\d{4}-\d{1,2}-\d{1,2}$/.test(str)) return str;
                const dt = new Date(str);
                return isNaN(dt) ? null : fmt(dt);
            }

            function waitUntilReadyWithIziToast(downloadUrl, filename, opts = {}) {
                const intervalMs = opts.intervalMs ?? 3000;
                const timeoutMs = opts.timeoutMs ?? 10 * 60 * 1000; // 10 menit

                const toastId = 'export-progress-' + Date.now();
                let toastEl = null;
                let elapsedSec = 0;
                let uiTimer = null;
                let pollTimer = null;
                let stopped = false;

                // Render toast dengan konten yang bisa di-update
                iziToast.show({
                    id: toastId,
                    timeout: false,
                    close: false,
                    progressBar: false,
                    position: 'topRight',
                    theme: 'light',
                    title: 'Menyiapkan Export…',
                    message: `<div id="${toastId}-wrap" style="min-width:260px">
                        <div class="mb-1">File sedang diproses di background.</div>
                        <div class="text-muted">Elapsed: <b id="${toastId}-elapsed">00:00</b></div>
                        <div class="text-muted small mt-1">Cek setiap ${(intervalMs/1000)} detik…</div>
                    </div>`,
                    buttons: [
                        ['<button>Batalkan</button>', function(instance, toast) {
                            stopped = true;
                            clearInterval(uiTimer);
                            clearInterval(pollTimer);
                            iziToast.hide({
                                transitionOut: 'fadeOut'
                            }, toast);
                            iziToast.info({
                                title: 'Dibatalkan',
                                message: 'Polling export dihentikan.'
                            });
                        }]
                    ],
                    onOpening: function(instance, toast) {
                        toastEl = toast;
                    },
                    onClosing: function() {
                        clearInterval(uiTimer);
                        clearInterval(pollTimer);
                    }
                });

                // Timer UI: update elapsed tiap 1s
                uiTimer = setInterval(function() {
                    elapsedSec++;
                    const el = document.getElementById(`${toastId}-elapsed`);
                    if (el) el.textContent = fmtElapsed(elapsedSec);
                }, 1000);

                const startedAt = Date.now();

                // Timer Polling: HEAD ke downloadUrl
                pollTimer = setInterval(function() {
                    if (stopped) return;

                    // timeout guard
                    if (Date.now() - startedAt > timeoutMs) {
                        clearInterval(uiTimer);
                        clearInterval(pollTimer);
                        iziToast.hide({}, toastEl);
                        iziToast.warning({
                            title: 'Timeout',
                            message: 'Menunggu file terlalu lama. Coba lagi nanti atau periksa antrian export.'
                        });
                        return;
                    }

                    $.ajax({
                            url: downloadUrl,
                            type: 'HEAD',
                            cache: false
                        })
                        .done(function() {
                            if (stopped) return;
                            clearInterval(uiTimer);
                            clearInterval(pollTimer);
                            iziToast.hide({
                                transitionOut: 'fadeOut'
                            }, toastEl);
                            iziToast.success({
                                title: 'Siap',
                                message: 'File siap diunduh. Mengunduh…',
                                timeout: 2000
                            });
                            triggerDownload(downloadUrl, filename || 'export.xlsx');
                        })
                        .fail(function() {
                            // belum siap → diam saja; toast tetap menampilkan timer
                        });
                }, intervalMs);
            }

            function triggerDownload(url, filename = 'export.xlsx') {
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
            }

            function fmtElapsed(sec) {
                const m = String(Math.floor(sec / 60)).padStart(2, '0');
                const s = String(sec % 60).padStart(2, '0');
                return `${m}:${s}`;
            }


            function checkActiveTab() {
                var activeTab = $('a.nav-link.active').attr('href');
                console.log('Active Tab:', activeTab);

                outlet = $('#filter-outlet').val();
                date = $('#date_range_transaction').val();

                console.log(date)

                if (activeTab === '#sales-summary-nobd') {
                    // Logika untuk Sales Summary
                    $.ajax({
                        url: '{{ route('report/sales/getSalesSummary') }}',
                        method: 'GET',
                        data: {
                            date: date,
                            outlet: outlet
                        },
                        beforeSend: function() {
                            showLoader();
                        },
                        complete: function() {
                            showLoader(false);
                        },
                        success: function(data) {
                            console.log(data);

                            $('#gross-sales-summary').text(formatRupiah(data.grossSales.toString(), 'Rp. '));
                            $('#discount-sales-summary').text(formatRupiah(data.discount.toString(), 'Rp. '));
                            $('#net-sales-summary').text(formatRupiah(data.netSales.toString(), 'Rp. '));
                            $('#tax-sales-summary').text(formatRupiah(data.tax.toString(), 'Rp. '));
                            $('#rounding-sales-summary').text(formatRupiah(data.rounding.toString(), 'Rp. '));
                            $('#total-collected-sales-summary').text(formatRupiah(data.totalCollect.toString(),
                                'Rp. '));

                        },
                        error: function(xhr) {
                            console.error(xhr);
                        }
                    });
                } else if (activeTab === '#gross-profit-nobd') {
                    $.ajax({
                        url: '{{ route('report/sales/getGrossProfit') }}',
                        method: 'GET',
                        data: {
                            date: date,
                            outlet: outlet
                        },
                        beforeSend: function() {
                            showLoader();
                        },
                        complete: function() {
                            showLoader(false);
                        },
                        success: function(data) {
                            console.log(data);

                            $('#gross-sales-gross-profit').text(formatRupiah(data.grossSales.toString(), 'Rp. '));
                            $('#discount-gross-profit').text(formatRupiah(data.discount.toString(), 'Rp. '));
                            $('#net-sales-gross-profit').html(formatRupiah(data.netSales.toString(), 'Rp. ') + ' ' +
                                '<span class="badge badge-success">100%</span>');
                            $('#gross-profit').html(formatRupiah(data.netSales.toString(), 'Rp. ') + ' ' +
                                '<span class="badge badge-success">100%</span>');
                        },
                        error: function(xhr) {
                            console.error(xhr);
                        }
                    });

                } else if (activeTab === '#payment-method-nobd') {
                    $.ajax({
                        url: '{{ route('report/sales/getPaymentMethodSales') }}',
                        method: 'GET',
                        data: {
                            date: date,
                            outlet: outlet
                        },
                        beforeSend: function() {
                            showLoader();
                        },
                        complete: function() {
                            showLoader(false);
                        },
                        success: function(data) {
                            var tbody = $('#payment-method-table tbody');
                            tbody.empty();

                            let numberOfTransaction = 0;
                            let totalCollected = 0;

                            $.each(data, function(index, transaction) {
                                console.log(transaction);
                                numberOfTransaction += transaction.number_of_transactions != '' ?
                                    transaction.number_of_transactions : 0;
                                totalCollected += transaction.total_collected != '' ? transaction
                                    .total_collected : 0;

                                if (transaction.parent) {
                                    if (transaction.payment_method == "Cash") {
                                        tbody.append(`
                                            <tr>
                                                <td>${transaction.payment_method}</td>
                                                <td>${transaction.number_of_transactions}</td>
                                                <td>${formatRupiah(transaction.total_collected.toString(), "Rp. ")}</td>
                                            </tr>
                                        `);
                                    } else {
                                        tbody.append(`
                                            <tr>
                                                <td>${transaction.payment_method}</td>
                                                <td>${transaction.number_of_transactions}</td>
                                                <td></td>
                                            </tr>
                                        `);
                                    }
                                } else {
                                    tbody.append(`
                                        <tr>
                                            <td style="color: rgb(133 133 133 / 75%) !important;">&nbsp ${transaction.payment_method}</td>
                                            <td>${transaction.number_of_transactions}</td>
                                            <td>${formatRupiah(transaction.total_collected.toString(), "Rp. ")}</td>
                                        </tr>
                                    `);
                                }
                            });

                            tbody.append(`
                                <tr>
                                    <td>Total</td>
                                    <td>${numberOfTransaction}</td>
                                    <td>${formatRupiah(totalCollected.toString(), "Rp. ")}</td>
                                </tr>
                            `);
                        },
                        error: function(xhr) {
                            console.error(xhr);
                        }
                    });
                } else if (activeTab === '#payment-method-merchant-nobd') {
                    $.ajax({
                        url: '{{ route('report/sales/getPaymentMerchantMethodSales') }}',
                        method: 'GET',
                        data: {
                            date: date,
                            outlet: outlet
                        },
                        beforeSend: function() {
                            showLoader();
                        },
                        complete: function() {
                            showLoader(false);
                        },
                        success: function(data) {
                            var tbody = $('#payment-method-merchant-table tbody');
                            tbody.empty();

                            let numberOfTransaction = 0;
                            let totalCollected = 0;

                            $.each(data, function(index, transaction) {
                                console.log(transaction);
                                numberOfTransaction += transaction.number_of_transactions != '' ?
                                    transaction.number_of_transactions : 0;
                                totalCollected += transaction.total_collected != '' ? transaction
                                    .total_collected : 0;

                                if (transaction.parent) {
                                    if (transaction.payment_method == "Cash") {
                                        tbody.append(`
                                            <tr>
                                                <td>${transaction.payment_method}</td>
                                                <td>${transaction.number_of_transactions}</td>
                                                <td>${formatRupiah(transaction.total_collected.toString(), "Rp. ")}</td>
                                            </tr>
                                        `);
                                    } else {
                                        tbody.append(`
                                            <tr>
                                                <td>${transaction.payment_method}</td>
                                                <td>${transaction.number_of_transactions}</td>
                                                <td></td>
                                            </tr>
                                        `);
                                    }
                                } else {
                                    tbody.append(`
                                        <tr>
                                            <td style="color: rgb(133 133 133 / 75%) !important;">&nbsp ${transaction.payment_method}</td>
                                            <td>${transaction.number_of_transactions}</td>
                                            <td>${formatRupiah(transaction.total_collected.toString(), "Rp. ")}</td>
                                        </tr>
                                    `);
                                }
                            });

                            tbody.append(`
                                <tr>
                                    <td>Total</td>
                                    <td>${numberOfTransaction}</td>
                                    <td>${formatRupiah(totalCollected.toString(), "Rp. ")}</td>
                                </tr>
                            `);
                        },
                        error: function(xhr) {
                            console.error(xhr);
                        }
                    });
                } else if (activeTab === '#sales-type-nobd') {
                    // Hancurkan instance DataTable jika sudah ada
                    if ($.fn.dataTable.isDataTable('#sales-type')) {
                        $('#sales-type').DataTable().destroy();
                    }

                    var tableSales = $('#sales-type').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: '{{ route('report/sales/getSalesType') }}', // Make sure this URL matches your Laravel route
                            type: 'GET',
                            data: {
                                date: date,
                                outlet: outlet
                            },
                        },
                        columns: [{
                                data: 'sales_type',
                                name: 'sales_type'
                            },
                            {
                                data: 'count',
                                name: 'count'
                            },
                            {
                                data: 'total_collected',
                                name: 'total_collected',
                                className: 'text-col-right'
                            }
                        ],
                        paging: false, // Menghilangkan pagination
                        searching: false, // Menghilangkan search bar
                        ordering: false,
                        initComplete: function(settings, json) {
                            var totalTransaction = 0;
                            console.log(json);
                            json.data.forEach(function(item) {
                                item.item_transaction.forEach(function(transactionItem) {
                                    totalTransaction += transactionItem.variant.harga;
                                })
                            });
                            console.log(totalTransaction);
                            // Menambahkan baris kustom setelah semua data dimuat
                            var customRow = {
                                sales_type: "Total Collected",
                                count: '',
                                total_collected: formatRupiah(totalTransaction.toString(), "Rp. ")
                            };

                            $(customRow).addClass('border-top');

                            tableSales.row.add(customRow).draw(true); // Menambahkan baris kustom
                        }
                    });
                } else if (activeTab === "#item-sales-nobd") {
                    if ($.fn.dataTable.isDataTable('#item-sales')) {
                        $('#item-sales').DataTable().destroy();

                        // kalau sebelumnya kamu pernah menambah baris header tambahan, bersihkan:
                        $('#item-sales').find('thead tr:gt(0)').remove(); // sisakan hanya baris header pertama
                    }

                    var tableSales = $('#item-sales').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: '{{ route('report/sales/getItemSales') }}', // Make sure this URL matches your Laravel route
                            type: 'GET',
                            data: function(d) {
                                d.date = date;
                                d.outlet = outlet;
                                var $hideZeroSales = $('#item-sales-hide-zero');
                                d.hide_zero_sales = !$hideZeroSales.length || $hideZeroSales.is(':checked') ? 1 : 0;
                            },
                        },
                        columns: [{
                                data: 'name',
                                name: 'name'
                            },
                            {
                                data: 'category',
                                name: 'category'
                            },
                            {
                                data: 'item_sold',
                                name: 'item_sold',
                                searchable: false,
                            },
                            {
                                data: 'gross_sales',
                                name: 'gross_sales',
                                searchable: false,
                            },
                            {
                                data: 'discounts',
                                name: 'discounts',
                                searchable: false,
                            },
                            {
                                data: 'net_sales',
                                name: 'net_sales',
                                searchable: false,
                            },
                            {
                                data: 'gross_profit',
                                name: 'gross_profit',
                                searchable: false,
                            },
                            {
                                data: 'gross_margin',
                                name: 'gross_margin',
                                searchable: false,
                            }
                        ],
                        paging: true,
                        pageLength: 25,
                        lengthMenu: [25, 50, 100],
                        searching: true,
                        ordering: true,
                        orderMulti: true,
                        order: [
                            [2, 'desc'],
                            [0, 'asc']
                        ],
                        scrollCollapse: true,
                        scrollX: true,
                        scrollY: 500,
                        autoWidth: false,
                        info: true,
                        dom: "<'item-sales-export-row'<'item-sales-export-row__left'B><'item-sales-export-row__right'>><'item-sales-toolbar'<'item-sales-toolbar__left'l><'item-sales-toolbar__right'f>>rtip",
                        buttons: [{
                                text: '<span class="mdi mdi-file-excel"></span> Export Excel',
                                className: 'btn btn-modern btn-excel',
                                action: function (e, dt, node, config) {
                                    // ambil filter yang lagi dipakai
                                    var dateVal   = date;          // kalau variabel date di luar scope, pakai selector input-nya
                                    var outletVal = outlet;        // atau: $('#outlet').val();

                                    var params = new URLSearchParams({
                                        date: dateVal,
                                        outlet: outletVal
                                    });

                                    // window.location = '{{ route('report/sales/exportItemSales') }}?' + params.toString();

                                    // BATAS BARU

                                    e.preventDefault();

                                    const $btn    = $(node); // button DOM dari DataTables
                                    const $dateEl = $('#date_range_transaction');
                                    const $outlet = $('#filter-outlet');

                                    const range = parseDateRange($dateEl.val());
                                    if (!range) {
                                        iziToast.error({
                                            title: 'Gagal',
                                            message: "Isi tanggal dengan format <b>YYYY-MM-DD - YYYY-MM-DD</b>."
                                        });
                                        return;
                                    }

                                    const payload = {
                                        from: range.from,
                                        to:   range.to
                                    };

                                    const ov = $outlet.val();
                                    if (Array.isArray(ov)) {
                                        ov.forEach(id => {
                                            (payload['outlet_id[]'] = (payload['outlet_id[]'] || [])).push(id);
                                        });
                                    } else if (ov && ov !== 'all') {
                                        payload['outlet_id[]'] = ov;
                                    }

                                    // Tombol jadi loading
                                    const originalHtml = $btn.html();
                                    $btn.prop('disabled', true).html(
                                        '<span class="spinner-border spinner-border-sm mr-1"></span>Memproses…'
                                    );

                                    $.ajax({
                                        url: "{{ route('report/sales/exportItemSales') }}", // controller ->queue()
                                        method: 'POST',
                                        data: payload,
                                        headers: {
                                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                        }
                                    })
                                    .done(function(resp) {
                                        if (resp && resp.ok && resp.download_url) {
                                            const filename = (resp.path || 'item_sales.xlsx').split('/').pop();

                                            // Helper yang sudah kamu pakai di exportPosFormat
                                            waitUntilReadyWithIziToast(resp.download_url, filename, {
                                                intervalMs: 3000,
                                                timeoutMs: 10 * 60 * 1000
                                            });
                                        } else {
                                            iziToast.error({
                                                title: 'Gagal',
                                                message: (resp && resp.message) || 'Export gagal dimulai.'
                                            });
                                        }
                                    })
                                    .fail(function(xhr) {
                                        let msg = 'Export gagal.';
                                        try {
                                            msg = (JSON.parse(xhr.responseText)).message || msg;
                                        } catch (_) {}

                                        iziToast.warning({
                                            title: 'Error',
                                            message: msg
                                        });
                                    })
                                    .always(function() {
                                        $btn.prop('disabled', false).html(originalHtml);
                                    });
                                }
                            },
                            // {
                            //     extend: 'excelHtml5',
                            //     text: '<span class="mdi mdi-file-excel"></span> Export Excel',
                            //     className: 'btn btn-modern btn-excel',
                            //     title: 'Item Sales Report', // judul di file
                            //     filename: 'item_sales_report', // nama file
                            //     sheetName: 'Item Sales', // nama sheet (Excel)
                            //     exportOptions: {
                            //         columns: [0, 1, 2, 3, 4, 5, 6]
                            //     } // misal kolom ke 0,1,3,5
                            // },
                            // {
                            //     extend: 'csvHtml5',
                            //     title: 'Item Sales Report',
                            //     exportOptions: {
                            //         columns: ':visible'
                            //     }
                            // }
                        ],
                        fixedHeader: false,
                        fixedColumns: {
                            start: 1,
                            end: 0
                        },
                        columnDefs: [
                            // Kolom Name (index 0): buat lebih lebar
                            {
                                targets: 0,
                                width: 320
                            }, // atau '320px'
                            // Kolom angka: width tetap agar Name bisa “mencuri” ruang
                            {
                                targets: [3, 4, 5, 6],
                                width: 120,
                                className: 'text-end'
                            }
                        ],
                        colResize: {
                            saveState: true,
                            hasBoundCheck: true
                        },
                        footerCallback: function(row, data, start, end, display) {
                            var api = this.api();
                            var json = api.ajax.json();
                            var totals = json && json.totals ? json.totals : {};

                            $(api.column(2).footer()).html(totals.item_sold || 0);
                            $(api.column(3).footer()).html(formatRupiah(String(totals.gross_sales || 0), "Rp. "));
                            $(api.column(4).footer()).html(formatRupiah(String(totals.discounts || 0), "Rp. "));
                            $(api.column(5).footer()).html(formatRupiah(String(totals.net_sales || 0), "Rp. "));
                            $(api.column(6).footer()).html(formatRupiah(String(totals.gross_profit || 0), "Rp. "));
                        },
                        initComplete: function(setting, json) {
                            $('#item-sales_wrapper .item-sales-export-row__right').append(`
                                <div class="item-sales-zero-filter">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                            id="item-sales-hide-zero" checked>
                                        <label class="form-check-label" for="item-sales-hide-zero">
                                            Sembunyikan item tanpa penjualan
                                        </label>
                                    </div>
                                </div>
                            `);
                            $('.dt-scroll-body table thead').remove();
                            $('.dt-scroll-body table tfoot').remove();
                        },
                    });

                    $('#item-sales-nobd').off('change.itemSales', '#item-sales-hide-zero')
                        .on('change.itemSales', '#item-sales-hide-zero', function() {
                        tableSales.ajax.reload();
                    });

                    // Event handler untuk menghapus header dan footer setiap kali tabel di redraw
                    tableSales.on('draw', function() {
                        // Cek apakah input kosong atau tidak
                        if ($('.dt-input').val() !== '') {
                            $('.dt-scroll-head').addClass('d-none');
                            $('.dt-scroll-foot').addClass('d-none');
                        }
                    });

                    // setiap kali tabel redraw / re-size / re-order, hide lagi header body
                    tableSales.on('draw.dt order.dt column-sizing.dt', function() {
                        $('.dt-scroll-body table thead').remove();
                    });

                    $('#item-sales tbody').off().on('click', 'tr', function() {
                        var data = tableSales.row(this).data();
                        console.log(data);
                        $.ajax({
                            url: '{{ route('report/sales/getDetailItemSales') }}',
                            method: 'GET',
                            data: {
                                date: date,
                                outlet: outlet,
                                idProduct: data.product_id,
                                idVariant: data.id,
                                nameProductVariant: data.name
                            },
                            beforeSend: function() {
                                showLoader();
                            },
                            complete: function() {
                                showLoader(false);
                            },
                            success: function(data) {
                                const modal = $('#modal_detail');

                                modal.html(data);
                                modal.modal('show');

                            },
                            error: function(xhr) {
                                console.error(xhr);
                            }
                        });
                    });

                } else if (activeTab === "#category-sales-nobd") {
                    if ($.fn.dataTable.isDataTable('#category-sales')) {
                        $('#category-sales').DataTable().destroy();
                    }

                    var categorySales = $('#category-sales').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: '{{ route('report/sales/getCategorySales') }}', // Make sure this URL matches your Laravel route
                            type: 'GET',
                            data: {
                                date: date,
                                outlet: outlet
                            },
                        },
                        columns: [{
                                data: 'category',
                                name: 'category'
                            },
                            {
                                data: 'item_sold',
                                name: 'item_sold'
                            },
                            {
                                data: 'gross_sales',
                                name: 'gross_sales',
                            },
                            {
                                data: 'discounts',
                                name: 'discounts',
                            },
                        ],
                        paging: false, // Menghilangkan pagination
                        searching: false, // Menghilangkan search bar
                        ordering: true,
                        scrollX: true,
                        scrollCollapse: true,
                        scrollY: 500,
                        fixedColumns: {
                            start: 1,
                        },
                        dom: 'Bfrtip',
                        buttons: [{
                            extend: 'excelHtml5',
                            text: '<span class="mdi mdi-file-excel"></span> Export Excel',
                            className: 'btn btn-modern btn-excel',
                            title: 'Category Sales Report', // judul di file
                            filename: 'category_sales_report', // nama file
                            sheetName: 'Category Sales', // nama sheet (Excel)
                            exportOptions: {
                                columns: ':visible'
                            } // misal kolom ke 0,1,3,5
                        }, ],
                        columnDefs: [{
                                targets: 0,
                                width: '200px'
                            } // Menetapkan lebar kolom pertama menjadi 200px
                        ],
                        initComplete: function(setting, json) {
                            console.log(json)
                            $('.dt-scroll-body table thead').remove();
                            $('.dt-scroll-body table tfoot').remove();
                        },
                        footerCallback: function(row, data, start, end, display) {
                            var api = this.api();

                            // Menghitung total untuk setiap kolom yang diinginkan
                            var totalItemSold = api.column(1).data().reduce(function(a, b) {
                                return parseInt(a) + parseInt(b);
                            }, 0);

                            var totalGrossSales = api.column(2).data().reduce(function(a, b) {
                                // return parseFloat(a) + parseFloat(b);
                                return getAmount(a.toString()) + getAmount(b.toString());
                            }, 0);

                            var totalDiscounts = api.column(3).data().reduce(function(a, b) {
                                // return parseFloat(a) + parseFloat(b);
                                return getAmount(a.toString()) + getAmount(b.toString());
                            }, 0);


                            // Menampilkan total di footer
                            $(api.column(1).footer()).html(totalItemSold);
                            $(api.column(2).footer()).html(formatRupiah(totalGrossSales.toString(), "Rp. "));
                            $(api.column(3).footer()).html(formatRupiah(totalDiscounts.toString(), "Rp. "));
                        }
                    });

                    // Pastikan untuk menambahkan elemen <tfoot> di HTML Anda
                    $('#category-sales tfoot').append(`
                        <tr>
                            <th colspan="1">Total</th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    `);

                    $('#category-sales tbody').off().on('click', 'tr', function() {
                        var data = categorySales.row(this).data();
                        console.log(data);
                        $.ajax({
                            url: '{{ route('report/sales/getDetailItemCategorySales') }}',
                            method: 'GET',
                            data: {
                                date: date,
                                outlet: outlet,
                                idCategory: data.id
                            },
                            beforeSend: function() {
                                showLoader();
                            },
                            complete: function() {
                                showLoader(false);
                            },
                            success: function(data) {
                                const modal = $('#modal_detail');
                                modal.html(data);
                                modal.modal('show');

                            },
                            error: function(xhr) {
                                console.error(xhr);
                            }
                        });
                    });
                } else if (activeTab === "#modifier-sales-nobd") {
                    if ($.fn.dataTable.isDataTable('#modifier-sales')) {
                        $('#modifier-sales').DataTable().destroy();
                    }

                    var modifierSales = $('#modifier-sales').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: '{{ route('report/sales/getModifierSales') }}', // Make sure this URL matches your Laravel route
                            type: 'GET',
                            data: {
                                date: date,
                                outlet: outlet
                            },
                        },
                        columns: [{
                                data: 'name',
                                name: 'name'
                            },
                            {
                                data: 'quantity_sold',
                                name: 'quantity_sold'
                            },
                            {
                                data: 'gross_sales',
                                name: 'gross_sales',
                            },
                            {
                                data: 'discounts',
                                name: 'discounts',
                            },
                            {
                                data: 'net_sales',
                                name: 'net_sales',
                            },
                        ],
                        paging: false, // Menghilangkan pagination
                        searching: false, // Menghilangkan search bar
                        ordering: true,
                        scrollX: true,
                        scrollCollapse: true,
                        scrollY: 500,
                        dom: 'Bfrtip',
                        buttons: [{
                            extend: 'excelHtml5',
                            text: '<span class="mdi mdi-file-excel"></span> Export Excel',
                            className: 'btn btn-modern btn-excel',
                            title: 'Category Sales Report', // judul di file
                            filename: 'category_sales_report', // nama file
                            sheetName: 'Category Sales', // nama sheet (Excel)
                            exportOptions: {
                                columns: ':visible'
                            } // misal kolom ke 0,1,3,5
                        }, ],
                        fixedColumns: {
                            start: 1,
                        },
                        columnDefs: [{
                                targets: 0,
                                width: '200px'
                            } // Menetapkan lebar kolom pertama menjadi 200px
                        ],
                        createdRow: function(row, data) {
                            $(row).addClass(data[6] ? 'modifier-parent-row' : 'modifier-child-row');
                        },
                        initComplete: function(setting, json) {
                            $('.dt-scroll-body table thead').remove();
                            $('.dt-scroll-body table tfoot').remove();
                        },
                        footerCallback: function(row, data, start, end, display) {
                            var api = this.api();

                            var totalItemSold = 0;
                            var totalGrossSales = 0;
                            var totalDiscounts = 0;
                            var totalNetSales = 0;
                            data.forEach(function(item, index) {
                                console.log(item)
                                if (data[index][6]) {
                                    totalItemSold += item[2];
                                    totalGrossSales += item[3];
                                    totalDiscounts += item[4];
                                    totalNetSales += item[5];
                                }
                            })

                            // Menampilkan total di footer
                            $(api.column(1).footer()).html(totalItemSold);
                            $(api.column(2).footer()).html(formatRupiah(totalGrossSales.toString(), "Rp. "));
                            $(api.column(3).footer()).html(formatRupiah(totalDiscounts.toString(), "Rp. "));
                            $(api.column(4).footer()).html(formatRupiah(totalNetSales.toString(), "Rp. "));
                        }
                    });

                    // Pastikan untuk menambahkan elemen <tfoot> di HTML Anda
                    $('#modifier-sales tfoot').append(`
                        <tr>
                            <th colspan="1">Total</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    `);
                } else if (activeTab === "#discount-nobd") {
                    if ($.fn.dataTable.isDataTable('#discount-sales')) {
                        $('#discount-sales').DataTable().destroy();
                    }

                    var discountSales = $('#discount-sales').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: '{{ route('report/sales/getDiscountSales') }}', // Make sure this URL matches your Laravel route
                            type: 'GET',
                            data: {
                                date: date,
                                outlet: outlet
                            },
                        },
                        columns: [{
                                data: 'name',
                                name: 'name'
                            },
                            {
                                data: 'discount_amount',
                                name: 'discount_amount'
                            },
                            {
                                data: 'count',
                                name: 'count',
                            },
                            {
                                data: 'discount_total',
                                name: 'discount_total',
                            },
                        ],
                        paging: false, // Menghilangkan pagination
                        searching: true, // Menghilangkan search bar
                        ordering: true,
                        scrollX: true,
                        scrollCollapse: true,
                        scrollY: 500,
                        fixedColumns: {
                            start: 1,
                        },
                        dom: 'Bfrtip',
                        buttons: [{
                            extend: 'excelHtml5',
                            text: '<span class="mdi mdi-file-excel"></span> Export Excel',
                            className: 'btn btn-modern btn-excel',
                            title: 'Category Sales Report', // judul di file
                            filename: 'category_sales_report', // nama file
                            sheetName: 'Category Sales', // nama sheet (Excel)
                            exportOptions: {
                                columns: ':visible'
                            } // misal kolom ke 0,1,3,5
                        }, ],
                        columnDefs: [{
                                targets: 0,
                                width: '200px'
                            } // Menetapkan lebar kolom pertama menjadi 200px
                        ],
                        initComplete: function(setting, json) {
                            $('.dt-scroll-body table thead').remove();
                            $('.dt-scroll-body table tfoot').remove();
                        },
                        footerCallback: function(row, data, start, end, display) {
                            var api = this.api();

                            var count = 0;
                            var totalDiscounts = 0;
                            data.forEach(function(item, index) {
                                count += item.count;
                                totalDiscounts += item.total_discount;
                            });

                            // Menampilkan total di footer
                            $(api.column(2).footer()).html(count);
                            $(api.column(3).footer()).html(formatRupiah(totalDiscounts.toString(), "Rp. "));
                        }
                    });

                    // Pastikan untuk menambahkan elemen <tfoot> di HTML Anda
                    $('#discount-sales tfoot').append(`
                        <tr>
                            <th colspan="2">Total</th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    `);
                } else if (activeTab === "#taxes-nobd") {
                    if ($.fn.dataTable.isDataTable('#tax-sales')) {
                        $('#tax-sales').DataTable().destroy();
                    }

                    var modifierSales = $('#tax-sales').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: '{{ route('report/sales/getTaxSales') }}', // Make sure this URL matches your Laravel route
                            type: 'GET',
                            data: {
                                date: date,
                                outlet: outlet
                            },
                        },
                        columns: [{
                                data: 'name',
                                name: 'name'
                            },
                            {
                                data: 'tax_rate',
                                name: 'tax_rate'
                            },
                            {
                                data: 'taxable_amount',
                                name: 'taxable_amount',
                            },
                            {
                                data: 'tax_collected',
                                name: 'tax_collected',
                            },
                        ],
                        paging: false, // Menghilangkan pagination
                        searching: false, // Menghilangkan search bar
                        ordering: true,
                        scrollX: true,
                        scrollCollapse: true,
                        scrollY: 500,
                        fixedColumns: {
                            start: 1,
                        },
                        dom: 'Bfrtip',
                        buttons: [{
                            extend: 'excelHtml5',
                            text: '<span class="mdi mdi-file-excel"></span> Export Excel',
                            className: 'btn btn-modern btn-excel',
                            title: 'Category Sales Report', // judul di file
                            filename: 'category_sales_report', // nama file
                            sheetName: 'Category Sales', // nama sheet (Excel)
                            exportOptions: {
                                columns: ':visible'
                            } // misal kolom ke 0,1,3,5
                        }, ],
                        columnDefs: [{
                                targets: 0,
                                width: '200px'
                            } // Menetapkan lebar kolom pertama menjadi 200px
                        ],
                        initComplete: function(setting, json) {
                            $('.dt-scroll-body table thead').remove();
                            $('.dt-scroll-body table tfoot').remove();
                        },
                        footerCallback: function(row, data, start, end, display) {
                            var api = this.api();

                            var totalTaxableAmount = 0;
                            var totalTaxCollected = 0;
                            console.log(data);
                            data.forEach(function(item, index) {
                                totalTaxableAmount += getAmount(item.taxable_amount);
                                totalTaxCollected += getAmount(item.tax_collected);
                            });

                            // Menampilkan total di footer
                            $(api.column(2).footer()).html(formatRupiah(totalTaxableAmount.toString(), "Rp. "));
                            $(api.column(3).footer()).html(formatRupiah(totalTaxCollected.toString(), "Rp. "));
                        }
                    });

                    // Pastikan untuk menambahkan elemen <tfoot> di HTML Anda
                    $('#tax-sales tfoot').append(`
                        <tr>
                            <th colspan="2">Total</th>
                            <th></th>
                            <th></th>
                        </tr>
                    `);

                } else if (activeTab === "#collected-by-nobd") {
                    if ($.fn.dataTable.isDataTable('#collected-by-sales')) {
                        $('#collected-by-sales').DataTable().destroy();
                    }

                    var collectedBySales = $('#collected-by-sales').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: '{{ route('report/sales/getCollectedBySales') }}', // Make sure this URL matches your Laravel route
                            type: 'GET',
                            data: {
                                date: date,
                                outlet: outlet
                            },
                        },
                        columns: [{
                                data: 'name',
                                name: 'name'
                            },
                            {
                                data: 'title',
                                name: 'title'
                            },
                            {
                                data: 'number_of_transaction',
                                name: 'number_of_transaction',
                            },
                            {
                                data: 'total_collected',
                                name: 'total_collected',
                            },
                        ],
                        paging: false, // Menghilangkan pagination
                        searching: false, // Menghilangkan search bar
                        ordering: true,
                        scrollX: true,
                        scrollCollapse: true,
                        scrollY: 500,
                        fixedColumns: {
                            start: 1,
                        },
                        dom: 'Bfrtip',
                        buttons: [{
                            extend: 'excelHtml5',
                            text: '<span class="mdi mdi-file-excel"></span> Export Excel',
                            className: 'btn btn-modern btn-excel',
                            title: 'Category Sales Report', // judul di file
                            filename: 'category_sales_report', // nama file
                            sheetName: 'Category Sales', // nama sheet (Excel)
                            exportOptions: {
                                columns: ':visible'
                            } // misal kolom ke 0,1,3,5
                        }, ],
                        columnDefs: [{
                                targets: 0,
                                width: '200px'
                            } // Menetapkan lebar kolom pertama menjadi 200px
                        ],
                        initComplete: function(setting, json) {
                            $('.dt-scroll-body table thead').remove();
                            $('.dt-scroll-body table tfoot').remove();
                        },
                        footerCallback: function(row, data, start, end, display) {
                            var api = this.api();

                            var totalNumberTranasction = 0;
                            var totalCollected = 0;
                            data.forEach(function(item, index) {
                                totalNumberTranasction += item.number_of_transaction;
                                totalCollected += getAmount(item.total_collected);
                            });

                            // Menampilkan total di footer
                            $(api.column(2).footer()).html(totalNumberTranasction);
                            $(api.column(3).footer()).html(formatRupiah(totalCollected.toString(), "Rp. "));
                        }
                    });

                    // Pastikan untuk menambahkan elemen <tfoot> di HTML Anda
                    $('#collected-by-sales tfoot').append(`
                        <tr>
                            <th colspan="2">Total</th>
                            <th></th>
                            <th></th>
                        </tr>
                    `);

                }
                // Tambahkan logika untuk tab lainnya
            }

            function isRangeMoreThan31Days(rangeStr) {
                const [startStr, endStr] = rangeStr.split(' - ');

                // parse "YYYY/MM/DD"
                const [sy, sm, sd] = startStr.split('/').map(Number);
                const [ey, em, ed] = endStr.split('/').map(Number);

                const startDate = new Date(sy, sm - 1, sd);
                const endDate   = new Date(ey, em - 1, ed);

                const diffMs   = endDate - startDate;
                const diffDays = diffMs / (1000 * 60 * 60 * 24) + 1; // +1 kalau mau hitung inklusif

                return diffDays > 31;
            }


            $(document).ready(function() {
                $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
                    checkActiveTab();
                });

                checkActiveTab();
                var startDate = moment().startOf('day');
                var endDate = moment().endOf('day');

                $('#date_range_transaction').daterangepicker({
                    startDate: startDate,
                    endDate: endDate,
                    // minDate: moment(),
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                            'month').endOf('month')]
                    },
                    "linkedCalendars": false,
                    "autoUpdateInput": false,
                    "showCustomRangeLabel": true,
                    // "startDate": "12/30/2024",
                    // "endDate": "01/05/2025",
                    "drops": "auto",
                    "buttonClasses": "btn btn-primary"
                }, function(start, end, label) {
                    $('#date_range_transaction').val(start.format('YYYY/MM/DD') + ' - ' + end.format(
                        'YYYY/MM/DD'));
                    console.log('New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format(
                        'YYYY-MM-DD') + ' (predefined range: ' + label + ')');

                    startDate = start;
                    endDate = end;

                    // Trigger refresh DataTable setelah memilih rentang tanggal
                    checkActiveTab();
                });

                // Set initial value for the input field
                $('#date_range_transaction').val(startDate.format('YYYY/MM/DD') + ' - ' + endDate.format('YYYY/MM/DD'));

                outlet = $('#filter-outlet').val();
                date = $('#date_range_transaction').val();

                // Fungsi untuk mengubah tanggal
                $('#prevDate').on('click', function() {
                    startDate.subtract(1, 'days');
                    endDate.subtract(1, 'days');

                    console.log(startDate);
                    console.log(endDate);
                    $('#date_range_transaction').data('daterangepicker').setStartDate(startDate);
                    $('#date_range_transaction').data('daterangepicker').setEndDate(endDate);

                    $('#date_range_transaction').val(startDate.format('YYYY/MM/DD') + ' - ' + endDate.format(
                        'YYYY/MM/DD'));

                    checkActiveTab();
                });

                $('#nextDate').on('click', function() {
                    startDate.add(1, 'days');
                    endDate.add(1, 'days');

                    console.log(startDate)
                    console.log(endDate)
                    $('#date_range_transaction').data('daterangepicker').setStartDate(startDate);
                    $('#date_range_transaction').data('daterangepicker').setEndDate(endDate);

                    $('#date_range_transaction').val(startDate.format('YYYY/MM/DD') + ' - ' + endDate.format(
                        'YYYY/MM/DD'));
                    checkActiveTab()
                });

                $('.ranges li').addClass('btn btn-primary w-75 ms-3 mt-2');

                // Event untuk mengosongkan rentang saat daterangepicker dibuka
                $('#date_range_transaction').on('show.daterangepicker', function(ev, picker) {
                    picker.setStartDate(moment().startOf(
                        'day')); // Set start date ke hari ini atau tanggal lain
                    picker.setEndDate(moment().startOf('day')); // Set end date ke hari ini atau tanggal lain
                });

                $('a[data-bs-toggle="pill"]').off().on('shown.bs.tab', function(e) {
                    checkActiveTab();
                });

                $('#filter-outlet').on('change', function() {
                    checkActiveTab();
                });

                $(document).on('click', '#btn-export-sales-summary', function() {
                    // cukup redirect GET agar memicu download
                    const url = "{{ route('report/sales/exportSalesSummary') }}";
                    const params = new URLSearchParams({
                        outlet: outlet,
                        date: date
                    });
                    window.location = url + '?' + params.toString();
                });

            });
        </script>
    @endpush
@endsection
