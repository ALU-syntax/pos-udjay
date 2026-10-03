@extends('layouts.app')

@section('content')
    <div class="main-content order-table-page">
        <div class="mb-4">
            <h2 class="h4 mb-1 font-weight-bold"><i class="fa fa-credit-card me-2"></i>Monitor Pembayaran</h2>
            <p class="text-muted mb-0">Data pembayaran Order Table bersifat read-only. Credential dan signature tidak ditampilkan.</p>
        </div>
        <div class="alert alert-info border-0 shadow-sm"><i class="fa fa-lock me-2"></i>Nominal, referensi gateway, callback, dan status pembayaran tidak dapat diubah dari CMS.</div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-3">Daftar Pembayaran</h5>
                <div class="row g-2">
                    <div class="col-md-3"><label for="outlet-filter" class="form-label small mb-1">Outlet</label><select id="outlet-filter" class="form-select form-select-sm"><option value="">Semua</option>@foreach ($outlets as $outlet)<option value="{{ $outlet->id }}">{{ $outlet->name }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label for="method-filter" class="form-label small mb-1">Metode</label><select id="method-filter" class="form-select form-select-sm"><option value="">Semua</option><option value="qris">qris</option><option value="cashier">cashier</option><option value="gateway">gateway</option></select></div>
                    <div class="col-md-2"><label for="status-filter" class="form-label small mb-1">Status</label><select id="status-filter" class="form-select form-select-sm"><option value="">Semua</option><option value="pending">pending</option><option value="paid">paid</option><option value="failed">failed</option><option value="expired">expired</option></select></div>
                    <div class="col-md-3"><label for="order-filter" class="form-label small mb-1">Nomor order</label><input id="order-filter" type="search" class="form-control form-control-sm" placeholder="Cari order"></div>
                    <div class="col-md-2"><label for="date-from-filter" class="form-label small mb-1">Dari</label><input id="date-from-filter" type="date" class="form-control form-control-sm"></div>
                    <div class="col-md-2"><label for="date-to-filter" class="form-label small mb-1">Sampai</label><input id="date-to-filter" type="date" class="form-control form-control-sm"></div>
                </div>
            </div>
            <div class="card-body"><div class="table-responsive">{!! $dataTable->table(['class' => 'table table-hover align-middle mb-0']) !!}</div></div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            $(function () {
                const table = window.LaravelDataTables['payment-monitor-table'];
                let timer;
                $('#outlet-filter,#method-filter,#status-filter,#date-from-filter,#date-to-filter').on('change', () => table.ajax.reload());
                $('#order-filter').on('input', function () { clearTimeout(timer); timer = setTimeout(() => table.ajax.reload(), 350); });
            });
        </script>
    @endpush
    @push('css')<style>.order-table-page .card{border-radius:10px}</style>@endpush
@endsection
