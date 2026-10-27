@extends('layouts.app')

@section('content')
    @include('layouts.reports.partials.ui-styles')

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Order Table</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-qrcode"></i></span>
                    Meja &amp; QR
                </h1>
                <p class="ob-header__desc">Kelola meja, status QR, dan materi QR yang ditempatkan di outlet.</p>
            </div>
            <div class="ob-header__actions">
                @can('download order-table/dining-tables')
                    <a href="{{ route('order-table/dining-tables/qr/download-all') }}" id="download-all-qr" class="btn ob-btn ob-btn-outline"
                        title="Unduh semua QR aktif sesuai filter outlet dalam satu file ZIP">
                        <i class="fas fa-file-archive" aria-hidden="true"></i> <span>Download Semua QR</span>
                    </a>
                @endcan
                @can('create order-table/dining-tables')
                    <a href="{{ route('order-table/dining-tables/create') }}" class="btn ob-btn ob-btn-primary action">
                        <i class="fas fa-plus" aria-hidden="true"></i> Tambah Meja
                    </a>
                @endcan
            </div>
        </div>

        <div class="ob-notice" role="note">
            <i class="fas fa-shield-alt" aria-hidden="true"></i>
            <span>QR yang sudah dicetak berlaku selama meja ada dan QR-nya <strong>aktif</strong>. Butuh QR pengganti? Nonaktifkan QR lama lalu buat meja baru.</span>
        </div>

        {{-- Tabel --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Daftar Meja</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Pelanggan hanya bisa memesan dari meja dengan QR aktif.</span>
                </div>
                <div class="ob-field ob-list-filter">
                    <label for="outlet-filter"><i class="fas fa-store" aria-hidden="true"></i> Outlet</label>
                    <select id="outlet-filter" class="form-control">
                        <option value="">Semua outlet</option>
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
                const tableId = 'dining-table-table';
                const csrf = @json(csrf_token());
                const reload = () => window.LaravelDataTables[tableId].ajax.reload(null, false);

                $('#outlet-filter').select2({ width: '100%', minimumResultsForSearch: 8 })
                    .on('change', () => window.LaravelDataTables[tableId].ajax.reload());

                handleAction(tableId, function () {
                    $('#form_action').on('input', '#code', function () { this.value = this.value.toUpperCase(); });
                });

                // ZIP diunduh lewat fetch agar ada status loading dan pesan bila tidak ada QR aktif
                $('#download-all-qr').on('click', async function (event) {
                    event.preventDefault();
                    const button = $(this);
                    if (button.hasClass('disabled')) return;

                    const label = button.find('span');
                    const originalLabel = label.text();
                    const url = new URL(this.href);
                    const outletId = $('#outlet-filter').val();
                    if (outletId) url.searchParams.set('outlet_id', outletId);

                    button.addClass('disabled').attr('aria-disabled', 'true');
                    label.text('Menyiapkan ZIP...');

                    try {
                        const response = await fetch(url, { headers: { Accept: 'application/zip, application/json' } });
                        const type = response.headers.get('Content-Type') || '';

                        if (!response.ok || type.includes('application/json')) {
                            const body = type.includes('application/json') ? await response.json() : {};
                            showToast(body.status || 'error', body.message || 'Gagal mengunduh QR meja');
                            return;
                        }

                        const filename = (response.headers.get('Content-Disposition') || '').match(/filename="?([^";]+)"?/)?.[1] || 'qr-meja.zip';
                        const blobUrl = URL.createObjectURL(await response.blob());
                        const link = Object.assign(document.createElement('a'), { href: blobUrl, download: filename });
                        document.body.appendChild(link);
                        link.click();
                        link.remove();
                        URL.revokeObjectURL(blobUrl);
                    } catch (error) {
                        showToast('error', 'Gagal mengunduh QR meja');
                    } finally {
                        button.removeClass('disabled').removeAttr('aria-disabled');
                        label.text(originalLabel);
                    }
                });

                $('.main-content').on('click', '.qr-preview', function (event) {
                    event.preventDefault();
                    handleAjax(this.href).excute();
                });

                $('.main-content').on('click', '.toggle-qr', function (event) {
                    event.preventDefault();
                    const button = this;
                    const active = $(button).data('active') === 1;

                    Swal.fire({
                        title: active ? 'Nonaktifkan QR meja?' : 'Aktifkan QR meja?',
                        text: active
                            ? 'Pelanggan tidak bisa memesan dari QR meja ini sampai diaktifkan kembali.'
                            : 'Pelanggan bisa kembali memesan dari QR meja ini.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: active ? 'Ya, nonaktifkan' : 'Ya, aktifkan',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (!result.isConfirmed) return;

                        $.post(button.href, { _token: csrf })
                            .done(function (response) {
                                showToast(response.status || 'success', response.message || 'Status QR meja diperbarui');
                                reload();
                            })
                            .fail(xhr => showToast('error', xhr.responseJSON?.message || 'Gagal memperbarui QR meja'));
                    });
                });

                $('.main-content').on('click', '.delete', function (event) {
                    event.preventDefault();
                    const button = this;

                    Swal.fire({
                        title: 'Hapus meja?',
                        text: 'QR meja ini langsung tidak berlaku. Riwayat sesi dan order tidak ikut dihapus.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus meja',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (!result.isConfirmed) return;

                        $.ajax({ url: button.href, method: 'DELETE', data: { _token: csrf } })
                            .done(function (response) {
                                showToast(response.status || 'success', response.message || 'Meja berhasil dihapus');
                                reload();
                            })
                            .fail(xhr => showToast('error', xhr.responseJSON?.message || 'Gagal menghapus meja'));
                    });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .ob-header__actions {
                display: flex;
                flex-wrap: wrap;
                gap: .5rem;
            }

            @media (max-width: 575.98px) {
                .ob-header__actions,
                .ob-header__actions .ob-btn {
                    width: 100%;
                    justify-content: center;
                }
            }

            /* Sel meja: kode + nama */
            .ob-table-cell {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: .2rem;
                min-width: 160px;
            }

            .ob-table-code {
                padding: .25rem .6rem;
                border: 1px solid var(--ob-border-strong);
                border-radius: 6px;
                background: var(--ob-surface-2);
                color: var(--ob-text);
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                font-size: .82rem;
                font-weight: 700;
                letter-spacing: .04em;
            }

            .ob-cell-capacity {
                display: inline-flex;
                align-items: center;
                gap: .4rem;
                white-space: nowrap;
                color: var(--ob-text-2);
            }

            .ob-cell-capacity i {
                color: var(--ob-muted);
                font-size: .75rem;
            }

            /* Modal preview QR */
            .ob-qr-frame {
                display: grid;
                place-items: center;
                padding: 1.25rem;
                margin-bottom: 1rem;
                border: 1px solid var(--ob-border);
                border-radius: var(--ob-radius);
                background: var(--ob-surface-2);
            }

            .ob-qr-frame__code {
                padding: .75rem;
                border-radius: var(--ob-radius-sm);
                background: #fff;
                box-shadow: var(--ob-shadow);
            }

            .ob-qr-frame__code svg {
                display: block;
                width: 220px;
                height: 220px;
            }

            .ob-qr-frame.is-inactive .ob-qr-frame__code {
                opacity: .35;
                filter: grayscale(1);
            }

            .ob-qr-url {
                display: block;
                padding: .6rem .8rem;
                border: 1px dashed var(--ob-border-strong);
                border-radius: var(--ob-radius-sm);
                background: var(--ob-surface-2);
                color: var(--ob-text-2);
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                font-size: .75rem;
                overflow-wrap: anywhere;
            }
        </style>
    @endpush
@endsection
