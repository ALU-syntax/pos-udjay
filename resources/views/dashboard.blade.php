@extends('layouts.app')

@push('css')
    <style>
        .dashboard-page {
            --dashboard-accent: #c9363f;
            --dashboard-accent-dark: #a9232d;
            --dashboard-border: #e9edf3;
            --dashboard-muted: #6b7280;
            --dashboard-text: #172033;
            color: var(--dashboard-text);
        }

        .dashboard-page .dashboard-header {
            align-items: flex-end;
            display: flex;
            gap: 24px;
            justify-content: space-between;
            margin-bottom: 22px;
        }

        .dashboard-page .dashboard-eyebrow {
            color: var(--dashboard-accent);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .14em;
            margin-bottom: 7px;
            text-transform: uppercase;
        }

        .dashboard-page .dashboard-title {
            color: var(--dashboard-text);
            font-size: clamp(25px, 3vw, 34px);
            font-weight: 750;
            letter-spacing: -.035em;
            line-height: 1.15;
            margin: 0 0 7px;
        }

        .dashboard-page .dashboard-description {
            color: var(--dashboard-muted);
            font-size: 14px;
            margin: 0;
        }

        .dashboard-page .dashboard-updated {
            align-items: center;
            background: #fff;
            border: 1px solid var(--dashboard-border);
            border-radius: 999px;
            color: #596174;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 12px;
            font-weight: 600;
            gap: 7px;
            padding: 8px 12px;
        }

        .dashboard-page .dashboard-updated i {
            color: #16a34a;
            font-size: 8px;
        }

        .dashboard-page .dashboard-panel,
        .dashboard-page .dashboard-card {
            background: #fff;
            border: 1px solid var(--dashboard-border);
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(20, 30, 55, .055);
        }

        .dashboard-page .dashboard-panel {
            margin-bottom: 20px;
            overflow: hidden;
        }

        .dashboard-page .dashboard-tabs {
            align-items: center;
            border-bottom: 1px solid var(--dashboard-border);
            display: flex;
            gap: 5px;
            margin: 0;
            overflow-x: auto;
            padding: 10px 14px;
            scrollbar-width: none;
        }

        .dashboard-page .dashboard-tabs::-webkit-scrollbar {
            display: none;
        }

        .dashboard-page .dashboard-tabs .nav-link {
            border: 0;
            border-radius: 10px;
            color: #667085;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 14px;
            white-space: nowrap;
        }

        .dashboard-page .dashboard-tabs .nav-link:hover {
            background: #f8f9fb;
            color: var(--dashboard-text);
        }

        .dashboard-page .dashboard-tabs .nav-link.active {
            background: #fcebec;
            color: var(--dashboard-accent-dark);
        }

        .dashboard-page .dashboard-filter-bar {
            align-items: end;
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(260px, 1fr) auto minmax(270px, 360px);
            padding: 16px;
        }

        .dashboard-page .filter-label {
            color: #475467;
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .dashboard-page .filter-hint {
            color: #98a2b3;
            font-size: 11px;
            font-weight: 500;
            margin-left: 5px;
        }

        .dashboard-page .filter-actions {
            display: flex;
            gap: 7px;
        }

        .dashboard-page .filter-actions .btn,
        .dashboard-page .date-navigation .btn {
            align-items: center;
            border-radius: 9px !important;
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            height: 40px;
            justify-content: center;
        }

        .dashboard-page .btn-select-all {
            background: var(--dashboard-accent);
            border-color: var(--dashboard-accent);
            color: #fff;
        }

        .dashboard-page .btn-select-all:hover {
            background: var(--dashboard-accent-dark);
            border-color: var(--dashboard-accent-dark);
            color: #fff;
        }

        .dashboard-page .btn-deselect-all {
            background: #fff;
            border-color: #dfe3ea;
            color: #596174;
        }

        .dashboard-page .btn-deselect-all:hover {
            background: #f7f8fa;
            color: var(--dashboard-text);
        }

        .dashboard-page .date-navigation {
            display: flex;
        }

        .dashboard-page .date-navigation .btn {
            border-color: #dfe3ea;
            color: #667085;
            flex: 0 0 40px;
            padding: 0;
        }

        .dashboard-page .date-navigation .btn:first-child {
            border-radius: 9px 0 0 9px !important;
        }

        .dashboard-page .date-navigation .btn:last-child {
            border-radius: 0 9px 9px 0 !important;
        }

        .dashboard-page .date-navigation .form-control {
            border-color: #dfe3ea;
            border-radius: 0;
            color: #344054;
            font-size: 12px;
            font-weight: 650;
            height: 40px;
            min-width: 190px;
            text-align: center;
        }

        .dashboard-page .select2-container {
            width: 100% !important;
        }

        .dashboard-page .select2-container--default .select2-selection--multiple {
            border-color: #dfe3ea;
            border-radius: 9px;
            min-height: 40px;
            padding: 2px 6px;
        }

        .dashboard-page .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: var(--dashboard-accent);
            box-shadow: 0 0 0 3px rgba(201, 54, 63, .09);
        }

        .dashboard-page .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background: #fcebec;
            border-color: #f5cdd0;
            border-radius: 6px;
            color: #9f2530;
            font-size: 11px;
            font-weight: 700;
            margin-top: 5px;
            padding-bottom: 2px;
            padding-top: 2px;
        }

        .dashboard-page .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            border-right-color: #f5cdd0;
            color: #9f2530;
            padding-bottom: 2px;
        }

        .dashboard-page .metric-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: 16px;
        }

        .dashboard-page .metric-card {
            overflow: hidden;
            padding: 19px;
            position: relative;
        }

        .dashboard-page .metric-card::after {
            background: var(--metric-color);
            border-radius: 99px;
            content: '';
            height: 3px;
            left: 19px;
            opacity: .8;
            position: absolute;
            top: 0;
            width: 35px;
        }

        .dashboard-page .metric-top {
            align-items: center;
            display: flex;
            justify-content: space-between;
            margin-bottom: 22px;
        }

        .dashboard-page .metric-label {
            color: #667085;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .055em;
            margin: 0;
            text-transform: uppercase;
        }

        .dashboard-page .metric-icon {
            align-items: center;
            background: var(--metric-soft);
            border-radius: 11px;
            color: var(--metric-color);
            display: flex;
            height: 40px;
            justify-content: center;
            width: 40px;
        }

        .dashboard-page .metric-value {
            color: var(--dashboard-text);
            font-size: clamp(18px, 2vw, 24px);
            font-weight: 750;
            letter-spacing: -.035em;
            line-height: 1.2;
            margin: 0 0 6px;
            overflow-wrap: anywhere;
        }

        .dashboard-page .metric-caption {
            color: #98a2b3;
            font-size: 11px;
            margin: 0;
        }

        .dashboard-page .chart-card {
            margin-bottom: 20px;
            overflow: hidden;
        }

        .dashboard-page .chart-header {
            align-items: flex-start;
            border-bottom: 1px solid var(--dashboard-border);
            display: flex;
            gap: 18px;
            justify-content: space-between;
            padding: 18px 20px;
        }

        .dashboard-page .section-title {
            color: var(--dashboard-text);
            font-size: 16px;
            font-weight: 750;
            margin: 0 0 4px;
        }

        .dashboard-page .section-description {
            color: #8a94a6;
            font-size: 12px;
            margin: 0;
        }

        .dashboard-page .chart-legend {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 7px 13px;
            justify-content: flex-end;
            max-width: 60%;
        }

        .dashboard-page .legend-item {
            align-items: center;
            color: #667085;
            display: inline-flex;
            font-size: 11px;
            font-weight: 650;
            gap: 6px;
        }

        .dashboard-page .legend-dot {
            border-radius: 50%;
            height: 7px;
            width: 7px;
        }

        .dashboard-page .chart-body {
            padding: 12px 20px 18px;
        }

        .dashboard-page .chart-container {
            height: 365px;
            position: relative;
        }

        .dashboard-page .comparison-heading {
            align-items: flex-end;
            display: flex;
            justify-content: space-between;
            margin: 4px 0 14px;
        }

        .dashboard-page .comparison-count {
            background: #f2f4f7;
            border-radius: 999px;
            color: #667085;
            font-size: 11px;
            font-weight: 700;
            padding: 6px 10px;
        }

        .dashboard-page .outlet-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .dashboard-page .outlet-card {
            overflow: hidden;
        }

        .dashboard-page .outlet-card-header {
            align-items: center;
            background: linear-gradient(135deg, #fff 0%, #fff7f7 100%);
            border-bottom: 1px solid var(--dashboard-border);
            display: flex;
            gap: 11px;
            padding: 16px;
        }

        .dashboard-page .outlet-avatar {
            align-items: center;
            background: var(--dashboard-accent);
            border-radius: 10px;
            color: #fff;
            display: flex;
            flex: 0 0 auto;
            font-size: 12px;
            font-weight: 800;
            height: 38px;
            justify-content: center;
            text-transform: uppercase;
            width: 38px;
        }

        .dashboard-page .outlet-name {
            color: var(--dashboard-text);
            font-size: 14px;
            font-weight: 750;
            margin: 0 0 2px;
        }

        .dashboard-page .outlet-subtitle {
            color: #98a2b3;
            font-size: 10px;
            margin: 0;
            text-transform: uppercase;
        }

        .dashboard-page .outlet-metrics {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding: 6px 16px 14px;
        }

        .dashboard-page .outlet-metric {
            border-bottom: 1px solid #f0f2f5;
            padding: 12px 5px;
        }

        .dashboard-page .outlet-metric:nth-child(odd) {
            border-right: 1px solid #f0f2f5;
            padding-left: 0;
            padding-right: 12px;
        }

        .dashboard-page .outlet-metric:nth-child(even) {
            padding-left: 12px;
            padding-right: 0;
        }

        .dashboard-page .outlet-metric-label {
            color: #98a2b3;
            display: block;
            font-size: 10px;
            margin-bottom: 4px;
        }

        .dashboard-page .outlet-metric-value {
            color: #344054;
            display: block;
            font-size: 12px;
            font-weight: 750;
            overflow-wrap: anywhere;
        }

        .dashboard-page .product-groups {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding: 0 16px 16px;
        }

        .dashboard-page .product-group {
            background: #fafbfc;
            border: 1px solid #f0f2f5;
            border-radius: 11px;
            padding: 11px;
        }

        .dashboard-page .product-group-title {
            align-items: center;
            color: #667085;
            display: flex;
            font-size: 10px;
            font-weight: 800;
            gap: 6px;
            letter-spacing: .04em;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .dashboard-page .product-group-title i {
            font-size: 9px;
        }

        .dashboard-page .product-group-title.top i {
            color: #16a34a;
        }

        .dashboard-page .product-group-title.down i {
            color: #f59e0b;
        }

        .dashboard-page .product-item {
            align-items: center;
            display: flex;
            gap: 7px;
            justify-content: space-between;
            padding: 4px 0;
        }

        .dashboard-page .product-name {
            color: #475467;
            font-size: 10px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dashboard-page .product-qty {
            background: #eef2f6;
            border-radius: 999px;
            color: #475467;
            flex: 0 0 auto;
            font-size: 9px;
            font-weight: 800;
            min-width: 22px;
            padding: 3px 6px;
            text-align: center;
        }

        .dashboard-page .empty-state {
            align-items: center;
            display: flex;
            flex-direction: column;
            grid-column: 1 / -1;
            justify-content: center;
            min-height: 270px;
            padding: 40px 20px;
            text-align: center;
        }

        .dashboard-page .empty-state-icon {
            align-items: center;
            background: #f7f8fa;
            border-radius: 16px;
            color: #98a2b3;
            display: flex;
            font-size: 22px;
            height: 58px;
            justify-content: center;
            margin-bottom: 14px;
            width: 58px;
        }

        .dashboard-page .empty-state h5 {
            color: #344054;
            font-size: 14px;
            font-weight: 750;
            margin-bottom: 5px;
        }

        .dashboard-page .empty-state p {
            color: #98a2b3;
            font-size: 12px;
            margin: 0;
        }

        @media (max-width: 1199.98px) {
            .dashboard-page .dashboard-filter-bar {
                grid-template-columns: minmax(260px, 1fr) auto;
            }

            .dashboard-page .date-filter {
                grid-column: 1 / -1;
            }

            .dashboard-page .outlet-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 991.98px) {
            .dashboard-page .metric-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-page .chart-header {
                display: block;
            }

            .dashboard-page .chart-legend {
                justify-content: flex-start;
                margin-top: 12px;
                max-width: none;
            }
        }

        @media (max-width: 767.98px) {
            .dashboard-page .dashboard-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
            }

            .dashboard-page .dashboard-filter-bar {
                display: flex;
                flex-direction: column;
            }

            .dashboard-page .outlet-filter,
            .dashboard-page .filter-actions,
            .dashboard-page .date-filter {
                width: 100%;
            }

            .dashboard-page .filter-actions .btn {
                flex: 1;
            }

            .dashboard-page .date-navigation .form-control {
                min-width: 0;
            }

            .dashboard-page .date-navigation .form-control {
                flex: 1;
            }

            .dashboard-page .outlet-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 575.98px) {
            .dashboard-page .metric-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .dashboard-page .metric-top {
                margin-bottom: 15px;
            }

            .dashboard-page .chart-container {
                height: 285px;
            }

            .dashboard-page .product-groups {
                grid-template-columns: minmax(0, 1fr);
            }

            .dashboard-page .comparison-heading {
                align-items: flex-start;
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="dashboard-page">
        <header class="dashboard-header">
            <div>
                <div class="dashboard-eyebrow">Business overview</div>
                <h1 class="dashboard-title">Dashboard</h1>
                <p class="dashboard-description">Pantau performa penjualan dan aktivitas seluruh outlet dalam satu tampilan.</p>
            </div>
            <div class="dashboard-updated" title="Data diperbarui setiap kali filter berubah">
                <i class="fas fa-circle"></i>
                <span id="last-updated">Data hari ini</span>
            </div>
        </header>

        <section class="dashboard-panel" aria-label="Filter dashboard">
            <ul class="nav dashboard-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="summary-sales-tab" data-bs-toggle="pill" href="#summary-sales"
                        role="tab" aria-controls="summary-sales" aria-selected="true">
                        <i class="fas fa-chart-pie me-2"></i>Summary
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="outlet-compare-tab" data-bs-toggle="pill" href="#outlet-compare"
                        role="tab" aria-controls="outlet-compare" aria-selected="false">
                        <i class="fas fa-store-alt me-2"></i>Outlet Comparison
                    </a>
                </li>
            </ul>

            <div class="dashboard-filter-bar">
                <div class="outlet-filter">
                    <label class="filter-label" for="filter-outlet">
                        Outlet <span class="filter-hint" id="selected-outlet-count">{{ $outlets->count() }} dipilih</span>
                    </label>
                    <select id="filter-outlet" class="form-control select2" multiple>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}" selected>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-actions">
                    <button id="select-all-option" class="btn btn-select-all" type="button">
                        <i class="fas fa-check-double me-2"></i>Select All
                    </button>
                    <button id="deselect-all-option" class="btn btn-deselect-all" type="button">
                        <i class="fas fa-times me-2"></i>Deselect All
                    </button>
                </div>

                <div class="date-filter">
                    <label class="filter-label" for="date_range_transaction">Periode transaksi</label>
                    <div class="date-navigation">
                        <button class="btn btn-outline-secondary" type="button" id="prevDate" aria-label="Periode sebelumnya">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <input type="text" id="date_range_transaction" name="date_range_transaction"
                            class="form-control" aria-label="Rentang tanggal transaksi" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="nextDate" aria-label="Periode berikutnya">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <div class="tab-content" id="dashboard-tab-content">
            <div class="tab-pane fade show active" id="summary-sales" role="tabpanel" aria-labelledby="summary-sales-tab">
                <div class="metric-grid">
                    <article class="dashboard-card metric-card" style="--metric-color: #2563eb; --metric-soft: #eaf1ff;">
                        <div class="metric-top">
                            <p class="metric-label">Gross Sales</p>
                            <span class="metric-icon"><i class="fas fa-shopping-bag"></i></span>
                        </div>
                        <h2 id="gross-sales" class="metric-value">{{ formatRupiah(strval($grossSales), 'Rp. ') }}</h2>
                        <p class="metric-caption">Total penjualan kotor</p>
                    </article>

                    <article class="dashboard-card metric-card" style="--metric-color: #059669; --metric-soft: #e7f8f1;">
                        <div class="metric-top">
                            <p class="metric-label">Net Sales</p>
                            <span class="metric-icon"><i class="fas fa-wallet"></i></span>
                        </div>
                        <h2 id="net-sales" class="metric-value">{{ formatRupiah(strval($netSales), 'Rp. ') }}</h2>
                        <p class="metric-caption">Penjualan bersih periode ini</p>
                    </article>

                    <article class="dashboard-card metric-card" style="--metric-color: #7c3aed; --metric-soft: #f1eafe;">
                        <div class="metric-top">
                            <p class="metric-label">Gross Profit</p>
                            <span class="metric-icon"><i class="fas fa-chart-line"></i></span>
                        </div>
                        <h2 id="gross-profit" class="metric-value">{{ formatRupiah(strval($netSales), 'Rp. ') }}</h2>
                        <p class="metric-caption">Estimasi laba kotor</p>
                    </article>

                    <article class="dashboard-card metric-card" style="--metric-color: #ea7c18; --metric-soft: #fff2e5;">
                        <div class="metric-top">
                            <p class="metric-label">Transactions</p>
                            <span class="metric-icon"><i class="fas fa-receipt"></i></span>
                        </div>
                        <h2 id="transactions" class="metric-value">{{ $transactions }}</h2>
                        <p class="metric-caption">Jumlah transaksi berhasil</p>
                    </article>
                </div>

                <section class="dashboard-card chart-card">
                    <div class="chart-header">
                        <div>
                            <h3 class="section-title">Hourly Gross Sales</h3>
                            <p class="section-description">Distribusi penjualan berdasarkan jam transaksi.</p>
                        </div>
                        <div id="chart-legend" class="chart-legend" aria-label="Legenda outlet"></div>
                    </div>
                    <div class="chart-body">
                        <div class="chart-container">
                            <canvas id="statisticsChart"></canvas>
                        </div>
                    </div>
                </section>
            </div>

            <div class="tab-pane fade" id="outlet-compare" role="tabpanel" aria-labelledby="outlet-compare-tab">
                <div class="comparison-heading">
                    <div>
                        <h3 class="section-title">Outlet Performance</h3>
                        <p class="section-description">Bandingkan performa dan produk setiap outlet pada periode yang dipilih.</p>
                    </div>
                    <span id="comparison-count" class="comparison-count">0 outlet</span>
                </div>
                <div id="list-outlet" class="outlet-grid">
                    <div class="dashboard-card empty-state">
                        <div class="empty-state-icon"><i class="fas fa-store"></i></div>
                        <h5>Pilih tab untuk memuat perbandingan</h5>
                        <p>Data outlet akan ditampilkan sesuai filter yang aktif.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        const hours = @json($hours);
        const hourlyGrossSalesPerOutlet = @json($hourlyGrossSalesPerOutlet);
        const outlets = @json($outlets);
        const chartColors = ['#c9363f', '#2563eb', '#059669', '#7c3aed', '#ea7c18', '#0891b2', '#db2777', '#4f46e5'];

        var outlet = $('#filter-outlet');
        var date = $('#date_range_transaction');
        var startDate = moment().startOf('day');
        var endDate = moment().endOf('day');
        var isInitializing = true;
        var dashboardSalesChart = null;

        function getOutletColor(outletId) {
            const outletIndex = outlets.findIndex(function(item) {
                return String(item.id) === String(outletId);
            });

            return chartColors[(outletIndex < 0 ? 0 : outletIndex) % chartColors.length];
        }

        function updateLastUpdated() {
            $('#last-updated').text('Diperbarui ' + moment().format('HH:mm'));
        }

        function updateSelectedCount() {
            const count = (outlet.val() || []).length;
            $('#selected-outlet-count').text(count + ' dipilih');
        }

        function resetSummary() {
            $('#gross-sales, #net-sales, #gross-profit').text(formatRupiah('0', 'Rp. '));
            $('#transactions').text('0');
            initChart({
                outlets: [],
                hours: hours,
                hourlyGrossSalesPerOutlet: {}
            });
            updateLastUpdated();
        }

        function renderComparisonEmpty(title, description) {
            $('#comparison-count').text('0 outlet');
            $('#list-outlet').empty().append(
                $('<div>').addClass('dashboard-card empty-state').append(
                    $('<div>').addClass('empty-state-icon').append($('<i>').addClass('fas fa-store')),
                    $('<h5>').text(title),
                    $('<p>').text(description)
                )
            );
        }

        function getDataSummary() {
            if ((outlet.val() || []).length === 0) {
                resetSummary();
                return;
            }

            $.ajax({
                url: '{{ route('getDataSummary') }}',
                method: 'GET',
                data: {
                    date: date.val(),
                    outlet: outlet.val()
                },
                beforeSend: function() {
                    showLoader();
                },
                complete: function() {
                    showLoader(false);
                },
                success: function(data) {
                    $('#gross-sales').text(formatRupiah(data.grossSales.toString(), 'Rp. '));
                    $('#net-sales').text(formatRupiah(data.netSales.toString(), 'Rp. '));
                    $('#gross-profit').text(formatRupiah(data.netSales.toString(), 'Rp. '));
                    $('#transactions').text(data.transactions);
                    initChart(data);
                    updateLastUpdated();
                },
                error: function(xhr) {
                    console.error(xhr);
                }
            });
        }

        function initChart(data) {
            const context = document.getElementById('statisticsChart').getContext('2d');
            const datasets = data.outlets.map(function(dataOutlet) {
                const color = getOutletColor(dataOutlet.id);

                return {
                    label: dataOutlet.name,
                    borderColor: color,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: color,
                    pointHoverBackgroundColor: color,
                    pointRadius: 2.5,
                    pointHoverRadius: 5,
                    backgroundColor: color + '14',
                    fill: true,
                    borderWidth: 2,
                    lineTension: .3,
                    data: data.hourlyGrossSalesPerOutlet[dataOutlet.id] || Array(24).fill(0)
                };
            });

            if (dashboardSalesChart && typeof dashboardSalesChart.destroy === 'function') {
                dashboardSalesChart.destroy();
            }

            dashboardSalesChart = new Chart(context, {
                type: 'line',
                data: {
                    labels: data.hours,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        display: false
                    },
                    tooltips: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#172033',
                        bodySpacing: 6,
                        xPadding: 12,
                        yPadding: 10,
                        callbacks: {
                            label: function(tooltipItem, chartData) {
                                const label = chartData.datasets[tooltipItem.datasetIndex].label || '';
                                return label + ': ' + formatRupiah(String(tooltipItem.yLabel), 'Rp. ');
                            }
                        }
                    },
                    hover: {
                        mode: 'nearest',
                        intersect: true
                    },
                    layout: {
                        padding: {
                            left: 4,
                            right: 8,
                            top: 18,
                            bottom: 2
                        }
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                maxTicksLimit: 6,
                                padding: 10,
                                callback: function(value) {
                                    if (value >= 1000000) return (value / 1000000) + ' jt';
                                    if (value >= 1000) return (value / 1000) + ' rb';
                                    return value;
                                }
                            },
                            gridLines: {
                                color: '#eef1f5',
                                drawBorder: false,
                                zeroLineColor: '#eef1f5'
                            }
                        }],
                        xAxes: [{
                            gridLines: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                autoSkip: true,
                                maxTicksLimit: 12,
                                padding: 10
                            }
                        }]
                    }
                }
            });

            renderChartLegend(datasets);
        }

        function renderChartLegend(datasets) {
            const legend = $('#chart-legend').empty();

            if (!datasets.length) {
                legend.append($('<span>').addClass('legend-item').text('Tidak ada outlet dipilih'));
                return;
            }

            datasets.forEach(function(dataset) {
                legend.append(
                    $('<span>').addClass('legend-item').append(
                        $('<span>').addClass('legend-dot').css('background-color', dataset.borderColor),
                        $('<span>').text(dataset.label)
                    )
                );
            });
        }

        function getProductName(item) {
            if (!item.variant) return item.product || 'Tanpa nama';
            if (item.product === item.variant) return item.variant;
            return item.product + ' - ' + item.variant;
        }

        function createProductGroup(title, items, type) {
            const list = $('<div>');
            const group = $('<div>').addClass('product-group');
            const iconClass = type === 'top' ? 'fa-arrow-up' : 'fa-arrow-down';

            group.append(
                $('<div>').addClass('product-group-title ' + type).append(
                    $('<i>').addClass('fas ' + iconClass),
                    $('<span>').text(title)
                )
            );

            if (Array.isArray(items) && items.length) {
                items.forEach(function(item) {
                    list.append(
                        $('<div>').addClass('product-item').append(
                            $('<span>').addClass('product-name').attr('title', getProductName(item)).text(getProductName(item)),
                            $('<span>').addClass('product-qty').text(item.qty != null ? item.qty : 0)
                        )
                    );
                });
            } else {
                list.append($('<div>').addClass('product-name py-2').text('Tidak ada item'));
            }

            return group.append(list);
        }

        function createOutletMetric(label, value) {
            return $('<div>').addClass('outlet-metric').append(
                $('<span>').addClass('outlet-metric-label').text(label),
                $('<span>').addClass('outlet-metric-value').text(value)
            );
        }

        function generateOutletCard(data) {
            const container = $('#list-outlet').empty();
            const outletData = data.data || [];

            $('#comparison-count').text(outletData.length + ' outlet');

            if (!outletData.length) {
                renderComparisonEmpty('Belum ada data outlet', 'Coba pilih outlet atau ubah periode transaksi.');
                return;
            }

            outletData.forEach(function(dataOutlet) {
                const initials = dataOutlet.outlet.split(/\s+/).slice(0, 2).map(function(word) {
                    return word.charAt(0);
                }).join('');

                const card = $('<article>').addClass('dashboard-card outlet-card');
                const header = $('<div>').addClass('outlet-card-header').append(
                    $('<div>').addClass('outlet-avatar').text(initials || 'OT'),
                    $('<div>').append(
                        $('<h4>').addClass('outlet-name').text(dataOutlet.outlet),
                        $('<p>').addClass('outlet-subtitle').text('Sales performance')
                    )
                );
                const metrics = $('<div>').addClass('outlet-metrics').append(
                    createOutletMetric('Gross Sales', formatRupiah(dataOutlet.grossSales.toString(), 'Rp. ')),
                    createOutletMetric('Net Sales', formatRupiah(dataOutlet.netSales.toString(), 'Rp. ')),
                    createOutletMetric('Gross Profit', formatRupiah(dataOutlet.netSales.toString(), 'Rp. ')),
                    createOutletMetric('Transactions', dataOutlet.transactions),
                    createOutletMetric('Avg. Sales', formatRupiah(dataOutlet.averageSales.toString(), 'Rp. ')),
                    createOutletMetric('Gross Margin', dataOutlet.grossMargin + '%')
                );
                const products = $('<div>').addClass('product-groups').append(
                    createProductGroup('Top Items', dataOutlet.topThreeItem, 'top'),
                    createProductGroup('Low Items', dataOutlet.downThreeItem, 'down')
                );

                container.append(card.append(header, metrics, products));
            });
        }

        function getDataOutletCompare() {
            if ((outlet.val() || []).length === 0) {
                renderComparisonEmpty('Belum ada outlet dipilih', 'Pilih satu atau beberapa outlet untuk melihat perbandingan.');
                updateLastUpdated();
                return;
            }

            $.ajax({
                url: '{{ route('getDataOutletCompare') }}',
                method: 'GET',
                data: {
                    date: date.val(),
                    outlet: outlet.val()
                },
                beforeSend: function() {
                    showLoader();
                },
                complete: function() {
                    showLoader(false);
                },
                success: function(data) {
                    generateOutletCard(data);
                    updateLastUpdated();
                },
                error: function(xhr) {
                    console.error(xhr);
                }
            });
        }

        function checkActiveTab() {
            const activeTab = $('.dashboard-tabs .nav-link.active').attr('href');

            if (activeTab === '#outlet-compare') {
                getDataOutletCompare();
            } else {
                getDataSummary();
            }
        }

        $(document).ready(function() {
            outlet.select2({
                placeholder: '-- Pilih Outlet --',
                multiple: true,
                closeOnSelect: false
            });

            const allOutletValues = outlet.find('option').map(function() {
                return this.value;
            }).get();
            outlet.val(allOutletValues).trigger('change.select2');
            updateSelectedCount();

            date.daterangepicker({
                startDate: startDate,
                endDate: endDate,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                },
                linkedCalendars: false,
                autoUpdateInput: false,
                showCustomRangeLabel: true,
                drops: 'auto',
                buttonClasses: 'btn btn-primary'
            }, function(start, end) {
                startDate = start;
                endDate = end;
                date.val(start.format('YYYY/MM/DD') + ' - ' + end.format('YYYY/MM/DD'));
                checkActiveTab();
            });

            date.val(startDate.format('YYYY/MM/DD') + ' - ' + endDate.format('YYYY/MM/DD'));

            $('#select-all-option').on('click', function() {
                outlet.val(allOutletValues).trigger('change');
            });

            $('#deselect-all-option').on('click', function() {
                outlet.val(null).trigger('change');
            });

            outlet.on('change', function() {
                updateSelectedCount();
                if (!isInitializing) checkActiveTab();
            });

            $('.dashboard-tabs a[data-bs-toggle="pill"]').on('shown.bs.tab', function() {
                checkActiveTab();
            });

            $('#prevDate').on('click', function() {
                startDate.subtract(1, 'days');
                endDate.subtract(1, 'days');
                date.data('daterangepicker').setStartDate(startDate);
                date.data('daterangepicker').setEndDate(endDate);
                date.val(startDate.format('YYYY/MM/DD') + ' - ' + endDate.format('YYYY/MM/DD'));
                checkActiveTab();
            });

            $('#nextDate').on('click', function() {
                startDate.add(1, 'days');
                endDate.add(1, 'days');
                date.data('daterangepicker').setStartDate(startDate);
                date.data('daterangepicker').setEndDate(endDate);
                date.val(startDate.format('YYYY/MM/DD') + ' - ' + endDate.format('YYYY/MM/DD'));
                checkActiveTab();
            });

            initChart({
                outlets: outlets,
                hours: hours,
                hourlyGrossSalesPerOutlet: hourlyGrossSalesPerOutlet
            });
            updateLastUpdated();
            isInitializing = false;
        });
    </script>
@endpush
