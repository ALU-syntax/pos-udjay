@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Laporan</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-cash-register"></i></span>
                    Transaction
                </h1>
                <p class="ob-header__desc">Daftar transaksi yang sudah selesai (Success Order) per outlet dan rentang tanggal.</p>
            </div>
        </div>

        {{-- Filter --}}
        <div class="ob-card">
            <div class="ob-toolbar">
                <div class="ob-field">
                    <label for="filter-outlet"><i class="fas fa-store" aria-hidden="true"></i> Outlet</label>
                    <select id="filter-outlet" class="form-control select2">
                        {{-- <option value="all" selected>-- Semua Outlet --</option> --}}
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

                <div class="ob-field ob-field--action">
                    <button id="btnExport" class="btn ob-btn ob-btn-primary">Export Transaksi</button>
                </div>
            </div>
        </div>

        {{-- KPI --}}
        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-receipt"></i></span>
                <div>
                    <p class="ob-stat__label">Transactions</p>
                    <h4 class="ob-stat__value" id="transaction">{{ count($data) }}</h4>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-wallet"></i></span>
                <div>
                    <p class="ob-stat__label">Total Collected</p>
                    <h4 class="ob-stat__value" id="total-collected"></h4>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-chart-line"></i></span>
                <div>
                    <p class="ob-stat__label">Net Sales</p>
                    <h4 class="ob-stat__value" id="net-sales">23</h4>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Tabel --}}
            <div class="col-12 animated" id="tableContainer">
                <div class="ob-card">
                    <div class="ob-tabs-bar">
                        <ul class="nav ob-tabs">
                            <li class="nav-item">
                                <a class="nav-link active" aria-current="page" href="#">
                                    <i class="fas fa-check-circle" aria-hidden="true"></i> Success Order
                                </a>
                            </li>
                        </ul>
                        <span class="ob-hint"><i class="fas fa-mouse-pointer" aria-hidden="true"></i> Klik baris untuk melihat detail</span>
                    </div>
                    <div class="ob-table-wrap">
                        <div class="table-responsive">
                            {!! $dataTable->table() !!}
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
                            <div class="ob-detail__name"><span id="complete-time"></span></div>
                            <span class="badge badge-success">Complete</span>
                        </div>
                        <button type="button" class="ob-icon-btn" id="btn-close-detail" aria-label="Tutup detail">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="ob-detail__body">
                        <h2 class="ob-section-title"><i class="fas fa-info-circle" aria-hidden="true"></i> Informasi</h2>
                        <dl class="ob-meta">
                            <div class="ob-meta__item">
                                <dt>Collected By</dt>
                                <dd id="collected-by"></dd>
                            </div>
                            <div class="ob-meta__item">
                                <dt>Payment Method</dt>
                                <dd id="payment-method"></dd>
                            </div>
                            <div class="ob-meta__item">
                                <dt>Served by</dt>
                                <dd id="served-by"></dd>
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
                                <dt>Pax</dt>
                                <dd></dd>
                            </div>
                            <div class="ob-meta__item">
                                <dt>Duration</dt>
                                <dd></dd>
                            </div>
                            <div class="ob-meta__item">
                                <dt>Point Use</dt>
                                <dd id="container-point-use">-</dd>
                            </div>

                            {{-- Customer + aksi kaitkan customer --}}
                            <div class="ob-meta__item ob-meta__item--wide">
                                <div class="ob-meta__head">
                                    <dt>Customer</dt>
                                    <div class="ob-actions">
                                        @can('edit-customer report/transactions')
                                            <button id="btn-kaitkan-customer" class="btn ob-btn ob-btn-outline ob-btn-sm">
                                                <i class="fas fa-user-tag" aria-hidden="true"></i> Kaitkan Customer
                                            </button>
                                        @endcan

                                        <button id="btn-batal-kaitkan-customer" class="btn ob-btn ob-btn-ghost ob-btn-sm d-none">Batal</button>

                                        <form action="{{ route('report/transaction/kaitkanCustomer') }}" id="submit-customer" method="PUT" class="d-none" enctype="multipart/form-data">
                                            @csrf

                                            <input type="text" name="id_transaction" id="id_transaction" hidden>
                                            <button id="btn-confirm-kaitkan-customer" class="btn ob-btn ob-btn-primary ob-btn-sm" type="submit">
                                                <i class="fas fa-check" aria-hidden="true"></i> Confirm
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <dd id="container-customer" class="d-flex"><span id="customer">-</span></dd>
                            </div>
                        </dl>

                        <h2 class="ob-section-title"><i class="fas fa-calculator" aria-hidden="true"></i> Ringkasan</h2>
                        <div class="ob-summary">
                            <div class="ob-summary__row ob-summary__row--total">
                                <div>Total Amount</div>
                                <div id="total-amount"></div>
                            </div>
                        </div>

                        <h2 class="ob-section-title"><i class="fas fa-utensils" aria-hidden="true"></i> Ordered Items</h2>
                        <div class="ob-items">
                            <table class="table" id="table-list-item">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="ob-detail__foot">
                        <div class="ob-actions">
                            @can('delete report/transactions')
                                <a id="btn-delete-transaction" class="btn ob-btn ob-btn-danger" href="">
                                    <i class="fas fa-trash-alt" aria-hidden="true"></i> Hapus Transaksi
                                </a>
                            @endcan
                        </div>
                        <div class="ob-actions">
                            <a href="{{ route('report/transaction/modalResendReceipt', 1) }}"
                                class="btn ob-btn ob-btn-outline action" id="btn-resend-receipt">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i> Resend Receipt
                            </a>
                            <a href="{{ route('report/transaction/showReceipt', 1) }}"
                                class="btn ob-btn ob-btn-primary" id="btn-show-receipt">
                                <i class="fas fa-file-invoice" aria-hidden="true"></i> Show Receipt
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            var dataTransaksi = @json($data);
            let totalTransaksi = 0;
            dataTransaksi.forEach(function(item) {
                totalTransaksi += parseInt(item.total);
            });
            var stateLastId = 0;
            const tableContainer = $('#tableContainer');
            const detailCard = $('#container-order-details');
            const transactionTable = $('#transactions-table');

            const $outlet = $('#filter-outlet');
            const $date = $('#date_range_transaction');
            const $btn = $('#btnExport');
            const $status = $('#exportStatus');

            function getNewData() {
                let outletTerpilih = $('#filter-outlet').val();
                let date = $('#date_range_transaction').val();

                $.ajax({
                    url: `{{ route('report/transaction/getTransactionData') }}`, // URL endpoint Laravel
                    type: 'GET',
                    data: {
                        idOutlet: outletTerpilih, // Kirim data array ke server
                        date: date
                    },
                    beforeSend: function() {
                        showLoader();
                    },
                    complete: function() {
                        showLoader(false);
                    },
                    success: function(response) {
                        console.log(response)
                        let totalTransaksi = 0;
                        response.data.forEach(function(item) {
                            totalTransaksi += parseInt(item.total);
                        });

                        console.log(totalTransaksi);
                        $('#transaction').text(response.data.length);
                        $("#total-collected").text(formatRupiah(totalTransaksi.toString(), "Rp. "));
                        $("#net-sales").text(formatRupiah(totalTransaksi.toString(), "Rp. "));
                    },
                    error: function(xhr, status, error) {
                        console.error("Terjadi kesalahan:", error);
                    }
                });
            }

            $('#transactions-table tbody tr').on('click', function() {
                var tableContainer = $('#tableContainer');
                console.log(tableContainer.hasClass('col-12'))
                if (tableContainer.hasClass('col-12')) {
                    tableContainer.removeClass('col-12').addClass('col-6');
                } else {
                    tableContainer.removeClass('col-6').addClass('col-12');
                }
            });

            function manipulateIdShowReceipt(id) {
                var $link = $('#btn-show-receipt');

                // Ambil nilai atribut href
                var currentHref = $link.attr('href');

                // Pecah URL menjadi bagian-bagian
                // Misalnya, URL adalah /report/transaction/showReceipt/1
                // Kita akan mengambil bagian terakhir yang merupakan angka 1
                var urlParts = currentHref.split('/');
                var lastIndex = urlParts.length - 1;
                var currentNumber = parseInt(urlParts[lastIndex], 10);

                // Ubah angka tersebut (misalnya tambahkan 1)
                var newNumber = id;

                // Ganti bagian terakhir dari URL dengan angka baru
                urlParts[lastIndex] = newNumber.toString();

                // Gabungkan kembali bagian-bagian URL menjadi URL baru
                var newHref = urlParts.join('/');

                // Ganti atribut href dengan URL baru
                $link.attr('href', newHref);
            }

            function manipulateIdResendReceipt(id) {
                var $link = $('#btn-resend-receipt');

                // Ambil nilai atribut href
                var currentHref = $link.attr('href');

                // Pecah URL menjadi bagian-bagian
                // Misalnya, URL adalah /report/transaction/showReceipt/1
                // Kita akan mengambil bagian terakhir yang merupakan angka 1
                var urlParts = currentHref.split('/');
                var lastIndex = urlParts.length - 1;
                var currentNumber = parseInt(urlParts[lastIndex], 10);

                // Ubah angka tersebut (misalnya tambahkan 1)
                var newNumber = id;

                // Ganti bagian terakhir dari URL dengan angka baru
                urlParts[lastIndex] = newNumber.toString();

                // Gabungkan kembali bagian-bagian URL menjadi URL baru
                var newHref = urlParts.join('/');

                // Ganti atribut href dengan URL baru
                $link.attr('href', newHref);
            }

            function fetchDetailTransaction(idTransaction) {
                $.ajax({
                    url: `{{ route('report/transaction/getTransactionDataDetail') }}`,
                    method: 'GET',
                    data: {
                        idTransaction: idTransaction,
                    },
                    beforeSend: function() {
                        showLoader();
                    },
                    complete: function() {
                        showLoader(false);
                    },
                    success: function(res) {
                        // console.log(res);

                        $('#id_transaction').val(res.data.id);
                        $('#complete-time').text(res.data.create_formated);
                        $('#collected-by').text(res.data.user.name);
                        $('#total-amount').text(formatRupiah(res.data.total.toString(), "Rp. "));
                        $('#payment-method').text(res.data.nama_tipe_pembayaran)

                        $('#submit-customer').addClass('d-none');
                        $('#btn-delete-transaction, #btn-close-detail, #btn-resend-receipt, #btn-show-receipt, #btn-kaitkan-customer').removeClass('d-none');
                        $('#btn-batal-kaitkan-customer').addClass('d-none');
                        $('#container-customer').addClass('d-flex');

                        $('#customer').empty();
                        $('#customer').text(res.customer?.name);

                        let customer = res.data.customer ? res.data.customer.name : '-'
                        let idCustomer = res.data.customer ? res.data.customer.id : null;
                        let pointUse = res.data.potongan_point ? res.data.potongan_point : 0;

                        $('#container-point-use').text(formatRupiah(pointUse.toString(), "Rp. "));
                        $('#customer').text(customer);
                        $('#btn-batal-kaitkan-customer').data('customer', customer);
                        $('#btn-kaitkan-customer').data('customer-id', idCustomer);

                        if(!$('#container-customer').hasClass('d-flex')){
                            $('#container-customer').addClass('d-flex');
                        }

                        var urlDestroyTransaction = "{{ route('report/transaction/destroy', ':id') }}".replace(
                            ':id', res.data.id);
                        $('#btn-delete-transaction').attr('href', urlDestroyTransaction);

                        // Mengosongkan tabel sebelum mengisi data baru
                        const tbody = $('#table-list-item tbody');
                        tbody.empty(); // Menghapus semua baris yang ada

                        // Mengisi tabel dengan data item_transaction
                        res.data.item_transaction.forEach(item => {
                            let modifiers = [];
                            try {
                                modifiers = JSON.parse(item.modifier_id) ?? [];
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
                                                    <td>${item.total_count}</td>
                                                </tr>
                                            `);
                            } else {
                                tbody.append(`
                                                <tr>
                                                    <td>custom</td>
                                                    <td>1</td>
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

            function removeDetailCard(detailCard, tableContainer, transactionTable) {
                detailCard.hide(1000, function(a) {
                    const cardDetail = $(this);
                    cardDetail.css('display', 'none').animate({
                        opacity: 0
                    });
                    transactionTable.animate({
                        width: '100%'
                    });

                    tableContainer.removeClass('col-6').addClass('col-12');
                    tableContainer.animate({
                        width: '100%'
                    }, 500);
                });
            }

            function handleClickRowTransaction(idTransaction) {
                manipulateIdShowReceipt(idTransaction);
                manipulateIdResendReceipt(idTransaction);
                if (tableContainer.hasClass('col-12')) {
                    tableContainer.removeClass('col-12').addClass('col-6');
                    tableContainer.animate({
                        width: '50%'
                    }, 500, function() {
                        transactionTable.animate({
                            width: '50%'
                        });

                        // Setelah animasi selesai, tampilkan detail card
                        detailCard.css('display', 'block').animate({
                            opacity: 1
                        }, 500);
                    });

                    fetchDetailTransaction(idTransaction);
                } else {
                    if (stateLastId != idTransaction) {
                        fetchDetailTransaction(idTransaction)
                    } else {
                        // Jika ingin mengembalikan ke ukuran asal
                        removeDetailCard(detailCard, tableContainer, transactionTable);
                        stateLastId = 0;
                        return
                    }
                }

                stateLastId = idTransaction;
            }

            function showModalResendReceipt(url) {
                $.ajax({
                    url,
                    method: "GET",
                    beforeSend: function() {
                        // showLoading()
                    },
                    complete: function() {
                        // hideLoading(false)
                    },
                    success: (res) => {
                        if (res) {
                            const modal = $('#modal_action');
                            modal.html(res);
                            modal.modal('show');
                        }
                    },
                    error: function(err) {
                        console.log(err);
                    }
                });
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

            $(document).ready(function() {
                $('#btn-close-detail').off().on('click', function() {
                    removeDetailCard(detailCard, tableContainer, transactionTable);
                });
                console.log(totalTransaksi);
                $("#total-collected").text(formatRupiah(totalTransaksi.toString(), "Rp. "));
                $("#net-sales").text(formatRupiah(totalTransaksi.toString(), "Rp. "));

                $("#filter-outlet").select2();
                var success = "{{ session('success') }}";
                const datatable = 'transactions-table';

                $(".select2InsideModal").select2({
                    dropdownParent: $("#modal_action")
                });

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
                    var table = $('#' + datatable).DataTable();
                    table.ajax.url("{{ route('report/transaction') }}?outlet=" + $('#filter-outlet').val() +
                            "&date=" + $('#date_range_transaction').val())
                        .load();

                    getNewData();
                });

                // Set initial value for the input field
                $('#date_range_transaction').val(startDate.format('YYYY/MM/DD') + ' - ' + endDate.format('YYYY/MM/DD'));

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

                    var table = $('#' + datatable).DataTable();
                    table.ajax.url("{{ route('report/transaction') }}?outlet=" + $('#filter-outlet').val() +
                            "&date=" + $('#date_range_transaction').val())
                        .load();

                    getNewData();
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

                    var table = $('#' + datatable).DataTable();
                    table.ajax.url("{{ route('report/transaction') }}?outlet=" + $('#filter-outlet').val() +
                            "&date=" + $('#date_range_transaction').val())
                        .load();

                    getNewData();
                });

                $('.ranges li').addClass('btn btn-primary w-75 ms-3 mt-2');

                // Event untuk mengosongkan rentang saat daterangepicker dibuka
                $('#date_range_transaction').on('show.daterangepicker', function(ev, picker) {
                    picker.setStartDate(moment().startOf(
                        'day')); // Set start date ke hari ini atau tanggal lain
                    picker.setEndDate(moment().startOf('day')); // Set end date ke hari ini atau tanggal lain
                });

                // Event untuk menangani pemilihan rentang yang telah ditentukan
                $('#date_range_transaction').on('apply.daterangepicker', function(ev, picker) {
                    // Memperbarui DataTable ketika rentang yang telah ditentukan dipilih
                    var table = $('#' + datatable).DataTable();

                    console.log(picker.startDate.format('YYYY-MM-DD'));
                    console.log(picker.endDate.format('YYYY-MM-DD'));
                    table.ajax.url("{{ route('report/transaction') }}?outlet=" + $('#filter-outlet').val() +
                            "&date=" + picker.startDate.format('YYYY/MM/DD') + ' - ' + picker.endDate.format(
                                'YYYY/MM/DD'))
                        .load();

                    getNewData();
                });

                handleAction(datatable);

                $('#filter-outlet').on('change', function() {
                    console.log($('#filter-outlet').val());
                    console.log($('#date_range_transaction').val());
                    var table = $('#' + datatable).DataTable();

                    // Refresh tabel
                    table.ajax.url("{{ route('report/transaction') }}?outlet=" + $('#filter-outlet').val() +
                            "&date=" + $('#date_range_transaction').val())
                        .load();

                    getNewData()
                });

                $('#btn-delete-transaction').on('click', function(e) {
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
                            handleAjax(this.href, 'delete').onSuccess(function(res) {
                                // showToast(res.status, res.message)
                                window.LaravelDataTables[datatable].ajax.reload(null, false)
                            }, false).excute();
                            console.log(result);
                            showToast('success', "Data berhasil dihapus");
                            removeDetailCard(detailCard, tableContainer, transactionTable);
                        }
                    })

                });


                $('#btnExport').on('click', function(e) {
                    e.preventDefault();

                    const range = parseDateRange($('#date_range_transaction').val());
                    if (!range) {
                        iziToast.error({
                            title: 'Gagal',
                            message: "Isi tanggal dengan format <b>YYYY-MM-DD - YYYY-MM-DD</b>."
                        });
                        return;
                    }

                    const payload = {
                        from: range.from,
                        to: range.to
                    };
                    const ov = $outlet.val();
                    if (Array.isArray(ov)) ov.forEach(id => (payload['outlet_id[]'] = (payload['outlet_id[]'] ||
                        []), payload['outlet_id[]'].push(id)));
                    else if (ov) payload['outlet_id[]'] = ov;

                    $btn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm mr-1"></span>Memproses…');

                    $.ajax({
                            url: "{{ route('report/transaction/exportPosFormat') }}", // POST ke controller ->queue()
                            method: 'POST',
                            data: payload,
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        })
                        .done(function(resp) {
                            if (resp && resp.ok && resp.download_url) {
                                const filename = (resp.path || 'transactions.xlsx').split('/').pop();
                                waitUntilReadyWithIziToast(resp.download_url, filename, {
                                    intervalMs: 3000, // cek tiap 3 detik
                                    timeoutMs: 10 * 60 * 1000 // maksimal 10 menit
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
                                title: 'Timeout',
                                message: msg
                            });
                        })
                        .always(function() {
                            $btn.prop('disabled', false).text('Export Transaksi');
                        });
                });

                $('#btn-kaitkan-customer').on('click', function() {
                    $('#btn-kaitkan-customer').addClass('d-none');
                    $('#btn-batal-kaitkan-customer').removeClass('d-none');
                    // kosongkan container
                    $('#customer').empty();
                    $('#btn-delete-transaction, #btn-close-detail, #btn-resend-receipt, #btn-show-receipt').addClass('d-none');

                    $('#container-customer').removeClass('d-flex')
                    $('#submit-customer').removeClass('d-none');

                    let idCustomerTerpasang = $('#btn-kaitkan-customer').data('customer-id');

                    // ambil data dari server (sudah jadi array JS)
                    const dataCustomer = @json($customer);

                    // buat elemen select (STRING atau DOM, bebas; di sini DOM biar gampang)
                    const $select = $(`
                        <select id="select-customer" class="form-control select2" style="width:100%">
                        <option value="" selected disabled>— Pilih Customer —</option>
                        </select>
                    `);

                    // generate <option> dari dataCustomer
                    // pastikan aman dari null phone, dsb.
                    dataCustomer.forEach(c => {
                        const label = [c.name, c.telfon].filter(Boolean).join(' — ');
                        const isSelected = String(c.id) === String(idCustomerTerpasang); // <- kunci: samakan tipe
                        $select.append(new Option(label, c.id, isSelected, isSelected));
                    });

                    // sisipkan ke DOM
                    $('#customer').append($select);

                    // PRESELECT fallback (kalau belum terset dari loop)
                    if (idCustomerTerpasang != null && idCustomerTerpasang !== '') {
                        $select.val(String(idCustomerTerpasang));
                    }

                    // init Select2
                    if ($select.data('select2')) $select.select2('destroy');
                    $select.select2({
                        placeholder: 'Ketik nama/telepon…',
                        width: '100%',
                    });

                    // pastikan Select2 UI ikut ter-update
                    if (idCustomerTerpasang != null && idCustomerTerpasang !== '') {
                        $select.trigger('change.select2');
                    }
                });

                $('#btn-batal-kaitkan-customer').on('click', function(){
                    $(this).addClass('d-none');
                    $('#submit-customer').addClass('d-none');
                    $('#btn-delete-transaction, #btn-close-detail, #btn-resend-receipt, #btn-show-receipt, #btn-kaitkan-customer').removeClass('d-none');
                    $('#container-customer').addClass('d-flex')
                    const customer = $(this).data('customer');

                    $('#customer').empty();
                    $('#customer').text(customer);
                });

                $("#submit-customer").on('submit', function(e) {
                    e.preventDefault();
                    const _form = this
                    let dataForm = new FormData(_form);
                    const customerIdLama = $('#btn-kaitkan-customer').data('customer-id');
                    const customerId = $('#select-customer').val();

                    dataForm.append('customer', customerId);
                    dataForm.append('oldCustomer', customerIdLama);


                    if(customerIdLama == customerId){
                        showToast("error", "Customer tidak boleh sama");
                        return
                    };

                    $.ajax({
                        url: this.action,
                        method: 'POST',
                        data: dataForm,
                        contentType: false,
                        processData: false,
                        beforeSend: function() {
                            submitLoader().show()
                        },
                        success: (res) => {
                            $('#submit-customer').addClass('d-none');
                            $('#btn-delete-transaction, #btn-close-detail, #btn-resend-receipt, #btn-show-receipt, #btn-kaitkan-customer').removeClass('d-none');
                            $('#btn-batal-kaitkan-customer').addClass('d-none');
                            $('#container-customer').addClass('d-flex');

                            $('#customer').empty();
                            $('#customer').text(res.customer?.name);

                            $('#btn-kaitkan-customer').data('customer-id', res.customer?.id);
                            showToast(res.status, res.message)
                        },
                        complete: function() {
                            submitLoader().hide()
                        },
                        error: function(err) {
                            const errors = err.responseJSON?.errors;

                            console.log(err);
                            console.log(err.responseJSON);
                            console.log(err.responseJSON?.errors);
                            if (errors) {
                                for (let [key, message] of Object.entries(errors)) {
                                    console.log(message);
                                    $(`[name=${key}]`).addClass('is-invalid')
                                        .parent()
                                        .append(
                                            `<div class="invalid-feedback">${message}</div>`
                                        )
                                }
                            }

                            showToast('error', err.responseJSON?.message)
                        }
                    })
                })
            });
        </script>
    @endpush
@endsection
