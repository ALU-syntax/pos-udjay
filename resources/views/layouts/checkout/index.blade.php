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
        </script>
    @endpush
@endsection
