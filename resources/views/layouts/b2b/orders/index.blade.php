@extends('layouts.app')

@section('content')
    @include('layouts.b2b.partials.assets')

    <div class="main-content ob-page">
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Kasir B2B</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-shopping-bag"></i></span>
                    Pesanan B2B
                </h1>
                <p class="ob-header__desc">Pesanan dari Kasir B2B pada outlet yang bisa Anda akses.</p>
            </div>
            @can('read b2b/cashier')
                <a href="{{ route('b2b/cashier') }}" class="btn ob-btn ob-btn-primary"><i class="fas fa-cash-register" aria-hidden="true"></i> Kasir B2B</a>
            @endcan
        </div>

        <div class="ob-card">
            <div class="ob-tabs-bar">
                <h2 class="ob-section-title mb-0"><i class="fas fa-list" aria-hidden="true"></i> Daftar Pesanan</h2>
                <div class="ob-list-filters d-flex flex-wrap gap-2">
                    <div class="ob-field ob-list-filter">
                        <label for="outlet-filter"><i class="fas fa-store" aria-hidden="true"></i> Outlet</label>
                        <select id="outlet-filter" class="form-control">
                            <option value="">Semua outlet</option>
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ob-field ob-list-filter">
                        <label for="status-filter"><i class="fas fa-filter" aria-hidden="true"></i> Status</label>
                        <select id="status-filter" class="form-control">
                            <option value="">Semua status</option>
                            <option value="confirmed">Dikonfirmasi</option>
                            <option value="completed">Selesai</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>
                    <div class="ob-field ob-list-filter">
                        <label for="date-from"><i class="far fa-calendar" aria-hidden="true"></i> Dari</label>
                        <input id="date-from" type="date" class="form-control">
                    </div>
                    <div class="ob-field ob-list-filter">
                        <label for="date-to"><i class="far fa-calendar" aria-hidden="true"></i> Sampai</label>
                        <input id="date-to" type="date" class="form-control">
                    </div>
                </div>
            </div>
            <div class="ob-table-wrap">
                {!! $dataTable->table(['class' => 'table w-100']) !!}
            </div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            $(function () {
                const reload = () => window.LaravelDataTables['b2b-order-table'].ajax.reload();
                $('#outlet-filter, #status-filter').on('change', reload);
                $('#date-from, #date-to').on('change', function () {
                    if (($('#date-from').val() && $('#date-to').val()) || (!$('#date-from').val() && !$('#date-to').val())) reload();
                });
            });
        </script>
    @endpush
@endsection
