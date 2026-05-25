@extends('layouts.app')
@section('content')
    <div class="main-content">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <h5>ROUNDING SETTINGS</h5>
                        <form action="{{ route('konfigurasi/checkout/store') }}" id="form_action" method="POST">
                            @csrf
                            <div class="row mb-3">
                                <label class="col-6">Enable Rounding</label>
                                <div class="col-6 text-end">
                                    <label class="switch">
                                        <input type="checkbox" id="roundingSwitch" name="rounded"
                                            @if ($data) @if ($data->rounded == 'true') checked @endif
                                            @endif >
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                            </div>

                            <div id="rounding-enable"
                                style="@if ($data) @if (!$data->rounded == 'true') display: none; @endif
@else
display: none; @endif">
                                <div class="row mb-3">
                                    <label class="col-6">Rounding Digits :</label>
                                    <div class="col-6">
                                        <select class="form-select" id="roundingDigits" name="rounded_type">
                                            <option value="1000"
                                                @if ($data) @if ($data->rounded_type == '1000') selected @endif
                                                @endif >000 - Thousands</option>
                                            <option value="100"
                                                @if ($data) @if ($data->rounded_type == '100') selected @endif
                                                @endif >00 - Hundreds</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-6">Rounding Down below :</label>
                                    <div class="col-6">
                                        <input type="number" value="{{ $data->rounded_benchmark ?? 1 }}"
                                            name="rounded_benchmark" class="form-control" id="roundingDown" value="1">
                                    </div>
                                </div>

                                <p class="text-muted">Saat ini Benchmark Tidak Berfungsi, diset berapapun nilainya akan
                                    menjadi seperti ini. 0 - 499 itu ke 0 , 500 - 999 itu ke 500</p>
                                <p id="exampleText">10001 is 10000; 10002 is 11000</p>
                            </div>

                            <div class="row">
                                <button class="btn btn-primary btn-md my-5 ms-3">Submit</button>
                            </div>
                        </form>

                    </div>

                    <div class="col-6">
                        <form method="POST" action="{{ route('konfigurasi/email/sendTest') }}">
                            @csrf
                            <label for="email">Masukkan Email:</label><br>
                            <input type="email" name="email" id="email" required>
                            <button type="submit">Kirim Email Test</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 col-md-8">
                        <h5>IMPORT DATA LAMA</h5>

                        <div>
                            <form action="{{ route('konfigurasi/checkout/backup-import') }}" method="POST"
                                enctype="multipart/form-data" id="backup_import_form">
                                @csrf
                                <input type="hidden" name="import_id" id="backup_import_id">
                                <div class="form-group mb-3">
                                    <label for="backup_file">Pilih file CSV MOKA</label>
                                    <input type="file" name="file" id="backup_file" class="form-control"
                                        accept=".csv,text/csv,text/plain" required>
                                    <small class="text-muted">Outlet pada CSV harus sama dengan master outlet di aplikasi.</small>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="fallback_outlet_id">Outlet tujuan</label>
                                    <select name="fallback_outlet_id" id="fallback_outlet_id" class="form-select" required>
                                        <option value="">Pilih outlet</option>
                                        @foreach ($outlets as $outlet)
                                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Dipakai saat nama outlet dari CSV lama berbeda dengan master outlet.</small>
                                </div>

                                <button type="submit" class="btn btn-primary" id="backup_import_button">
                                    Import
                                </button>
                            </form>

                            <div id="backup_import_progress_wrap" class="mt-4" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span id="backup_import_status" class="fw-semibold">Menyiapkan import...</span>
                                    <span id="backup_import_percent" class="text-muted">0%</span>
                                </div>
                                <div class="progress" style="height: 12px;">
                                    <div id="backup_import_progress"
                                        class="progress-bar progress-bar-striped progress-bar-animated"
                                        role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0"
                                        aria-valuemax="100"></div>
                                </div>
                                <div id="backup_import_summary" class="small text-muted mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    @push('js')
        <script>
            $(document).ready(function() {
                handleFormSubmit('#form_action').init();
                // Handle the rounding switch
                $('#roundingSwitch').on('change', function() {
                    if ($(this).is(':checked')) {
                        document.getElementById("rounding-enable").style.display = "block"
                    } else {
                        document.getElementById("rounding-enable").style.display = "none"
                    }
                });

                // Handle rounding digits change
                $('#roundingDigits').on('change', function() {
                    let roundingValue = $(this).val();
                    reset();
                    if (roundingValue == 100) {
                        // document.getElementById("roundingDown").max = "2"
                    } else if (roundingValue == 1000) {
                        // document.getElementById("roundingDown").max = "3"
                    }
                    console.log('Rounding digits set to: ' + roundingValue);
                });

                // Handle rounding down change
                $('#roundingDown').on('input', function() {
                    let roundingDownValue = $(this).val();
                    let roundingValue = $('#roundingDigits').val();

                    console.log(roundingDownValue);
                    // Convert value to string and check if it's a valid number
                    if (!isNaN(roundingDownValue)) {
                        roundingDownValue = roundingDownValue.toString(); // Convert to string

                        // Restrict input to 1 to 3 digits
                        if (roundingDownValue.length > 3) {
                            roundingDownValue = roundingDownValue.slice(0, 3); // Limit to 3 digits
                            $(this).val(roundingDownValue);

                        }
                    }

                    let exampleText = document.getElementById("exampleText");
                    exampleText.innerText =
                        `${10000 + parseInt(roundingDownValue)} is 10000; ${10001 + parseInt(roundingDownValue)} is 11000`
                    // Ensure minimum value of 1


                    if (roundingValue == "100") {
                        // Restrict input to 1 to 2 digits
                        if (roundingDownValue.length > 2) {
                            roundingDownValue = roundingDownValue.slice(0, 2); // Limit to 3 digits
                            $(this).val(roundingDownValue);
                            exampleText.innerText =
                                `${10000 + parseInt(roundingDownValue)} is 10000; ${10001 + parseInt(roundingDownValue)} is 10100`
                        }
                    }

                    if (roundingDownValue < 1) {
                        $(this).val(1); // Set minimum value to 1
                        exampleText.innerText = `10001 is 10000; 10002 is 11000`
                        if (roundingValue == "100") {
                            exampleText.innerText = `10001 is 10000; 10002 is 10100`

                        }
                    }
                    console.log('Rounding down below: ' + roundingDownValue);
                });

                $('#backup_import_form').on('submit', function(e) {
                    e.preventDefault();

                    const form = this;
                    const fileInput = document.getElementById('backup_file');
                    if (!fileInput.files.length) {
                        showToast('error', 'Pilih file CSV terlebih dahulu.');
                        return;
                    }

                    const importId = 'moka-' + Date.now() + '-' + Math.random().toString(16).slice(2);
                    $('#backup_import_id').val(importId);

                    const formData = new FormData(form);
                    const progressUrl = "{{ route('konfigurasi/checkout/backup-import-progress', ['importId' => '__IMPORT_ID__']) }}"
                        .replace('__IMPORT_ID__', encodeURIComponent(importId));

                    const button = $('#backup_import_button');
                    const originalText = button.text();
                    let pollTimer = null;
                    let completed = false;

                    resetBackupImportProgress();
                    setBackupImportProgress(3, 'Mengunggah file...');
                    button.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm me-2"></span>Mengimport...'
                    );

                    pollTimer = setInterval(function() {
                        $.get(progressUrl).done(function(res) {
                            if (!res || res.status === 'waiting') return;

                            if (res.status === 'failed') {
                                completed = true;
                                clearInterval(pollTimer);
                                setBackupImportProgress(100, res.error || res.message || 'Import gagal.', true);
                                button.prop('disabled', false).text(originalText);
                                showToast('error', res.error || res.message || 'Import gagal.');
                                return;
                            }

                            if (res.status === 'finished') {
                                completed = true;
                                clearInterval(pollTimer);
                                setBackupImportProgress(100, 'Import selesai.');
                                renderBackupImportSummary(res.summary || {});
                                button.prop('disabled', false).text(originalText);
                                form.reset();
                                showToast('success', 'Import selesai.');
                                return;
                            }

                            const percent = res.status === 'queued' ? 10 : Math.min(95, Math.max(20, parseInt(res.percent || 0)));
                            const label = res.total > 0 ?
                                `${res.message} ${res.processed}/${res.total} baris` :
                                res.message;

                            setBackupImportProgress(percent, label);

                            if (res.summary) {
                                renderBackupImportSummary(res.summary);
                            }
                        });
                    }, 600);

                    $.ajax({
                        url: form.action,
                        method: form.method,
                        data: formData,
                        contentType: false,
                        processData: false,
                        dataType: 'json',
                        xhr: function() {
                            const xhr = $.ajaxSettings.xhr();
                            if (xhr.upload) {
                                xhr.upload.addEventListener('progress', function(event) {
                                    if (!event.lengthComputable) return;

                                    const uploaded = Math.round((event.loaded / event.total) * 15);
                                    setBackupImportProgress(Math.max(3, uploaded), 'Mengunggah file...');
                                });
                            }
                            return xhr;
                        },
                        success: function(res) {
                            setBackupImportProgress(10, 'Import masuk antrean...');
                            showToast('success', res.message);
                        },
                        error: function(err) {
                            completed = true;
                            clearInterval(pollTimer);
                            const message = err.responseJSON?.message || 'Import gagal diproses.';
                            setBackupImportProgress(100, message, true);
                            showToast('error', message);
                        },
                        complete: function() {
                            if (completed) {
                                button.prop('disabled', false).text(originalText);
                            }
                        }
                    });
                });
            });

            function reset() {
                let exampleText = document.getElementById("exampleText");
                let roundingInput = document.getElementById("roundingDown");
                roundingInput.value = 1;

                if ($('#roundingDigits').val() == "1000") {
                    exampleText.innerText = "10001 is 10000; 10002 is 11000"
                } else {
                    exampleText.innerText = "10001 is 10000; 10002 is 10100"
                }
            }

            function resetBackupImportProgress() {
                $('#backup_import_progress_wrap').show();
                $('#backup_import_progress')
                    .removeClass('bg-danger')
                    .addClass('progress-bar-animated')
                    .css('width', '0%')
                    .attr('aria-valuenow', 0);
                $('#backup_import_percent').text('0%');
                $('#backup_import_status').text('Menyiapkan import...');
                $('#backup_import_summary').empty();
            }

            function setBackupImportProgress(percent, status, failed = false) {
                percent = Math.max(0, Math.min(100, parseInt(percent || 0)));
                const progress = $('#backup_import_progress');

                progress
                    .toggleClass('bg-danger', failed)
                    .toggleClass('progress-bar-animated', !failed && percent < 100)
                    .css('width', `${percent}%`)
                    .attr('aria-valuenow', percent);

                $('#backup_import_percent').text(`${percent}%`);
                $('#backup_import_status').text(status);
            }

            function renderBackupImportSummary(summary) {
                const rowsTotal = summary.rows_total ?? 0;
                const rowsProcessed = summary.rows_processed ?? 0;
                const transactionsCreated = summary.transactions_created ?? 0;
                const transactionsUpdated = summary.transactions_updated ?? 0;
                const itemsCreated = summary.items_created ?? 0;
                const productsCreated = summary.products_created ?? 0;
                const variantsCreated = summary.variants_created ?? 0;
                const skippedRows = summary.skipped_rows ?? 0;

                $('#backup_import_summary').html(`
                    <div>Baris: <b>${rowsProcessed}/${rowsTotal}</b></div>
                    <div>Transaksi baru: <b>${transactionsCreated}</b>, diperbarui: <b>${transactionsUpdated}</b></div>
                    <div>Item: <b>${itemsCreated}</b>, produk history baru: <b>${productsCreated}</b>, varian baru: <b>${variantsCreated}</b></div>
                    <div>Baris dilewati: <b>${skippedRows}</b></div>
                `);
            }
        </script>
    @endpush
@endsection
