@extends('layouts.app')

@section('content')
    @php
        $outlets = App\Models\Outlets::query()->whereIn('id', auth()->user()->outletIds())->orderBy('name')->get();
    @endphp
    <div class="main-content order-table-page">
        <div class="mb-4"><h2 class="h4 mb-1 font-weight-bold"><i class="fa fa-receipt me-2"></i>Monitor Penukaran Voucher</h2><p class="text-muted mb-0">Riwayat audit penukaran voucher. Data ini hanya dapat dibaca.</p></div>
        <div class="alert alert-info border-0 shadow-sm"><i class="fa fa-lock me-2"></i>Monitor bersifat read-only. Tidak tersedia aksi ubah atau hapus.</div>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><h5 class="mb-3">Riwayat Penukaran</h5><div class="row g-2">
                <div class="col-md-3"><label for="outlet-filter" class="form-label small mb-1">Outlet</label><select id="outlet-filter" class="form-select form-select-sm"><option value="">Semua outlet</option>@foreach ($outlets as $outlet)<option value="{{ $outlet->id }}">{{ $outlet->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label for="voucher-filter" class="form-label small mb-1">Voucher</label><select id="voucher-filter" class="form-select form-select-sm"><option value="">Semua voucher</option>@foreach (($vouchers ?? []) as $voucher)<option value="{{ $voucher->id }}">{{ $voucher->code }}</option>@endforeach</select></div>
                <div class="col-md-2"><label for="date-from-filter" class="form-label small mb-1">Dari tanggal</label><input id="date-from-filter" type="date" class="form-control form-control-sm"></div>
                <div class="col-md-2"><label for="date-to-filter" class="form-label small mb-1">Sampai tanggal</label><input id="date-to-filter" type="date" class="form-control form-control-sm"></div>
                <div class="col-md-2"><label for="order-filter" class="form-label small mb-1">Nomor order</label><input id="order-filter" type="search" class="form-control form-control-sm" placeholder="Cari order"></div>
            </div></div>
            <div class="card-body"><div class="table-responsive">{!! $dataTable->table(['class' => 'table table-hover align-middle mb-0']) !!}</div></div>
        </div>
    </div>
    @push('js')
        {!! $dataTable->scripts() !!}
        <script>$(function(){const table=window.LaravelDataTables['voucher-redemption-table'];let timer;$('#outlet-filter,#voucher-filter,#date-from-filter,#date-to-filter').on('change',()=>table.ajax.reload());$('#order-filter').on('input',function(){clearTimeout(timer);timer=setTimeout(()=>table.ajax.reload(),350);});});</script>
    @endpush
    @push('css')<style>.order-table-page .card{border-radius:10px}</style>@endpush
@endsection
