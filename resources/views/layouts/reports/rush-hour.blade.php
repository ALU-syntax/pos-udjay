@extends('layouts.app')

@section('content')
    <style>
        .rush-hour-page {
            text-align: left;
        }

        .rush-hour-header {
            margin-bottom: 18px;
        }

        .rush-hour-page .page-title {
            color: #1f2937;
            font-size: 26px;
            font-weight: 700;
            line-height: 1.25;
            margin-bottom: 6px;
        }

        .rush-hour-page .page-description {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 0;
            max-width: 960px;
        }

        .rush-hour-filter-card {
            border: 1px solid #edf0f4;
            border-radius: 8px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
        }

        .rush-hour-filter-card .card-body {
            padding: 16px;
        }

        .rush-hour-filter-grid {
            align-items: end;
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(180px, 1fr) minmax(160px, .85fr) minmax(180px, 1fr) max-content;
        }

        .rush-hour-filter-label {
            color: #495057;
            display: block;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.2;
            margin-bottom: 6px;
        }

        .rush-hour-filter-card .select2-container {
            width: 100% !important;
        }

        .rush-hour-filter-control .form-control,
        .rush-hour-filter-control .input-group-text {
            border-color: #e5e7eb;
            min-height: 36px;
        }

        .rush-hour-filter-control .form-control {
            color: #374151;
            font-size: 13px;
        }

        .rush-hour-filter-control .input-group-text {
            background: #f9fafb;
            color: #6b7280;
        }

        .rush-hour-filter-card .select2-container--default .select2-selection--multiple {
            border-color: #e5e7eb;
            border-radius: 6px;
            height: 36px;
            min-height: 36px;
            overflow: hidden;
            padding: 1px 5px;
        }

        .rush-hour-filter-card .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #d03c3c;
        }

        .rush-hour-filter-card .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            height: 32px;
            overflow: hidden;
            padding: 0;
        }

        .rush-hour-filter-card .select2-container--default .select2-selection--multiple .select2-selection__choice {
            align-items: center;
            background: #f3f4f6;
            border-color: #e5e7eb;
            border-radius: 6px;
            color: #374151;
            display: inline-flex;
            font-size: 12px;
            line-height: 22px;
            margin-bottom: 0;
            margin-top: 4px;
            max-width: 120px;
            overflow: hidden;
            padding-bottom: 0;
            padding-top: 0;
            text-overflow: ellipsis;
            vertical-align: top;
            white-space: nowrap;
        }

        .rush-hour-filter-card .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .rush-hour-filter-card .select2-container--default .select2-selection--multiple .rush-select2-overflow-count {
            background: #eef6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
            display: inline-flex !important;
            font-weight: 700;
            padding-right: 8px;
        }

        .rush-hour-filter-card .select2-container--default .select2-selection--multiple .select2-search__field {
            color: #6b7280;
            font-size: 13px;
            margin-top: 6px;
        }

        .rush-hour-actions {
            align-items: end;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .rush-hour-actions .btn {
            align-items: center;
            border-radius: 8px !important;
            display: inline-flex;
            font-size: 13px;
            font-weight: 600;
            height: 36px;
            justify-content: center;
            padding: 7px 12px;
            white-space: nowrap;
        }

        .btn-rush-primary {
            background-color: #d03c3c !important;
            border-color: #d03c3c !important;
            color: #fff !important;
            font-weight: 600;
        }

        .btn-rush-success {
            background-color: #198754 !important;
            border-color: #198754 !important;
            color: #fff !important;
            font-weight: 600;
        }

        .btn-rush-outline {
            background: #fff !important;
            border-color: #d8dee8 !important;
            color: #4b5563 !important;
        }

        .btn-rush-outline:hover {
            background: #f8fafc !important;
            color: #1f2937 !important;
        }

        .rush-hour-dashboard-row {
            align-items: stretch;
            row-gap: 16px;
        }

        .rush-hour-dashboard-row > [class*="col-"] {
            display: flex;
        }

        .rush-summary-container {
            display: grid;
            flex: 1 1 auto;
            gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            grid-template-rows: repeat(3, minmax(112px, 1fr));
            height: 100%;
            width: 100%;
        }

        .rush-summary-card {
            background: #ffffff;
            border: 1px solid #edf0f4;
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .055);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            min-height: 0;
            overflow: hidden;
            padding: 14px;
            position: relative;
            transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
        }

        .rush-summary-card:hover {
            border-color: #dbe3ee;
            box-shadow: 0 16px 32px rgba(15, 23, 42, .08);
            transform: translateY(-1px);
        }

        .rush-summary-card::before {
            background: var(--summary-accent, #d03c3c);
            content: "";
            height: 3px;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
        }

        .rush-summary-top {
            align-items: center;
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }

        .rush-summary-icon {
            align-items: center;
            background: var(--summary-soft, #fff1f1);
            border-radius: 8px;
            color: var(--summary-accent, #d03c3c);
            display: inline-flex;
            flex: 0 0 34px;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .rush-summary-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.25;
            margin: 0;
        }

        .rush-summary-value {
            color: #111827;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0;
            line-height: 1.25;
            margin: auto 0;
            overflow-wrap: anywhere;
            text-align: left;
            width: 100%;
        }

        .rush-summary-card.is-loading {
            opacity: .62;
        }

        .rush-payment-panel {
            background: #ffffff;
            border: 1px solid #edf0f4;
            border-radius: 8px;
            flex: 1 1 auto;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .055);
            height: 100%;
            min-height: 100%;
            overflow: hidden;
            padding: 16px;
        }

        .rush-payment-panel.is-loading {
            opacity: .62;
        }

        .rush-payment-header {
            align-items: flex-start;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .rush-payment-title {
            color: #111827;
            font-size: 15px;
            font-weight: 800;
            line-height: 1.25;
            margin: 0;
        }

        .rush-payment-subtitle {
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
            margin: 3px 0 0;
        }

        .rush-payment-header-icon {
            align-items: center;
            background: #eef6ff;
            border-radius: 8px;
            color: #2563eb;
            display: inline-flex;
            flex: 0 0 36px;
            height: 36px;
            justify-content: center;
            width: 36px;
        }

        .rush-payment-chart {
            min-height: 270px;
        }

        .rush-payment-list {
            border-top: 1px solid #edf0f4;
            display: grid;
            gap: 10px 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding-top: 12px;
        }

        .rush-payment-item {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #edf0f4;
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-template-columns: 34px minmax(0, 1fr);
            min-width: 0;
            padding: 8px;
        }

        .rush-payment-body {
            min-width: 0;
        }

        .rush-payment-title-row {
            align-items: center;
            display: flex;
            gap: 8px;
            justify-content: space-between;
            min-width: 0;
        }

        .rush-payment-icon {
            align-items: center;
            background: var(--payment-color, #d03c3c);
            border-radius: 8px;
            color: #ffffff;
            display: inline-flex;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .rush-payment-name {
            color: #1f2937;
            flex: 1 1 auto;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.25;
            margin: 0;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .rush-payment-value {
            color: #64748b;
            font-size: 12px;
            line-height: 1.25;
            margin: 5px 0 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .rush-payment-percent {
            background: #f8fafc;
            border: 1px solid #edf0f4;
            border-radius: 999px;
            color: #111827;
            flex: 0 0 auto;
            font-size: 12px;
            font-weight: 800;
            min-width: 58px;
            padding: 4px 8px;
            text-align: center;
        }

        .rush-payment-empty {
            align-items: center;
            border: 1px dashed #d8dee8;
            border-radius: 8px;
            color: #64748b;
            display: flex;
            font-size: 13px;
            font-weight: 600;
            grid-column: 1 / -1;
            justify-content: center;
            min-height: 56px;
        }

        .rush-hourly-panel {
            background: #ffffff;
            border: 1px solid #edf0f4;
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .055);
            overflow: hidden;
            padding: 16px;
        }

        .rush-hourly-panel.is-loading {
            opacity: .62;
        }

        .rush-hourly-header {
            align-items: flex-start;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .rush-hourly-title {
            color: #111827;
            font-size: 16px;
            font-weight: 800;
            line-height: 1.25;
            margin: 0;
        }

        .rush-hourly-subtitle {
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
            margin: 4px 0 0;
        }

        .rush-hourly-legend {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .rush-hourly-legend-item {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #edf0f4;
            border-radius: 999px;
            color: #475569;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 6px;
            line-height: 1;
            padding: 6px 9px;
        }

        .rush-hourly-legend-dot {
            background: var(--legend-color, #dc2626);
            border-radius: 999px;
            display: inline-flex;
            height: 9px;
            width: 9px;
        }

        .rush-hourly-legend-dynamic {
            display: contents;
        }

        .rush-hourly-header-icon {
            align-items: center;
            background: #fff7ed;
            border-radius: 8px;
            color: #f97316;
            display: inline-flex;
            flex: 0 0 36px;
            height: 36px;
            justify-content: center;
            width: 36px;
        }

        .rush-hourly-chart {
            min-height: 360px;
        }

        .rush-hour-tooltip {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, .18);
            min-width: 270px;
            overflow: hidden;
            padding: 12px;
        }

        .rush-hour-tooltip-head {
            align-items: flex-start;
            display: flex;
            gap: 12px;
            justify-content: space-between;
        }

        .rush-hour-tooltip-hour {
            color: #111827;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.25;
            margin: 0;
        }

        .rush-hour-tooltip-total {
            color: #dc2626;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.25;
            margin: 0;
            text-align: right;
            white-space: nowrap;
        }

        .rush-hour-tooltip-line {
            background: #edf0f4;
            height: 1px;
            margin: 10px 0;
        }

        .rush-hour-tooltip-list {
            display: grid;
            gap: 8px;
        }

        .rush-hour-tooltip-item {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
        }

        .rush-hour-tooltip-label {
            align-items: center;
            color: #64748b;
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            gap: 7px;
            line-height: 1.25;
            min-width: 0;
        }

        .rush-hour-tooltip-label i {
            color: var(--tooltip-color, #dc2626);
            width: 14px;
        }

        .rush-hour-tooltip-value {
            color: #111827;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.25;
            text-align: right;
            white-space: nowrap;
        }

        .rush-detail-panel {
            background: #ffffff;
            border: 1px solid #edf0f4;
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .055);
            overflow: hidden;
            padding: 16px;
        }

        .rush-detail-panel.is-loading {
            opacity: .62;
        }

        .rush-detail-header {
            align-items: flex-start;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .rush-detail-title {
            color: #111827;
            font-size: 16px;
            font-weight: 800;
            line-height: 1.25;
            margin: 0;
        }

        .rush-detail-subtitle {
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
            margin: 4px 0 0;
        }

        .rush-detail-header-icon {
            align-items: center;
            background: #eef6ff;
            border-radius: 8px;
            color: #2563eb;
            display: inline-flex;
            flex: 0 0 36px;
            height: 36px;
            justify-content: center;
            width: 36px;
        }

        .rush-detail-table-wrap {
            border: 1px solid #edf0f4;
            border-radius: 8px;
            overflow-x: auto;
        }

        .rush-detail-table {
            margin-bottom: 0;
            min-width: 980px;
        }

        .rush-detail-table thead th {
            background: #f8fafc;
            border-bottom: 1px solid #edf0f4;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .02em;
            padding: 11px 12px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .rush-detail-table tbody td {
            border-color: #edf0f4;
            color: #111827;
            font-size: 12px;
            padding: 11px 12px;
            vertical-align: middle;
        }

        .rush-detail-row {
            cursor: pointer;
        }

        .rush-detail-row:hover td {
            background: #f8fafc;
        }

        .rush-detail-row.is-open td {
            background: #fbfdff;
        }

        .rush-detail-time {
            align-items: center;
            display: inline-flex;
            font-weight: 800;
            gap: 8px;
            white-space: nowrap;
        }

        .rush-detail-chevron {
            color: #94a3b8;
            font-size: 11px;
            transition: transform .15s ease, color .15s ease;
            width: 12px;
        }

        .rush-detail-row.is-open .rush-detail-chevron {
            color: #2563eb;
            transform: rotate(90deg);
        }

        .rush-detail-tx {
            font-weight: 800;
            text-align: center;
            white-space: nowrap;
        }

        .rush-detail-amount {
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
        }

        .rush-detail-expand-cell {
            background: #fbfdff;
            padding: 12px !important;
        }

        .rush-detail-outlet-wrap {
            border: 1px solid #edf0f4;
            border-radius: 8px;
            overflow-x: auto;
        }

        .rush-detail-outlet-table {
            background: #ffffff;
            margin-bottom: 0;
            min-width: 920px;
        }

        .rush-detail-outlet-table thead th {
            background: #ffffff;
            border-bottom: 1px solid #edf0f4;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            padding: 10px 12px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .rush-detail-outlet-table tbody td {
            border-color: #edf0f4;
            color: #111827;
            font-size: 12px;
            padding: 10px 12px;
            vertical-align: middle;
        }

        .rush-detail-outlet-name {
            color: #111827;
            font-weight: 800;
            white-space: nowrap;
        }

        .rush-detail-empty {
            color: #64748b !important;
            font-weight: 700;
            height: 64px;
            text-align: center;
        }

        @media (max-width: 1199.98px) {
            .rush-hour-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .rush-hour-actions {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 767.98px) {
            .rush-hour-filter-grid {
                grid-template-columns: 1fr;
            }

            .rush-summary-container {
                grid-template-columns: 1fr;
                grid-template-rows: none;
            }

            .rush-hour-actions {
                flex-direction: column;
            }

            .rush-hour-actions .btn {
                width: 100%;
            }

            .rush-payment-list {
                grid-template-columns: 1fr;
            }

            .rush-hour-tooltip {
                min-width: 240px;
            }
        }
    </style>

    <div class="main-content rush-hour-page">
        <div class="rush-hour-header">
            <h1 class="page-title">Rush Hour</h1>
            <p class="page-description">
                Menganalisis kinerja penjualan per jam, tren pembayaran, diskon, pengembalian dana, pajak, tip,
                dan jumlah yang terkumpul di berbagai gerai.
            </p>
        </div>

        <div class="card rush-hour-filter-card">
            <div class="card-body">
                <form id="rushHourFilterForm" action="{{ route('report/rush-hour') }}" method="GET">
                    <input type="hidden" id="filter-from" name="from" value="{{ $selectedFrom }}">
                    <input type="hidden" id="filter-to" name="to" value="{{ $selectedTo }}">

                    <div class="rush-hour-filter-grid">
                        <div class="rush-hour-filter-control">
                            <label for="date_range_rush_hour" class="rush-hour-filter-label">Date Range</label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="date_range_rush_hour" class="form-control form-control-sm" readonly>
                                <span class="input-group-text">
                                    <i class="fas fa-calendar-alt"></i>
                                </span>
                            </div>
                        </div>

                        <div class="rush-hour-filter-control">
                            <label for="filter-outlet" class="rush-hour-filter-label">Outlet</label>
                            <select id="filter-outlet" name="outlet_id[]" class="form-control form-control-sm select2" multiple
                                data-placeholder="Semua outlet">
                                @foreach ($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" @selected(in_array($outlet->id, $selectedOutletIds))>
                                        {{ $outlet->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rush-hour-filter-control">
                            <label for="filter-payment-method" class="rush-hour-filter-label">Payment Method</label>
                            <select id="filter-payment-method" name="payment_method[]" class="form-control form-control-sm select2" multiple
                                data-placeholder="Semua payment method">
                                @foreach ($paymentMethods as $paymentMethod)
                                    <option value="{{ $paymentMethod->id }}" @selected(in_array($paymentMethod->id, $selectedPaymentMethodIds))>
                                        {{ $paymentMethod->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rush-hour-actions">
                            <a href="{{ route('report/rush-hour') }}" class="btn btn-rush-outline">
                                <i class="fas fa-undo me-1"></i>
                                Reset
                            </a>
                            <button type="submit" class="btn btn-rush-primary">
                                <i class="fas fa-filter me-1"></i>
                                Apply Filter
                            </button>
                            <button type="button" id="btnExportRushHour" class="btn btn-rush-success">
                                <i class="fas fa-file-excel me-1"></i>
                                Export
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mt-4 rush-hour-dashboard-row">
            <div class="col-12 col-xl-7">
                <div class="rush-summary-container">
                    <div class="rush-summary-card" style="--summary-accent:#dc2626;--summary-soft:#fee2e2;">
                        <div class="rush-summary-top">
                            <span class="rush-summary-icon"><i class="fas fa-chart-line"></i></span>
                            <p class="rush-summary-label">Gross Sales</p>
                        </div>
                        <p class="rush-summary-value" data-summary-value="gross_sales">Rp0</p>
                    </div>

                    <div class="rush-summary-card" style="--summary-accent:#2563eb;--summary-soft:#dbeafe;">
                        <div class="rush-summary-top">
                            <span class="rush-summary-icon"><i class="fas fa-cash-register"></i></span>
                            <p class="rush-summary-label">Total Sales</p>
                        </div>
                        <p class="rush-summary-value" data-summary-value="total_sales">Rp0</p>
                    </div>

                    <div class="rush-summary-card" style="--summary-accent:#9333ea;--summary-soft:#f3e8ff;">
                        <div class="rush-summary-top">
                            <span class="rush-summary-icon"><i class="fas fa-tags"></i></span>
                            <p class="rush-summary-label">Discount</p>
                        </div>
                        <p class="rush-summary-value" data-summary-value="discount">Rp0</p>
                    </div>

                    <div class="rush-summary-card" style="--summary-accent:#f97316;--summary-soft:#ffedd5;">
                        <div class="rush-summary-top">
                            <span class="rush-summary-icon"><i class="fas fa-undo-alt"></i></span>
                            <p class="rush-summary-label">Refunds</p>
                        </div>
                        <p class="rush-summary-value" data-summary-value="refunds">Rp0</p>
                    </div>

                    <div class="rush-summary-card" style="--summary-accent:#16a34a;--summary-soft:#dcfce7;">
                        <div class="rush-summary-top">
                            <span class="rush-summary-icon"><i class="fas fa-receipt"></i></span>
                            <p class="rush-summary-label">Net Sales</p>
                        </div>
                        <p class="rush-summary-value" data-summary-value="net_sales">Rp0</p>
                    </div>

                    <div class="rush-summary-card" style="--summary-accent:#0891b2;--summary-soft:#cffafe;">
                        <div class="rush-summary-top">
                            <span class="rush-summary-icon"><i class="fas fa-hand-holding-usd"></i></span>
                            <p class="rush-summary-label">Gratuity</p>
                        </div>
                        <p class="rush-summary-value" data-summary-value="gratuity">Rp0</p>
                    </div>

                    <div class="rush-summary-card" style="--summary-accent:#4f46e5;--summary-soft:#e0e7ff;">
                        <div class="rush-summary-top">
                            <span class="rush-summary-icon"><i class="fas fa-percent"></i></span>
                            <p class="rush-summary-label">Tax</p>
                        </div>
                        <p class="rush-summary-value" data-summary-value="tax">Rp0</p>
                    </div>

                    <div class="rush-summary-card" style="--summary-accent:#0f766e;--summary-soft:#ccfbf1;">
                        <div class="rush-summary-top">
                            <span class="rush-summary-icon"><i class="fas fa-wallet"></i></span>
                            <p class="rush-summary-label">Total Collected</p>
                        </div>
                        <p class="rush-summary-value" data-summary-value="total_collected">Rp0</p>
                    </div>

                    <div class="rush-summary-card" style="--summary-accent:#475569;--summary-soft:#e2e8f0;">
                        <div class="rush-summary-top">
                            <span class="rush-summary-icon"><i class="fas fa-calculator"></i></span>
                            <p class="rush-summary-label">Total Amount</p>
                        </div>
                        <p class="rush-summary-value" data-summary-value="total_amount">Rp0</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="rush-payment-panel">
                    <div class="rush-payment-header">
                        <div>
                            <h2 class="rush-payment-title">Payment Composition</h2>
                            <p class="rush-payment-subtitle">Komposisi nominal transaksi per payment method</p>
                        </div>
                        <span class="rush-payment-header-icon">
                            <i class="fas fa-chart-pie"></i>
                        </span>
                    </div>
                    <div id="paymentCompositionChart" class="rush-payment-chart"></div>
                    <div id="paymentCompositionList" class="rush-payment-list"></div>
                </div>
            </div>
        </div>

        <div class="rush-hourly-panel mt-4">
            <div class="rush-hourly-header">
                <div>
                    <h2 class="rush-hourly-title">Hourly Sales Performance</h2>
                    <p class="rush-hourly-subtitle">Performa nominal transaksi dari 00:00 sampai 24:00</p>
                    <div class="rush-hourly-legend">
                        <span class="rush-hourly-legend-item">
                            <span class="rush-hourly-legend-dot" style="--legend-color:#2563eb;"></span>
                            Total Collected
                        </span>
                        <span class="rush-hourly-legend-item">
                            <span class="rush-hourly-legend-dot" style="--legend-color:#16a34a;"></span>
                            Highest
                        </span>
                        <span class="rush-hourly-legend-item">
                            <span class="rush-hourly-legend-dot" style="--legend-color:#dc2626;"></span>
                            Lowest
                        </span>
                        <span id="hourlyOutletLegend" class="rush-hourly-legend-dynamic"></span>
                    </div>
                </div>
                <span class="rush-hourly-header-icon">
                    <i class="fas fa-chart-bar"></i>
                </span>
            </div>
            <div id="hourlySalesPerformanceChart" class="rush-hourly-chart"></div>
        </div>

        <div class="rush-detail-panel mt-4">
            <div class="rush-detail-header">
                <div>
                    <h2 class="rush-detail-title">Hourly Detail</h2>
                    <p class="rush-detail-subtitle">Semua jam operasional dan gerai dalam pilihan saat ini.</p>
                </div>
                <span class="rush-detail-header-icon">
                    <i class="fas fa-table"></i>
                </span>
            </div>
            <div class="rush-detail-table-wrap">
                <table class="table rush-detail-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th class="rush-detail-tx">Tx</th>
                            <th class="rush-detail-amount">Gross Sales</th>
                            <th class="rush-detail-amount">Total Sales</th>
                            <th class="rush-detail-amount">Discount</th>
                            <th class="rush-detail-amount">Refunds</th>
                            <th class="rush-detail-amount">Net Sales</th>
                            <th class="rush-detail-amount">Gratuity</th>
                            <th class="rush-detail-amount">Tax</th>
                        </tr>
                    </thead>
                    <tbody id="hourlyDetailTableBody">
                        <tr>
                            <td colspan="9" class="rush-detail-empty">Memuat data</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            const $dateRange = $('#date_range_rush_hour');
            const $from = $('#filter-from');
            const $to = $('#filter-to');
            const $outlet = $('#filter-outlet');
            const $paymentMethod = $('#filter-payment-method');
            const $btnExportRushHour = $('#btnExportRushHour');
            const outletOptions = @json($outlets->pluck('id')->map(fn ($id) => (string) $id)->values());
            const paymentMethodOptions = @json($paymentMethods->pluck('id')->map(fn ($id) => (string) $id)->values());
            const paymentCompositionColors = [
                '#dc2626',
                '#2563eb',
                '#16a34a',
                '#f97316',
                '#9333ea',
                '#0891b2',
                '#4f46e5',
                '#0f766e',
                '#475569',
                '#ca8a04'
            ];
            const hourlyOutletColors = [
                '#0891b2',
                '#9333ea',
                '#f97316',
                '#0f766e',
                '#ca8a04',
                '#4f46e5',
                '#be185d',
                '#475569',
                '#14b8a6',
                '#7c3aed'
            ];
            const rushSummaryDetails = [
                { key: 'gross_sales', label: 'Gross Sales', icon: 'fa-chart-line', color: '#dc2626' },
                { key: 'total_sales', label: 'Total Sales', icon: 'fa-cash-register', color: '#2563eb' },
                { key: 'discount', label: 'Discount', icon: 'fa-tags', color: '#9333ea' },
                { key: 'refunds', label: 'Refunds', icon: 'fa-undo-alt', color: '#f97316' },
                { key: 'net_sales', label: 'Net Sales', icon: 'fa-receipt', color: '#16a34a' },
                { key: 'gratuity', label: 'Gratuity', icon: 'fa-hand-holding-usd', color: '#0891b2' },
                { key: 'tax', label: 'Tax', icon: 'fa-percent', color: '#4f46e5' },
                { key: 'total_collected', label: 'Total Collected', icon: 'fa-wallet', color: '#0f766e' },
                { key: 'total_amount', label: 'Total Amount', icon: 'fa-calculator', color: '#475569' }
            ];
            const hourlyDetailColumns = [
                { key: 'gross_sales', label: 'Gross Sales' },
                { key: 'total_sales', label: 'Total Sales' },
                { key: 'discount', label: 'Discount' },
                { key: 'refunds', label: 'Refunds' },
                { key: 'net_sales', label: 'Net Sales' },
                { key: 'gratuity', label: 'Gratuity' },
                { key: 'tax', label: 'Tax' }
            ];
            let paymentCompositionChart = null;
            let hourlySalesPerformanceChart = null;

            $('.select2').select2({
                closeOnSelect: false,
                width: '100%'
            });

            function ensureSelectedAllWhenEmpty($select, values) {
                if (($select.val() || []).length || !values.length) {
                    return;
                }

                $select.val(values).trigger('change.select2');
                $select.data('last-value', values);
            }

            function bindMinimumSelection($select, values) {
                ensureSelectedAllWhenEmpty($select, values);
                $select.data('last-value', $select.val() || values);

                $select.on('change', function() {
                    const selectedValues = $select.val() || [];

                    if (selectedValues.length) {
                        $select.data('last-value', selectedValues);
                        return;
                    }

                    const previousValues = $select.data('last-value') || values;
                    const fallbackValues = previousValues.length ? previousValues : values.slice(0, 1);
                    $select.val(fallbackValues).trigger('change.select2');
                    setTimeout(() => collapseSelect2Choices($select), 0);
                });
            }

            bindMinimumSelection($outlet, outletOptions);
            bindMinimumSelection($paymentMethod, paymentMethodOptions);

            function makeOverflowBadge(count) {
                return $('<li>', {
                    class: 'select2-selection__choice rush-select2-overflow-count',
                    title: count + ' item lain dipilih',
                    text: '+' + count
                });
            }

            function collapseSelect2Choices($select) {
                const $container = $select.next('.select2-container');
                const $rendered = $container.find('.select2-selection__rendered');
                const $choices = $rendered.find('.select2-selection__choice').not('.rush-select2-overflow-count');

                $rendered.find('.rush-select2-overflow-count').remove();
                $choices.show();

                if (!$choices.length) {
                    return;
                }

                const firstRowTop = $choices.first()[0].offsetTop;
                let hiddenCount = 0;

                $choices.each(function() {
                    if (this.offsetTop > firstRowTop + 2) {
                        $(this).hide();
                        hiddenCount++;
                    }
                });

                if (!hiddenCount) {
                    return;
                }

                let $visibleChoices = $choices.filter(':visible');

                if (!$visibleChoices.length) {
                    $choices.first().show();
                    $visibleChoices = $choices.first();
                    hiddenCount = $choices.length - 1;
                }

                let $badge = makeOverflowBadge(hiddenCount).insertAfter($visibleChoices.last());

                while ($badge[0].offsetTop > firstRowTop + 2 && $visibleChoices.length > 1) {
                    $badge.remove();
                    $visibleChoices.last().hide();
                    hiddenCount++;
                    $visibleChoices = $choices.filter(':visible');
                    $badge = makeOverflowBadge(hiddenCount).insertAfter($visibleChoices.last());
                }
            }

            function refreshSelect2ChoiceOverflow() {
                $('#filter-outlet, #filter-payment-method').each(function() {
                    collapseSelect2Choices($(this));
                });
            }

            $('#filter-outlet, #filter-payment-method').on(
                'change select2:select select2:unselect select2:clear select2:close',
                function() {
                    setTimeout(() => collapseSelect2Choices($(this)), 0);
                }
            );

            $(window).on('resize', function() {
                clearTimeout(window.rushHourSelect2ResizeTimer);
                window.rushHourSelect2ResizeTimer = setTimeout(refreshSelect2ChoiceOverflow, 120);
            });

            setTimeout(refreshSelect2ChoiceOverflow, 0);
            setTimeout(refreshSelect2ChoiceOverflow, 150);

            function syncDateRange(start, end) {
                $from.val(start.format('YYYY-MM-DD'));
                $to.val(end.format('YYYY-MM-DD'));
                $dateRange.val(start.format('YYYY/MM/DD') + ' - ' + end.format('YYYY/MM/DD'));
            }

            function payloadFromFilters() {
                const payload = {
                    from: $from.val(),
                    to: $to.val()
                };

                const outletIds = $outlet.val();
                if (Array.isArray(outletIds)) {
                    outletIds.forEach(function(id) {
                        payload['outlet_id[]'] = payload['outlet_id[]'] || [];
                        payload['outlet_id[]'].push(id);
                    });
                }

                const paymentMethodIds = $paymentMethod.val();
                if (Array.isArray(paymentMethodIds)) {
                    paymentMethodIds.forEach(function(id) {
                        payload['payment_method[]'] = payload['payment_method[]'] || [];
                        payload['payment_method[]'].push(id);
                    });
                }

                return payload;
            }

            function formatSummaryCurrency(value) {
                const amount = Math.round(Number(value || 0));
                const sign = amount < 0 ? '-' : '';
                const formattedAmount = Math.abs(amount).toLocaleString('id-ID', {
                    maximumFractionDigits: 0
                });

                return sign + 'Rp' + formattedAmount;
            }

            function setSummaryLoading(isLoading) {
                $('.rush-summary-card').toggleClass('is-loading', isLoading);
                $('.rush-payment-panel').toggleClass('is-loading', isLoading);
                $('.rush-hourly-panel').toggleClass('is-loading', isLoading);
                $('.rush-detail-panel').toggleClass('is-loading', isLoading);
            }

            function updateSummaryCards(summary) {
                Object.keys(summary || {}).forEach(function(key) {
                    $('[data-summary-value="' + key + '"]').text(formatSummaryCurrency(summary[key]));
                });
            }

            function escapeHtml(value) {
                return $('<div>').text(value || '').html();
            }

            function paymentIconClass(name) {
                const loweredName = String(name || '').toLowerCase();

                if (loweredName.includes('cash') || loweredName.includes('tunai')) {
                    return 'fa-money-bill-wave';
                }

                if (loweredName.includes('qris') || loweredName.includes('qr')) {
                    return 'fa-qrcode';
                }

                if (
                    loweredName.includes('card') ||
                    loweredName.includes('kartu') ||
                    loweredName.includes('debit') ||
                    loweredName.includes('credit') ||
                    loweredName.includes('visa') ||
                    loweredName.includes('master')
                ) {
                    return 'fa-credit-card';
                }

                if (loweredName.includes('bank') || loweredName.includes('transfer')) {
                    return 'fa-university';
                }

                if (
                    loweredName.includes('wallet') ||
                    loweredName.includes('ewallet') ||
                    loweredName.includes('ovo') ||
                    loweredName.includes('gopay') ||
                    loweredName.includes('go-pay') ||
                    loweredName.includes('dana') ||
                    loweredName.includes('shopee')
                ) {
                    return 'fa-wallet';
                }

                return 'fa-wallet';
            }

            function formatPaymentPercent(value) {
                return Number(value || 0).toLocaleString('id-ID', {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1
                }) + '%';
            }

            function updatePaymentCompositionList(items, colors) {
                const $list = $('#paymentCompositionList');

                if (!items.length) {
                    $list.html('<div class="rush-payment-empty">Belum ada transaksi</div>');
                    return;
                }

                $list.html(items.map(function(item, index) {
                    const color = colors[index % colors.length];
                    const name = escapeHtml(item.name || 'Unknown');

                    return `
                        <div class="rush-payment-item" style="--payment-color:${color};">
                            <span class="rush-payment-icon">
                                <i class="fas ${paymentIconClass(item.name)}"></i>
                            </span>
                            <div class="rush-payment-body">
                                <div class="rush-payment-title-row">
                                    <p class="rush-payment-name" title="${name}">${name}</p>
                                    <span class="rush-payment-percent">${formatPaymentPercent(item.percentage)}</span>
                                </div>
                                <p class="rush-payment-value">${formatSummaryCurrency(item.value)}</p>
                            </div>
                        </div>
                    `;
                }).join(''));
            }

            function renderPaymentComposition(composition) {
                const sourceItems = composition && Array.isArray(composition.items) ? composition.items : [];
                const items = sourceItems.filter(function(item) {
                    return Number(item.value || 0) > 0;
                });
                const hasData = items.length > 0;
                const labels = hasData ? items.map(function(item) {
                    return item.name || 'Unknown';
                }) : ['Belum ada data'];
                const series = hasData ? items.map(function(item) {
                    return Number(item.value || 0);
                }) : [1];
                const colors = hasData ? items.map(function(_, index) {
                    return paymentCompositionColors[index % paymentCompositionColors.length];
                }) : ['#e5e7eb'];
                const total = Number((composition && composition.total) || 0);

                updatePaymentCompositionList(items, colors);

                if (typeof ApexCharts === 'undefined') {
                    $('#paymentCompositionChart').html('<div class="rush-payment-empty">Chart belum siap</div>');
                    return;
                }

                const chartOptions = {
                    chart: {
                        type: 'donut',
                        height: 270,
                        fontFamily: 'Public Sans, sans-serif',
                        toolbar: {
                            show: false
                        },
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 450
                        }
                    },
                    series: series,
                    labels: labels,
                    colors: colors,
                    dataLabels: {
                        enabled: false
                    },
                    legend: {
                        show: false
                    },
                    stroke: {
                        colors: ['#ffffff'],
                        width: 4
                    },
                    tooltip: {
                        y: {
                            formatter: function(value) {
                                return formatSummaryCurrency(hasData ? value : 0);
                            }
                        }
                    },
                    plotOptions: {
                        pie: {
                            expandOnClick: false,
                            donut: {
                                size: '72%',
                                labels: {
                                    show: true,
                                    name: {
                                        show: true,
                                        color: '#64748b',
                                        fontSize: '12px',
                                        fontWeight: 700,
                                        offsetY: -3
                                    },
                                    value: {
                                        show: true,
                                        color: '#111827',
                                        fontSize: '18px',
                                        fontWeight: 800,
                                        offsetY: 3,
                                        formatter: function(value) {
                                            return formatSummaryCurrency(hasData ? value : 0);
                                        }
                                    },
                                    total: {
                                        show: true,
                                        showAlways: true,
                                        label: 'Total',
                                        color: '#64748b',
                                        fontSize: '12px',
                                        fontWeight: 700,
                                        formatter: function() {
                                            return formatSummaryCurrency(total);
                                        }
                                    }
                                }
                            }
                        }
                    },
                    states: {
                        hover: {
                            filter: {
                                type: 'lighten',
                                value: .05
                            }
                        },
                        active: {
                            filter: {
                                type: 'none'
                            }
                        }
                    }
                };

                if (paymentCompositionChart) {
                    paymentCompositionChart.updateOptions(chartOptions, false, true);
                    return;
                }

                paymentCompositionChart = new ApexCharts(
                    document.querySelector('#paymentCompositionChart'),
                    chartOptions
                );
                paymentCompositionChart.render();
            }

            function emptyHourlySummary() {
                const summary = {};

                rushSummaryDetails.forEach(function(item) {
                    summary[item.key] = 0;
                });

                return summary;
            }

            function normalizeHourlyPerformance(hourlyPerformance) {
                const sourceItems = Array.isArray(hourlyPerformance) ? hourlyPerformance : [];
                const itemsByHour = {};

                sourceItems.forEach(function(item) {
                    if (!item || !item.hour) {
                        return;
                    }

                    itemsByHour[item.hour] = item;
                });

                const normalized = [];

                for (let hour = 0; hour <= 24; hour++) {
                    const hourLabel = String(hour).padStart(2, '0') + ':00';
                    const item = itemsByHour[hourLabel] || {};
                    const summary = Object.assign(emptyHourlySummary(), item.summary || {});

                    normalized.push({
                        hour: hourLabel,
                        value: Number(item.value || summary.total_collected || 0),
                        summary: summary
                    });
                }

                return normalized;
            }

            function formatPlainNumber(value) {
                return Number(value || 0).toLocaleString('id-ID', {
                    maximumFractionDigits: 0
                });
            }

            function normalizeHourlyDetail(hourlyDetail) {
                const sourceItems = Array.isArray(hourlyDetail) ? hourlyDetail : [];
                const itemsByHour = {};

                sourceItems.forEach(function(item) {
                    if (!item || !item.hour) {
                        return;
                    }

                    itemsByHour[item.hour] = item;
                });

                const normalized = [];

                for (let hour = 0; hour <= 24; hour++) {
                    const hourLabel = String(hour).padStart(2, '0') + ':00';
                    const item = itemsByHour[hourLabel] || {};
                    const summary = Object.assign(emptyHourlySummary(), item.summary || {});
                    const outlets = Array.isArray(item.outlets) ? item.outlets : [];

                    normalized.push({
                        hour: hourLabel,
                        transaction_count: Number(item.transaction_count || 0),
                        summary: summary,
                        outlets: outlets.map(function(outlet) {
                            return {
                                outlet_id: outlet.outlet_id || null,
                                outlet_name: outlet.outlet_name || 'Outlet',
                                transaction_count: Number(outlet.transaction_count || 0),
                                summary: Object.assign(emptyHourlySummary(), outlet.summary || {})
                            };
                        })
                    });
                }

                return normalized;
            }

            function hourlyDetailAmountCells(summary) {
                const safeSummary = Object.assign(emptyHourlySummary(), summary || {});

                return hourlyDetailColumns.map(function(column) {
                    return `<td class="rush-detail-amount">${formatSummaryCurrency(safeSummary[column.key])}</td>`;
                }).join('');
            }

            function hourlyDetailOutletTable(outlets) {
                const sourceOutlets = Array.isArray(outlets) ? outlets : [];
                const headerCells = hourlyDetailColumns.map(function(column) {
                    return `<th class="rush-detail-amount">${escapeHtml(column.label)}</th>`;
                }).join('');
                const bodyRows = sourceOutlets.length
                    ? sourceOutlets.map(function(outlet) {
                        return `
                            <tr>
                                <td class="rush-detail-outlet-name">${escapeHtml(outlet.outlet_name || 'Outlet')}</td>
                                <td class="rush-detail-tx">${formatPlainNumber(outlet.transaction_count)}</td>
                                ${hourlyDetailAmountCells(outlet.summary)}
                            </tr>
                        `;
                    }).join('')
                    : '<tr><td colspan="9" class="rush-detail-empty">Belum ada outlet</td></tr>';

                return `
                    <div class="rush-detail-outlet-wrap">
                        <table class="table rush-detail-outlet-table">
                            <thead>
                                <tr>
                                    <th>Outlet</th>
                                    <th class="rush-detail-tx">Tx</th>
                                    ${headerCells}
                                </tr>
                            </thead>
                            <tbody>${bodyRows}</tbody>
                        </table>
                    </div>
                `;
            }

            function renderHourlyDetailTable(hourlyDetail) {
                const items = normalizeHourlyDetail(hourlyDetail);
                const $body = $('#hourlyDetailTableBody');

                if (!items.length) {
                    $body.html('<tr><td colspan="9" class="rush-detail-empty">Belum ada data</td></tr>');
                    return;
                }

                $body.html(items.map(function(item, index) {
                    const targetId = 'hourly-detail-' + String(index).padStart(2, '0');

                    return `
                        <tr class="rush-detail-row" data-detail-target="${targetId}" aria-expanded="false">
                            <td>
                                <span class="rush-detail-time">
                                    <i class="fas fa-chevron-right rush-detail-chevron"></i>
                                    ${escapeHtml(item.hour)}
                                </span>
                            </td>
                            <td class="rush-detail-tx">${formatPlainNumber(item.transaction_count)}</td>
                            ${hourlyDetailAmountCells(item.summary)}
                        </tr>
                        <tr id="${targetId}" class="rush-detail-outlet-row d-none">
                            <td colspan="9" class="rush-detail-expand-cell">
                                ${hourlyDetailOutletTable(item.outlets)}
                            </td>
                        </tr>
                    `;
                }).join(''));
            }

            function hourlyTooltipHtml(item) {
                const safeItem = item || {
                    hour: '00:00',
                    value: 0,
                    summary: emptyHourlySummary()
                };
                const summary = Object.assign(emptyHourlySummary(), safeItem.summary || {});

                const listHtml = rushSummaryDetails.map(function(detail) {
                    return `
                        <div class="rush-hour-tooltip-item">
                            <span class="rush-hour-tooltip-label" style="--tooltip-color:${detail.color};">
                                <i class="fas ${detail.icon}"></i>
                                ${escapeHtml(detail.label)}
                            </span>
                            <span class="rush-hour-tooltip-value">${formatSummaryCurrency(summary[detail.key])}</span>
                        </div>
                    `;
                }).join('');

                return `
                    <div class="rush-hour-tooltip">
                        <div class="rush-hour-tooltip-head">
                            <p class="rush-hour-tooltip-hour">${escapeHtml(safeItem.hour)}</p>
                            <p class="rush-hour-tooltip-total">${formatSummaryCurrency(safeItem.value)}</p>
                        </div>
                        <div class="rush-hour-tooltip-line"></div>
                        <div class="rush-hour-tooltip-list">${listHtml}</div>
                    </div>
                `;
            }

            function hourlyExtremaIndexes(items) {
                const valuedItems = items
                    .map(function(item, index) {
                        return {
                            index: index,
                            value: Number(item.value || 0)
                        };
                    })
                    .filter(function(item) {
                        return item.value > 0;
                    });

                if (!valuedItems.length) {
                    return {
                        highestIndex: null,
                        lowestIndex: null
                    };
                }

                const highest = valuedItems.reduce(function(maxItem, item) {
                    return item.value > maxItem.value ? item : maxItem;
                }, valuedItems[0]);
                const lowest = valuedItems.reduce(function(minItem, item) {
                    return item.value < minItem.value ? item : minItem;
                }, valuedItems[0]);
                const fallbackLowest = valuedItems.find(function(item) {
                    return item.index !== highest.index;
                });

                return {
                    highestIndex: highest.index,
                    lowestIndex: highest.index === lowest.index
                        ? (fallbackLowest ? fallbackLowest.index : null)
                        : lowest.index
                };
            }

            function hourlyBarSeriesValue(item, index, extremaIndexes, type) {
                const value = Number(item.value || 0);

                if (!value) {
                    return 0;
                }

                if (type === 'highest') {
                    return index === extremaIndexes.highestIndex ? value : 0;
                }

                if (type === 'lowest') {
                    return index === extremaIndexes.lowestIndex ? value : 0;
                }

                return index !== extremaIndexes.highestIndex && index !== extremaIndexes.lowestIndex ? value : 0;
            }

            function hourlyExtremaAnnotationPoint(items, index, label, color) {
                const item = items[index];

                if (!item || !Number(item.value || 0)) {
                    return null;
                }

                return {
                    x: item.hour,
                    y: Number(item.value || 0),
                    marker: {
                        size: 5,
                        fillColor: '#ffffff',
                        strokeColor: color,
                        strokeWidth: 3
                    },
                    label: {
                        borderColor: color,
                        offsetY: -10,
                        style: {
                            background: color,
                            color: '#ffffff',
                            fontSize: '11px',
                            fontWeight: 800,
                            padding: {
                                left: 7,
                                right: 7,
                                top: 4,
                                bottom: 4
                            }
                        },
                        text: label + ' ' + formatSummaryCurrency(item.value)
                    }
                };
            }

            function hourlyExtremaAnnotations(items, extremaIndexes) {
                return [
                    hourlyExtremaAnnotationPoint(items, extremaIndexes.highestIndex, 'Highest', '#16a34a'),
                    hourlyExtremaAnnotationPoint(items, extremaIndexes.lowestIndex, 'Lowest', '#dc2626')
                ].filter(Boolean);
            }

            function normalizeHourlyOutletPerformance(hourlyOutletPerformance) {
                const sourceItems = Array.isArray(hourlyOutletPerformance) ? hourlyOutletPerformance : [];

                return sourceItems.map(function(outlet, index) {
                    const dataByHour = {};
                    const sourceData = Array.isArray(outlet.data) ? outlet.data : [];

                    sourceData.forEach(function(item) {
                        if (!item || !item.hour) {
                            return;
                        }

                        dataByHour[item.hour] = Number(item.value || 0);
                    });

                    const data = [];

                    for (let hour = 0; hour <= 24; hour++) {
                        const hourLabel = String(hour).padStart(2, '0') + ':00';

                        data.push({
                            x: hourLabel,
                            y: Number(dataByHour[hourLabel] || 0)
                        });
                    }

                    return {
                        id: outlet.outlet_id,
                        name: outlet.outlet_name || 'Outlet',
                        color: hourlyOutletColors[index % hourlyOutletColors.length],
                        data: data
                    };
                });
            }

            function updateHourlyOutletLegend(outlets) {
                const $legend = $('#hourlyOutletLegend');

                if (!outlets.length) {
                    $legend.empty();
                    return;
                }

                $legend.html(outlets.map(function(outlet) {
                    return `
                        <span class="rush-hourly-legend-item">
                            <span class="rush-hourly-legend-dot" style="--legend-color:${outlet.color};"></span>
                            ${escapeHtml(outlet.name)}
                        </span>
                    `;
                }).join(''));
            }

            function renderHourlySalesPerformance(hourlyPerformance, hourlyOutletPerformance) {
                const items = normalizeHourlyPerformance(hourlyPerformance);
                const labels = items.map(function(item) {
                    return item.hour;
                });
                const extremaIndexes = hourlyExtremaIndexes(items);
                const hourlyOutlets = normalizeHourlyOutletPerformance(hourlyOutletPerformance);
                const outletAreaSeries = hourlyOutlets.map(function(outlet) {
                    return {
                        name: outlet.name,
                        type: 'area',
                        data: outlet.data
                    };
                });
                const defaultCollectedSeries = items.map(function(item, index) {
                    return {
                        x: item.hour,
                        y: hourlyBarSeriesValue(item, index, extremaIndexes, 'default')
                    };
                });
                const highestCollectedSeries = items.map(function(item, index) {
                    return {
                        x: item.hour,
                        y: hourlyBarSeriesValue(item, index, extremaIndexes, 'highest')
                    };
                });
                const lowestCollectedSeries = items.map(function(item, index) {
                    return {
                        x: item.hour,
                        y: hourlyBarSeriesValue(item, index, extremaIndexes, 'lowest')
                    };
                });

                updateHourlyOutletLegend(hourlyOutlets);

                if (typeof ApexCharts === 'undefined') {
                    $('#hourlySalesPerformanceChart').html('<div class="rush-payment-empty">Chart belum siap</div>');
                    return;
                }

                const chartOptions = {
                    annotations: {
                        points: hourlyExtremaAnnotations(items, extremaIndexes)
                    },
                    chart: {
                        type: 'line',
                        height: 360,
                        fontFamily: 'Public Sans, sans-serif',
                        stacked: true,
                        stackOnlyBar: true,
                        toolbar: {
                            show: false,
                            tools: {
                                download: false,
                                selection: false,
                                zoom: false,
                                zoomin: false,
                                zoomout: false,
                                pan: false,
                                reset: false
                            }
                        },
                        zoom: {
                            enabled: false,
                            allowMouseWheelZoom: false
                        },
                        selection: {
                            enabled: false
                        },
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 450
                        }
                    },
                    series: [
                        {
                            name: 'Total Collected',
                            type: 'column',
                            data: defaultCollectedSeries
                        },
                        {
                            name: 'Highest',
                            type: 'column',
                            data: highestCollectedSeries
                        },
                        {
                            name: 'Lowest',
                            type: 'column',
                            data: lowestCollectedSeries
                        }
                    ].concat(outletAreaSeries),
                    colors: ['#2563eb', '#16a34a', '#dc2626'].concat(
                        hourlyOutlets.map(function(outlet) {
                            return outlet.color;
                        })
                    ),
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth',
                        width: [0, 0, 0].concat(hourlyOutlets.map(function() {
                            return 3;
                        }))
                    },
                    fill: {
                        opacity: [0.9, 0.9, 0.9].concat(hourlyOutlets.map(function() {
                            return 0.18;
                        })),
                        type: ['solid', 'solid', 'solid'].concat(hourlyOutlets.map(function() {
                            return 'gradient';
                        })),
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.34,
                            opacityTo: 0.05,
                            stops: [0, 100]
                        }
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 4,
                            columnWidth: '48%'
                        }
                    },
                    grid: {
                        borderColor: '#edf0f4',
                        strokeDashArray: 4,
                        xaxis: {
                            lines: {
                                show: false
                            }
                        }
                    },
                    legend: {
                        show: false,
                        horizontalAlign: 'right',
                        markers: {
                            radius: 8
                        },
                        fontSize: '12px',
                        fontWeight: 700,
                        labels: {
                            colors: '#64748b'
                        }
                    },
                    xaxis: {
                        categories: labels,
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        },
                        labels: {
                            rotate: -45,
                            style: {
                                colors: '#64748b',
                                fontSize: '11px',
                                fontWeight: 700
                            }
                        },
                        tooltip: {
                            enabled: false
                        }
                    },
                    yaxis: {
                        labels: {
                            formatter: function(value) {
                                return formatSummaryCurrency(value);
                            },
                            style: {
                                colors: '#64748b',
                                fontSize: '11px',
                                fontWeight: 700
                            }
                        }
                    },
                    markers: {
                        size: [0, 0, 0].concat(hourlyOutlets.map(function() {
                            return 4;
                        })),
                        strokeWidth: 3,
                        hover: {
                            size: 6
                        }
                    },
                    tooltip: {
                        shared: false,
                        intersect: false,
                        custom: function(context) {
                            return hourlyTooltipHtml(items[context.dataPointIndex]);
                        }
                    },
                    states: {
                        hover: {
                            filter: {
                                type: 'lighten',
                                value: .05
                            }
                        },
                        active: {
                            filter: {
                                type: 'none'
                            }
                        }
                    }
                };

                if (hourlySalesPerformanceChart) {
                    hourlySalesPerformanceChart.updateOptions(chartOptions, false, true);
                    return;
                }

                hourlySalesPerformanceChart = new ApexCharts(
                    document.querySelector('#hourlySalesPerformanceChart'),
                    chartOptions
                );
                hourlySalesPerformanceChart.render();
            }

            function loadRushHourSummary() {
                setSummaryLoading(true);

                return $.ajax({
                    url: "{{ route('report/rush-hour/summary') }}",
                    method: 'GET',
                    data: payloadFromFilters()
                }).done(function(resp) {
                    if (resp && resp.ok) {
                        updateSummaryCards(resp.summary);
                        renderPaymentComposition(resp.payment_composition);
                        renderHourlySalesPerformance(resp.hourly_performance, resp.hourly_outlet_performance);
                        renderHourlyDetailTable(resp.hourly_detail);
                        return;
                    }

                    iziToast.error({
                        title: 'Gagal',
                        message: 'Summary Rush Hour gagal dimuat.'
                    });
                }).fail(function(xhr) {
                    let message = 'Summary Rush Hour gagal dimuat.';

                    try {
                        message = JSON.parse(xhr.responseText).message || message;
                    } catch (_) {}

                    iziToast.warning({
                        title: 'Gagal',
                        message: message
                    });
                }).always(function() {
                    setSummaryLoading(false);
                });
            }

            $('#hourlyDetailTableBody').on('click', '.rush-detail-row', function() {
                const $row = $(this);
                const targetId = $row.data('detail-target');
                const $target = $('#' + targetId);
                const isOpen = $row.hasClass('is-open');

                $row.toggleClass('is-open', !isOpen);
                $row.attr('aria-expanded', String(!isOpen));
                $target.toggleClass('d-none', isOpen);
            });

            function triggerDownload(downloadUrl, filename) {
                const link = document.createElement('a');
                link.href = downloadUrl;
                link.download = filename || 'rush_hour.xlsx';
                document.body.appendChild(link);
                link.click();
                link.remove();
            }

            function waitUntilReady(downloadUrl, filename) {
                const timeoutAt = Date.now() + (10 * 60 * 1000);

                const poll = function() {
                    if (Date.now() > timeoutAt) {
                        iziToast.warning({
                            title: 'Timeout',
                            message: 'Export masih diproses. Coba unduh lagi beberapa saat lagi.'
                        });
                        return;
                    }

                    $.ajax({
                        url: downloadUrl,
                        method: 'HEAD',
                        cache: false
                    }).done(function() {
                        iziToast.success({
                            title: 'Selesai',
                            message: 'File Rush Hour siap diunduh.'
                        });
                        triggerDownload(downloadUrl, filename);
                    }).fail(function() {
                        setTimeout(poll, 3000);
                    });
                };

                iziToast.info({
                    title: 'Export',
                    message: 'Export Rush Hour dimulai di background.'
                });

                poll();
            }

            const initialStart = moment($from.val(), 'YYYY-MM-DD');
            const initialEnd = moment($to.val(), 'YYYY-MM-DD');

            $dateRange.daterangepicker({
                startDate: initialStart,
                endDate: initialEnd,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                        'month').endOf('month')]
                },
                linkedCalendars: false,
                autoUpdateInput: false,
                showCustomRangeLabel: true,
                drops: 'auto',
                buttonClasses: 'btn btn-primary'
            }, function(start, end) {
                syncDateRange(start, end);
            });

            syncDateRange(initialStart, initialEnd);

            $('#rushHourFilterForm').on('submit', function(e) {
                e.preventDefault();

                const picker = $dateRange.data('daterangepicker');
                syncDateRange(picker.startDate, picker.endDate);
                loadRushHourSummary();
            });

            loadRushHourSummary();

            $btnExportRushHour.on('click', function(e) {
                e.preventDefault();

                $btnExportRushHour.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span>Memproses'
                );

                $.ajax({
                    url: "{{ route('report/rush-hour/exportRushHour') }}",
                    method: 'POST',
                    data: payloadFromFilters()
                }).done(function(resp) {
                    if (resp && resp.ok && resp.download_url) {
                        const filename = (resp.path || 'rush_hour.xlsx').split('/').pop();
                        waitUntilReady(resp.download_url, filename);
                        return;
                    }

                    iziToast.error({
                        title: 'Gagal',
                        message: (resp && resp.message) || 'Export Rush Hour gagal dimulai.'
                    });
                }).fail(function(xhr) {
                    let message = 'Export Rush Hour gagal.';

                    try {
                        message = JSON.parse(xhr.responseText).message || message;
                    } catch (_) {}

                    iziToast.warning({
                        title: 'Gagal',
                        message: message
                    });
                }).always(function() {
                    $btnExportRushHour.prop('disabled', false).html(
                        '<i class="fas fa-file-excel me-1"></i>Export'
                    );
                });
            });
        });
    </script>
@endpush
