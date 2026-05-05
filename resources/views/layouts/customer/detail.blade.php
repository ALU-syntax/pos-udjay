@extends('layouts.app')
@section('content')
    <style>
        .member-detail-layout {
            align-items: stretch;
        }

        .member-profile-card,
        .member-summary-panel {
            height: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .06);
        }

        .member-profile-card {
            padding: 22px;
        }

        .member-profile-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .member-initial-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 58px;
            width: 58px;
            height: 58px;
            border-radius: 14px;
            background: #eef4ff;
            color: #2563eb;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0;
        }

        .member-heading {
            min-width: 0;
            flex: 1;
        }

        .member-name-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 8px;
        }

        .member-name {
            margin: 0;
            color: #111827;
            font-size: 22px;
            font-weight: 800;
            line-height: 1.25;
        }

        .member-level-badge {
            display: inline-flex;
            align-items: center;
            min-height: 27px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .18);
        }

        .member-subtext-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 16px;
            color: #4b5563;
            font-size: 14px;
            line-height: 1.4;
        }

        .member-subtext-item {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-width: 0;
            max-width: 100%;
        }

        .member-subtext-item i {
            color: #2563eb;
            font-size: 13px;
        }

        .member-subtext-item span {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .member-summary-panel {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            padding: 18px;
        }

        .member-stat-item {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 92px;
            padding: 16px;
            border: 1px solid #eef2f7;
            border-radius: 8px;
            background: #fbfdff;
        }

        .member-stat-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 42px;
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: #eef4ff;
            color: #2563eb;
            font-size: 18px;
        }

        .member-stat-label {
            margin: 0 0 4px;
            color: #6b7280;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .member-stat-value {
            margin: 0;
            color: #111827;
            font-size: 20px;
            font-weight: 800;
            line-height: 1.2;
        }

        @media (max-width: 767.98px) {
            .member-profile-header {
                align-items: flex-start;
            }

            .member-profile-card {
                padding: 18px;
            }

            .member-summary-panel {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <div class="main-content">
        <div class="row member-detail-layout mt-4">
            <div class="col-lg-6 ">
                <div class="member-profile-card">
                    <div class="member-profile-header">
                        <div class="member-initial-box">{{ $customerInitials }}</div>
                        <div class="member-heading">
                            <div class="member-name-row">
                                <h4 class="member-name">{{ $data->name }}</h4>
                                <span class="member-level-badge"
                                    style="background-color: {{ $levelBadgeColor }}; color: {{ $levelBadgeTextColor }};">
                                    {{ $data->levelMembership?->name ?? '-' }}
                                </span>
                            </div>

                            <div class="member-subtext-list">
                                <span class="member-subtext-item">
                                    <i class="fas fa-envelope"></i>
                                    <span>{{ $data->email ?? '-' }}</span>
                                </span>
                                <span class="member-subtext-item">
                                    <i class="fas fa-phone"></i>
                                    <span>{{ $data->telfon ?? '-' }}</span>
                                </span>
                                <span class="member-subtext-item">
                                    <i class="fas fa-calendar-check"></i>
                                    <span>Join {{ \Carbon\Carbon::parse($data->created_at)->format('d M Y') }}</span>
                                </span>
                                <span class="member-subtext-item">
                                    <i class="fas fa-clock"></i>
                                    <span>{{ $accountAge }}</span>
                                </span>
                                <span class="member-subtext-item">
                                    <i class="fas fa-store"></i>
                                    <span>{{ $createdLocation }}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-3">
                <div class="member-summary-panel">
                    <div class="member-stat-item">
                        <span class="member-stat-icon"><i class="far fa-star"></i></span>
                        <div>
                            <p class="member-stat-label">Total Point</p>
                            <h4 class="member-stat-value" id="point">{{ $data->point }}</h4>
                        </div>
                    </div>
                    <div class="member-stat-item">
                        <span class="member-stat-icon"><i class="fab fa-viacoin"></i></span>
                        <div>
                            <p class="member-stat-label">Total Exp</p>
                            <h4 class="member-stat-value" id="exp">{{ $data->exp }}</h4>
                        </div>
                    </div>
                    <div class="member-stat-item">
                        <span class="member-stat-icon"><i class="fas fa-coins"></i></span>
                        <div>
                            <p class="member-stat-label">Jumlah Transaksi</p>
                            <h4 class="member-stat-value" id="count-transaction">{{ count($data->transactions) }}</h4>
                        </div>
                    </div>
                    <div class="member-stat-item">
                        <span class="member-stat-icon"><i class="icon-wallet"></i></span>
                        <div>
                            <p class="member-stat-label">Nominal Transaksi</p>
                            <h4 class="member-stat-value" id="transaction-nominal">{{ formatRupiah(strval($transactionNominal), "Rp. ") }}</h4>
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
