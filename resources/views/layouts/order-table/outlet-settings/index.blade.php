@extends('layouts.app')

@section('content')
    @include('layouts.reports.partials.ui-styles')

    @php
        $outlets = App\Models\Outlets::query()
            ->whereIn('id', auth()->user()->outletIds())
            ->orderBy('name')
            ->get();
    @endphp
    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Order Table</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-store"></i></span>
                    Pengaturan Order Outlet
                </h1>
                <p class="ob-header__desc">Atur ketersediaan order, stok, jam operasional, biaya layanan, dan geofence outlet.</p>
            </div>
            @can('create order-table/outlet-settings')
                <a href="{{ route('order-table/outlet-settings/create') }}" class="btn ob-btn ob-btn-primary action">
                    <i class="fas fa-plus" aria-hidden="true"></i> Tambah Pengaturan
                </a>
            @endcan
        </div>

        <div class="ob-notice" role="note">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <span>Perubahan pause, jam operasional, dan geofence langsung memengaruhi pelanggan Order Table.</span>
        </div>

        {{-- Domain halaman order --}}
        @can('update order-table/outlet-settings')
            <div class="ob-card">
                <div class="ob-tabs-bar">
                    <div>
                        <h2 class="ob-section-title mb-1"><i class="fas fa-globe" aria-hidden="true"></i> Domain Halaman Order (Global)</h2>
                        <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Base URL yang dipakai saat membuat QR meja. Berlaku untuk semua outlet.</span>
                    </div>
                </div>
                <form id="public-url-form" action="{{ route('order-table/outlet-settings/public-url') }}" method="POST" class="ob-url-form">
                    @csrf
                    @method('put')
                    <div class="ob-field">
                        <label for="public_url"><i class="fas fa-link" aria-hidden="true"></i> Base URL</label>
                        <div class="ob-url-form__row">
                            <input id="public_url" name="public_url" type="url" class="form-control"
                                value="{{ old('public_url', $publicUrl['value']) }}"
                                placeholder="https://order.example.com">
                            <button type="submit" class="btn ob-btn ob-btn-primary">
                                <i class="fas fa-save" aria-hidden="true"></i> Simpan Base URL
                            </button>
                        </div>
                        <small class="ob-url-form__help">
                            Kosongkan untuk memakai domain backoffice ({{ $publicUrl['fallback'] }}).
                            URL QR saat ini: <span class="text-break">{{ $publicUrl['resolved'] }}</span>
                        </small>
                    </div>
                </form>
            </div>
        @endcan

        {{-- Tabel --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Konfigurasi per Outlet</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Status efektif memperhitungkan enabled, pause manual, dan jam operasional.</span>
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
        <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
        {!! $dataTable->scripts() !!}
        <script>
            $(function () {
                const tableId = 'outlet-setting-table';

                $('#outlet-filter').select2({ width: '100%', minimumResultsForSearch: 8 })
                    .on('change', () => window.LaravelDataTables[tableId].ajax.reload());

                // Peta dan input koordinat saling sinkron; input tetap jadi nilai yang dikirim form
                function initLocationPicker() {
                    const form = $('#form_action');
                    const container = form.find('#outlet-location-map');
                    if (!container.length || !window.L) return;

                    const modal = $('#modal_action');
                    const latitude = form.find('#latitude');
                    const longitude = form.find('#longitude');
                    const radius = form.find('#geofence_radius_m');

                    function readPoint() {
                        const lat = parseFloat(latitude.val());
                        const lng = parseFloat(longitude.val());
                        const valid = Math.abs(lat) <= 90 && Math.abs(lng) <= 180;

                        return valid ? L.latLng(lat, lng) : null;
                    }

                    const initialPoint = readPoint();
                    // Wheel zoom dimatikan agar scroll di atas peta tetap menggulir form di modal
                    const map = L.map(container.empty()[0], { scrollWheelZoom: false })
                        .setView(initialPoint || [-2.5, 118], initialPoint ? 17 : 5);
                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
                    }).addTo(map);

                    const marker = L.marker([0, 0], { draggable: true, keyboard: true, title: 'Titik outlet' });
                    const circle = L.circle([0, 0], { radius: 1, color: '#1572e8', weight: 2, fillOpacity: .12, interactive: false });

                    function render() {
                        const point = readPoint();
                        const meters = parseFloat(radius.val());

                        if (!point) {
                            marker.remove();
                            circle.remove();
                            return;
                        }

                        marker.setLatLng(point).addTo(map);
                        circle.setLatLng(point).setRadius(meters > 0 ? meters : 1).addTo(map);
                    }

                    function setPoint(latLng) {
                        const point = latLng.wrap();
                        latitude.val(point.lat.toFixed(7));
                        longitude.val(point.lng.toFixed(7));
                        render();
                    }

                    map.on('click', event => setPoint(event.latlng));
                    marker.on('drag', event => circle.setLatLng(event.latlng));
                    marker.on('dragend', () => setPoint(marker.getLatLng()));
                    form.on('input', '#latitude, #longitude, #geofence_radius_m', render);
                    form.on('change', '#latitude, #longitude', function () {
                        const point = readPoint();
                        if (point) map.panTo(point);
                    });

                    render();
                    modal.one('shown.bs.modal', () => map.invalidateSize());
                    modal.one('hidden.bs.modal', () => map.remove());
                }

                handleAction(tableId, initLocationPicker);

                $('#public-url-form').on('submit', function (event) {
                    event.preventDefault();
                    const form = this;

                    $.ajax({
                        url: form.action,
                        method: 'POST',
                        data: $(form).serialize(),
                        success: function (response) {
                            showToast(response.status || 'success', response.message || 'Base URL QR diperbarui');
                            window.location.reload();
                        },
                        error: function (xhr) {
                            const errors = xhr.responseJSON?.errors;
                            const firstError = errors ? Object.values(errors)[0]?.[0] : null;
                            showToast('error', firstError || xhr.responseJSON?.message || 'Gagal menyimpan base URL');
                        }
                    });
                });

                $('.main-content').on('click', '.toggle-outlet-order', function (event) {
                    event.preventDefault();
                    const button = this;
                    const pausing = $(button).data('paused') !== 1;

                    Swal.fire({
                        title: pausing ? 'Pause order outlet?' : 'Buka kembali order?',
                        text: pausing
                            ? 'Pelanggan tidak dapat membuat order baru sampai outlet dibuka kembali.'
                            : 'Outlet akan kembali menerima order sesuai jam operasional.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: pausing ? 'Ya, pause order' : 'Ya, buka order',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: button.href,
                            method: 'POST',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function (response) {
                                showToast(response.status || 'success', response.message || 'Status outlet diperbarui');
                                window.LaravelDataTables[tableId].ajax.reload(null, false);
                            },
                            error: function (xhr) {
                                showToast('error', xhr.responseJSON?.message || 'Gagal memperbarui status outlet');
                            }
                        });
                    });
                });
            });
        </script>
    @endpush

    @push('css')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
        <style>
            /* Peta pemilih koordinat; isolation menjaga z-index Leaflet tetap di dalam peta */
            .ob-map {
                display: grid;
                place-items: center;
                height: 320px;
                border: 1px solid var(--ob-border-strong);
                border-radius: var(--ob-radius-sm);
                background: var(--ob-surface-2);
                overflow: hidden;
                isolation: isolate;
            }

            .ob-map.leaflet-container {
                display: block;
            }

            .ob-map__fallback {
                padding: 1rem;
                color: var(--ob-muted);
                font-size: .85rem;
                text-align: center;
            }

            /* Form base URL di luar modal: input setinggi tombol, bantuan di bawahnya */
            .ob-url-form {
                padding: 1rem 1.25rem 1.25rem;
            }

            .ob-url-form__row {
                display: flex;
                flex-wrap: wrap;
                gap: .75rem;
            }

            .ob-url-form .form-control {
                flex: 1 1 280px;
                min-width: 0;
                min-height: 42px;
                border: 1px solid var(--ob-border-strong);
                border-radius: var(--ob-radius-sm);
                background-color: var(--ob-surface);
                color: var(--ob-text);
                font-size: .875rem !important;
                box-shadow: none;
                transition: border-color .15s ease, box-shadow .15s ease;
            }

            .ob-url-form .form-control:focus {
                border-color: var(--ob-primary);
                box-shadow: 0 0 0 3px rgba(21, 114, 232, .15);
            }

            .ob-url-form__help {
                display: block;
                margin-top: .5rem;
                font-size: .78rem;
                color: var(--ob-muted);
            }

            /* Sel koordinat + radius */
            .ob-cell-stack {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: .2rem;
                white-space: nowrap;
                font-variant-numeric: tabular-nums;
            }

            .ob-page table.dataTable tbody td.ob-col-time {
                white-space: nowrap;
                font-size: .8rem;
            }

            @media (max-width: 575.98px) {
                .ob-url-form__row .ob-btn {
                    width: 100%;
                    justify-content: center;
                }
            }
        </style>
    @endpush
@endsection
