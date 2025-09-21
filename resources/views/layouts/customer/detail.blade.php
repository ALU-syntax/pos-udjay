@extends('layouts.app')
@section('content')
    <div class="main-content">
        <div class="card text-center">
            <h5 class="card-header">Detail {{ $data->name }}</h5>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row row-card-no-pd mt-3">
                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-5">
                                        <div class="icon-big text-center">
                                            <i class="far fa-star"></i>
                                        </div>
                                    </div>
                                    <div class="col-7 col-stats">
                                        <div class="numbers">
                                            <p class="card-category">Total Point</p>
                                            <h4 class="card-title" id="point">{{ $data->point }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-5">
                                        <div class="icon-big text-center">
                                            <i class="fab fa-viacoin"></i>
                                        </div>
                                    </div>
                                    <div class="col-7 col-stats">
                                        <div class="numbers">
                                            <p class="card-category">Total Exp</p>
                                            <h4 class="card-title" id="exp">{{ $data->exp }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-5">
                                        <div class="icon-big text-center">
                                            <i class="fas fa-coins text-primary"></i>
                                        </div>
                                    </div>
                                    <div class="col-7 col-stats">
                                        <div class="numbers">
                                            <p class="card-category">Jumlah Transaksi</p>
                                            <h4 class="card-title" id="count-transaction">{{ count($data->transactions) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-5">
                                        <div class="icon-big text-center">
                                            <i class="icon-wallet text-success"></i>
                                        </div>
                                    </div>
                                    <div class="col-7 col-stats">
                                        <div class="numbers">
                                            <p class="card-category">Jumlah Nominal Transaksi</p>
                                            <h4 class="card-title" id="transaction-nominal">{{ formatRupiah(strval($transactionNominal), "Rp. ") }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if (session()->has('success'))
            <div class="alert alert-success mt-2" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger mt-2" role="alert">
                {{ session('error') }}
            </div>
        @endif
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-end">
                <a href="{{ route('membership/community/historyUseExp', $data->id) }}" type="button" id="btnHistoryUseExp"
                    class="btn btn-primary">History Use Exp</a>
                <a href="{{ route('membership/community/createExchangeExp', $data->id) }}" type="button"
                    class="btn btn-primary btn-round ms-3 me-2 action">Use Exp</a>

            </div>
            <div class="card-body">
                <div class="table-responsive">
                    {!! $dataTable->table() !!}
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal_history_exp" tabindex="-1" role="dialog" aria-hidden="true">
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}

        <script>
            var success = "{{ session('success') }}";
            var datatable = 'listcustomertransaction-table';

            handleAction(datatable);
            handleDelete(datatable, false, function(res){
                console.log(res);

                $('#point').text(res.data.point);
                $('#exp').text(res.data.exp);
                $('#count-transaction').text(res.data.transactions.length);
                $('#transaction-nominal').text(formatRupiah(res.transactionNominal.toString(), "Rp. "));
            });

            $('#btnHistoryUseExp').on('click', function(e) {
                e.preventDefault();
                $.ajax({
                    url: this.href,
                    method: "GET",
                    beforeSend: function() {
                        showLoader();
                    },
                    complete: function() {
                        showLoader(false);
                    },
                    success: (res) => {
                        const modal = $('#modal_history_exp');
                        modal.html(res);
                        modal.modal('show');
                    },
                    error: function(err) {
                        console.log(err);
                    }
                });
            });

            if (success) {
                Swal.fire({
                    title: 'Success!',
                    // text: 'Data User Berhasil Disimpan',
                    text: success,
                    icon: 'success',
                    type: 'success',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                });
            }
        </script>
    @endpush
@endsection
