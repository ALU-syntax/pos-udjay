@extends('layouts.app')

@section('content')
    @include('layouts.b2b.partials.assets')

    <div class="main-content ob-page">
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Kasir B2B</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-file-invoice"></i></span>
                    Invoice B2B
                </h1>
                <p class="ob-header__desc">Invoice dibuat otomatis saat pesanan dikonfirmasi. Pencatatan pembayaran menyusul.</p>
            </div>
        </div>

        <div class="ob-card">
            <div class="ob-tabs-bar">
                <h2 class="ob-section-title mb-0"><i class="fas fa-list" aria-hidden="true"></i> Daftar Invoice</h2>
                <div class="ob-field ob-list-filter">
                    <label for="status-filter"><i class="fas fa-filter" aria-hidden="true"></i> Status</label>
                    <select id="status-filter" class="form-control">
                        <option value="">Semua invoice</option>
                        <option value="open" selected>Belum lunas</option>
                        <option value="overdue">Lewat jatuh tempo</option>
                        <option value="paid">Lunas</option>
                        <option value="void">Dibatalkan</option>
                    </select>
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
                $('#status-filter').on('change', () => window.LaravelDataTables['b2b-invoice-table'].ajax.reload());
            });
        </script>
    @endpush
@endsection
