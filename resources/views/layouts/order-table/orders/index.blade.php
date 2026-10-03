@extends('layouts.app')

@section('content')
    <div class="main-content order-table-page">
        <div class="mb-4">
            <h2 class="h4 mb-1 font-weight-bold"><i class="fa fa-clipboard-list me-2"></i>Monitor Order</h2>
            <p class="text-muted mb-0">Pantau order Order Table. Data harga, pembayaran, dan bridge bersifat read-only.</p>
        </div>

        @unless ($nodeEnabled)
            <div class="alert alert-warning border-0 shadow-sm">
                <i class="fa fa-plug me-2"></i>Aksi <strong>Tandai Disajikan</strong> dan <strong>Batalkan Order</strong> dinonaktifkan sampai integrasi layanan Node diaktifkan.
            </div>
        @endunless

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-3">Daftar Order</h5>
                <div class="row g-2">
                    <div class="col-md-2"><label for="outlet-filter" class="form-label small mb-1">Outlet</label><select id="outlet-filter" class="form-select form-select-sm"><option value="">Semua</option>@foreach ($outlets as $outlet)<option value="{{ $outlet->id }}">{{ $outlet->name }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label for="status-filter" class="form-label small mb-1">Status</label><select id="status-filter" class="form-select form-select-sm"><option value="">Semua</option>@foreach (['draft','placed','received','preparing','served','cancelled','expired'] as $status)<option value="{{ $status }}">{{ $status }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label for="payment-status-filter" class="form-label small mb-1">Pembayaran</label><select id="payment-status-filter" class="form-select form-select-sm"><option value="">Semua</option>@foreach (['unpaid','pending','paid','failed','expired','refunded'] as $status)<option value="{{ $status }}">{{ $status }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label for="payment-mode-filter" class="form-label small mb-1">Mode</label><select id="payment-mode-filter" class="form-select form-select-sm"><option value="">Semua</option><option value="qris">qris</option><option value="pay_at_cashier">pay_at_cashier</option></select></div>
                    <div class="col-md-2"><label for="bridge-status-filter" class="form-label small mb-1">Bridge</label><select id="bridge-status-filter" class="form-select form-select-sm"><option value="">Semua</option><option value="pending">pending</option><option value="linked">linked</option><option value="failed">failed</option></select></div>
                    <div class="col-md-2"><label for="order-filter" class="form-label small mb-1">Nomor order</label><input id="order-filter" type="search" class="form-control form-control-sm" placeholder="Cari order"></div>
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
                const table = window.LaravelDataTables['order-monitor-table'];
                let timer;
                $('#outlet-filter,#status-filter,#payment-status-filter,#payment-mode-filter,#bridge-status-filter,#date-from-filter,#date-to-filter').on('change', () => table.ajax.reload());
                $('#order-filter').on('input', function () { clearTimeout(timer); timer = setTimeout(() => table.ajax.reload(), 350); });
            });
        </script>
    @endpush
    @push('css')<style>.order-table-page .card{border-radius:10px}.order-table-actions{display:inline-flex;gap:5px}</style>@endpush
@endsection
