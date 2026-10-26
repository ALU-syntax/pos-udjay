@extends('layouts.app')

@section('content')
    @include('layouts.reports.partials.ui-styles')

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Order Table</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-ticket-alt"></i></span>
                    Voucher
                </h1>
                <p class="ob-header__desc">Kelola kode voucher yang bisa dipakai pelanggan saat checkout.</p>
            </div>
            @can('create order-table/vouchers')
                <a href="{{ route('order-table/vouchers/create') }}" class="btn ob-btn ob-btn-primary action">
                    <i class="fas fa-plus" aria-hidden="true"></i> Tambah Voucher
                </a>
            @endcan
        </div>

        @if ($canManageGlobal)
            <div class="ob-notice" role="note">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <span>Perubahan voucher <strong>global</strong> langsung berlaku di seluruh outlet aktif.</span>
            </div>
        @endif

        {{-- Tabel --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Daftar Voucher</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Status memperhitungkan status aktif, periode berlaku, dan kuota.</span>
                </div>
                <div class="ob-field ob-list-filter">
                    <label for="outlet-filter"><i class="fas fa-store" aria-hidden="true"></i> Outlet</label>
                    <select id="outlet-filter" class="form-control">
                        <option value="">Semua voucher</option>
                        @if ($canManageGlobal)<option value="global">Hanya voucher global</option>@endif
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
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
                const tableId = 'voucher-table';
                const selectorUrls = { category: @json(route('order-table/selectors/categories')), product: @json(route('order-table/selectors/products')) };
                $('#outlet-filter').select2({ width: '100%', minimumResultsForSearch: 8 })
                    .on('change', () => window.LaravelDataTables[tableId].ajax.reload());
                handleAction(tableId, function () {
                    const form = $('#form_action');
                    const target = form.find('#scope_ids');
                    function updateType() {
                        const percent = form.find('#type').val() === 'percent';
                        form.find('.value-prefix').text(percent ? '%' : 'Rp');
                        form.find('.max-discount-field').toggleClass('d-none', !percent).find('input').prop('disabled', !percent);
                    }
                    function initTarget(clear) {
                        const scope = form.find('#scope').val();
                        const outlet = form.find('#outlet_id').val();
                        const global = outlet === '';
                        form.find('#scope option[value="category"], #scope option[value="product"]').prop('disabled', global);
                        if (global && scope !== 'all') {
                            form.find('#scope').val('all');
                        }
                        const effectiveScope = form.find('#scope').val();
                        if (target.hasClass('select2-hidden-accessible')) target.select2('destroy');
                        if (clear) target.empty();
                        form.find('.scope-target-field').toggleClass('d-none', effectiveScope === 'all');
                        target.prop('disabled', effectiveScope === 'all');
                        if (effectiveScope === 'all') return;
                        target.select2({ dropdownParent: $('#modal_action'), width: '100%', placeholder: 'Pilih target', ajax: { url: selectorUrls[effectiveScope], dataType: 'json', delay: 250, data: params => ({ q: params.term, outlet_id: outlet }), processResults: response => ({ results: response.data || [] }) } });
                    }
                    function updateGlobalWarning() { form.find('.global-impact-warning').toggleClass('d-none', form.find('#outlet_id').val() !== ''); }
                    form.on('input', '#code', function () { this.value = this.value.toUpperCase(); });
                    form.on('change', '#type', updateType);
                    form.on('change', '#scope, #outlet_id', function () { initTarget(true); updateGlobalWarning(); });
                    updateType(); initTarget(false); updateGlobalWarning();
                });
                $('.main-content').on('click', '.toggle-voucher', function (event) {
                    event.preventDefault(); const button = this;
                    Swal.fire({ title: 'Ubah status voucher?', text: 'Perubahan langsung memengaruhi voucher yang dapat digunakan pelanggan.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, ubah status', cancelButtonText: 'Batal' }).then(function (result) {
                        if (!result.isConfirmed) return;
                        $.post(button.href, { _token: @json(csrf_token()) }).done(function (response) { showToast(response.status || 'success', response.message || 'Status voucher diperbarui'); window.LaravelDataTables[tableId].ajax.reload(null, false); }).fail(xhr => showToast('error', xhr.responseJSON?.message || 'Gagal memperbarui voucher'));
                    });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            /* Sel voucher: kode bergaya tiket + manfaat */
            .ob-voucher-cell,
            .ob-cell-stack {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: .2rem;
            }

            .ob-voucher-cell {
                min-width: 180px;
            }

            .ob-voucher-code {
                padding: .25rem .6rem;
                border: 1px dashed var(--ob-primary);
                border-radius: 6px;
                background: var(--ob-primary-soft);
                color: var(--ob-primary);
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                font-size: .82rem;
                font-weight: 700;
                letter-spacing: .04em;
            }

            .ob-cell-meta {
                font-size: .75rem;
                color: var(--ob-muted);
            }

            .ob-cell-stack {
                white-space: nowrap;
                font-variant-numeric: tabular-nums;
            }

            .ob-quota {
                min-width: 120px;
            }

            .ob-quota__bar {
                display: block;
                width: 100%;
                height: 5px;
                border-radius: 999px;
                background: var(--ob-border);
                overflow: hidden;
            }

            .ob-quota__fill {
                display: block;
                height: 100%;
                border-radius: inherit;
            }

            .ob-quota__fill--primary { background: var(--ob-primary); }
            .ob-quota__fill--warning { background: #d97706; }
            .ob-quota__fill--danger { background: var(--ob-danger); }

            .ob-page table.dataTable tbody td.ob-col-time {
                white-space: nowrap;
                font-size: .8rem;
            }
        </style>
    @endpush
@endsection
