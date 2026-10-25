@extends('layouts.app')

@section('content')
    @include('layouts.reports.partials.ui-styles')

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Order Table</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-image"></i></span>
                    Banner
                </h1>
                <p class="ob-header__desc">Atur materi promosi yang tampil di halaman menu pelanggan.</p>
            </div>
            @can('create order-table/banners')
                <a href="{{ route('order-table/banners/create') }}" class="btn ob-btn ob-btn-primary action">
                    <i class="fas fa-plus" aria-hidden="true"></i> Tambah Banner
                </a>
            @endcan
        </div>

        @if ($canManageGlobal)
            <div class="ob-notice" role="note">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <span>Perubahan banner <strong>global</strong> langsung tampil di seluruh outlet aktif.</span>
            </div>
        @endif

        {{-- Tabel --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Daftar Banner</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Status memperhitungkan status aktif dan periode tayang.</span>
                </div>
                <div class="ob-field ob-banner-filter">
                    <label for="outlet-filter"><i class="fas fa-store" aria-hidden="true"></i> Outlet</label>
                    <select id="outlet-filter" class="form-control">
                        <option value="">Semua banner</option>
                        @if ($canManageGlobal)<option value="global">Hanya banner global</option>@endif
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
                const tableId = 'banner-table';
                const selectorUrls = { product: @json(route('order-table/selectors/products')), category: @json(route('order-table/selectors/categories')), voucher: @json(route('order-table/selectors/vouchers')) };
                $('#outlet-filter').select2({ width: '100%', minimumResultsForSearch: 8 })
                    .on('change', () => window.LaravelDataTables[tableId].ajax.reload());
                handleAction(tableId, function () {
                    const form = $('#form_action'); const target = form.find('#action_target');
                    function initTarget(clear) {
                        const global = form.find('#outlet_id').val() === '';
                        form.find('#action_type option[value="product"], #action_type option[value="category"]').prop('disabled', global);
                        if (global && !['none', 'url', 'voucher'].includes(form.find('#action_type').val())) {
                            form.find('#action_type').val('none');
                        }
                        const effectiveType = form.find('#action_type').val();
                        const isUrl = effectiveType === 'url';
                        const effectiveSelectable = Object.hasOwn(selectorUrls, effectiveType);
                        if (target.hasClass('select2-hidden-accessible')) target.select2('destroy');
                        if (clear) target.empty();
                        form.find('.action-url-field').toggleClass('d-none', !isUrl).find('input').prop('disabled', !isUrl);
                        form.find('.action-target-field').toggleClass('d-none', !effectiveSelectable);
                        target.prop('disabled', !effectiveSelectable);
                        if (!effectiveSelectable) return;
                        target.select2({ dropdownParent: $('#modal_action'), width: '100%', placeholder: 'Pilih target', ajax: { url: selectorUrls[effectiveType], dataType: 'json', delay: 250, data: params => ({ q: params.term, outlet_id: form.find('#outlet_id').val() }), processResults: response => ({ results: response.data || [] }) } });
                    }
                    function preview(src) { form.find('.banner-image-preview').attr('src', src).toggleClass('d-none', !src); form.find('.banner-image-placeholder').toggleClass('d-none', !!src); }
                    function updateDisplayMode() {
                        const text = form.find('#display_mode').val() === 'text'; const title = form.find('#title');
                        form.find('.banner-text-only').toggleClass('d-none', !text).find('input, select').prop('disabled', !text);
                        form.find('.banner-image-only').toggleClass('d-none', text);
                        title.attr('maxlength', text ? title.data('text-maxlength') : 255);
                    }
                    function updateGlobalWarning() { form.find('.global-impact-warning').toggleClass('d-none', form.find('#outlet_id').val() !== ''); }
                    form.on('change', '#action_type, #outlet_id', function () { initTarget(true); updateGlobalWarning(); });
                    form.on('change', '#display_mode', updateDisplayMode);
                    form.on('input', '#image_url', function () { if (this.value) form.find('#image').val(''); preview(this.value); });
                    form.on('change', '#image', function () { if (this.files[0]) { form.find('#image_url').val(''); preview(URL.createObjectURL(this.files[0])); } });
                    initTarget(false); updateGlobalWarning(); updateDisplayMode();
                });
                $('.main-content').on('click', '.toggle-banner', function (event) {
                    event.preventDefault(); const button = this;
                    Swal.fire({ title: 'Ubah status banner?', text: 'Perubahan langsung memengaruhi banner yang dilihat pelanggan.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, ubah status', cancelButtonText: 'Batal' }).then(function (result) {
                        if (!result.isConfirmed) return;
                        $.post(button.href, { _token: @json(csrf_token()) }).done(function (response) { showToast(response.status || 'success', response.message || 'Status banner diperbarui'); window.LaravelDataTables[tableId].ajax.reload(null, false); }).fail(xhr => showToast('error', xhr.responseJSON?.message || 'Gagal memperbarui banner'));
                    });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .ob-notice {
                display: flex;
                align-items: center;
                gap: .65rem;
                margin-bottom: 1.25rem;
                padding: .8rem 1rem;
                border: 1px solid #fde68a;
                border-radius: var(--ob-radius-sm);
                background: #fffbeb;
                color: #92400e;
                font-size: .875rem;
            }

            /* badge-primary tidak punya titik di partial bersama (dipakai Open Bill untuk nama outlet) */
            .ob-page .badge.ob-badge-dot::before {
                content: "";
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: currentColor;
            }

            .ob-banner-filter {
                width: 260px;
                max-width: 100%;
            }

            /* Sel banner: thumbnail 16:9 + judul + jenis */
            .ob-banner-cell {
                display: flex;
                align-items: center;
                gap: .85rem;
                min-width: 240px;
            }

            .ob-banner-thumb {
                display: inline-grid;
                place-items: center;
                flex-shrink: 0;
                width: 96px;
                height: 54px;
                border-radius: 8px;
                border: 1px solid var(--ob-border);
                object-fit: cover;
                font-weight: 800;
                font-size: .95rem;
            }

            /* Warna sama dengan tone di PWA Order Table */
            .ob-banner-thumb--black { background: #000; color: #fff; }
            .ob-banner-thumb--red { background: #c72129; color: #fff; }
            .ob-banner-thumb--coffee { background: #943e0f; color: #fff; }
            .ob-banner-thumb--yellow { background: #fdf100; color: #000; }

            .ob-banner-thumb--empty {
                background: var(--ob-surface-2);
                color: var(--ob-muted);
            }

            .ob-banner-cell__title {
                font-weight: 600;
                color: var(--ob-text);
                overflow-wrap: anywhere;
            }

            .ob-banner-cell__meta,
            .ob-target__type {
                font-size: .75rem;
                color: var(--ob-muted);
            }

            .ob-target {
                display: flex;
                flex-direction: column;
                gap: .1rem;
            }

            .ob-page table.dataTable tbody td.ob-col-time {
                white-space: nowrap;
                font-size: .8rem;
            }

            .ob-page table.dataTable th.ob-col-order,
            .ob-page table.dataTable td.ob-col-order {
                text-align: center;
                font-variant-numeric: tabular-nums;
            }

            /* Aksi per baris */
            .ob-row-actions {
                display: inline-flex;
                gap: .35rem;
            }

            .ob-page .ob-icon-btn--sm {
                width: 34px;
                height: 34px;
                border-radius: 9px;
                font-size: .8rem;
                text-decoration: none;
            }

            .ob-page .ob-icon-btn--sm:hover {
                background: var(--ob-primary-soft);
                color: var(--ob-primary);
            }

            .ob-page .ob-icon-btn--danger:hover {
                background: var(--ob-danger-soft);
                color: var(--ob-danger);
            }

            .ob-page .ob-icon-btn--success:hover {
                background: var(--ob-success-soft);
                color: var(--ob-success);
            }

            .ob-page .ob-icon-btn:focus-visible {
                outline: 2px solid var(--ob-primary);
                outline-offset: 2px;
            }

            @media (max-width: 575.98px) {
                .ob-header > .ob-btn,
                .ob-banner-filter {
                    width: 100%;
                    justify-content: center;
                }
            }
        </style>
    @endpush
@endsection
