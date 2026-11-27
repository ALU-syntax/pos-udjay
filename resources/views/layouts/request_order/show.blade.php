@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')
    @include('layouts.warehouse.partials.ui-styles')

    @php
        $status = $requestOrder->status;
        $statusCode = $status?->code;
        $isDraft = $statusCode === \App\Models\RawMaterialRequestStatus::DRAFT;
        $isSubmitted = $statusCode === \App\Models\RawMaterialRequestStatus::SUBMITTED;
        $canUpdate = auth()->user()->can('update warehouse/request-order');
        $canDelete = auth()->user()->can('delete warehouse/request-order');
        $canApprove = $isSubmitted && $canUpdate;
        $isOverdue = $requestOrder->needed_at && $requestOrder->needed_at->lt(today())
            && ! in_array($statusCode, \App\Models\RawMaterialRequestStatus::CLOSED, true);

        // Tampilan: tanpa nol di belakang koma, format Indonesia (25.000 / 0,05)
        $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 5, ',', '.'), '0'), ',');
        // Nilai input: titik desimal, tanpa pembulatan (0.05 tetap 0.05)
        $plain = fn ($value) => rtrim(rtrim(number_format((float) $value, 5, '.', ''), '0'), '.');
    @endphp

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">
                    <a href="{{ route('warehouse/request-order') }}">Request Order</a><span class="wh-crumb-sep" aria-hidden="true">/</span>Detail
                </div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-clipboard-list"></i></span>
                    {{ $requestOrder->request_number }}
                </h1>
                <div class="wh-profile-meta">
                    @if ($status)
                        <span class="badge {{ $status->badge_class }}">{{ $status->label }}</span>
                    @endif
                    <span>
                        {{ $requestOrder->requesterInventory?->name ?? '-' }}
                        <i class="fas fa-long-arrow-alt-right" aria-hidden="true"></i>
                        {{ $requestOrder->fulfillmentInventory?->name ?? 'Pemenuh belum ditentukan' }}
                    </span>
                    @if ($requestOrder->needed_at)
                        <span class="{{ $isOverdue ? 'ro-overdue' : '' }}">
                            Dibutuhkan {{ $requestOrder->needed_at->translatedFormat('d M Y') }}{{ $isOverdue ? ' (lewat tanggal)' : '' }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="wh-actions">
                <a href="{{ route('warehouse/request-order') }}" class="btn ob-btn ob-btn-outline">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Kembali
                </a>
                @if ($isDraft && $canUpdate)
                    <a href="{{ route('warehouse/request-order/edit', $requestOrder->id) }}" class="btn ob-btn ob-btn-outline">
                        <i class="fas fa-pen" aria-hidden="true"></i> Edit
                    </a>
                @endif
                @if ($isDraft && $canDelete)
                    <a href="{{ route('warehouse/request-order/destroy', $requestOrder->id) }}" class="btn ob-btn ob-btn-danger delete-request-order-detail">
                        <i class="fas fa-trash" aria-hidden="true"></i> Hapus
                    </a>
                @endif
                @if ($isDraft && $canUpdate)
                    <a href="{{ route('warehouse/request-order/submit', $requestOrder->id) }}" class="btn ob-btn ob-btn-primary submit-request-order-detail">
                        <i class="fas fa-paper-plane" aria-hidden="true"></i> Ajukan
                    </a>
                @endif
                @if ($canApprove)
                    <button type="submit" form="approveRequestOrderForm" class="btn ob-btn ob-btn-primary">
                        <i class="fas fa-check-circle" aria-hidden="true"></i> Setujui
                    </button>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="ob-notice ro-notice" role="status">
                <i class="fas fa-check-circle" aria-hidden="true"></i> {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="ob-notice ro-notice ro-notice--error" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i> {{ $errors->first() }}
            </div>
        @endif

        {{-- Ringkasan: hitungan item, karena qty tiap bahan bersatuan berbeda --}}
        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-boxes"></i></span>
                <div>
                    <p class="ob-stat__label">Jumlah Item</p>
                    <p class="ob-stat__value">{{ number_format($stats['total_items'], 0, ',', '.') }}</p>
                    <p class="wh-stat-sub">bahan yang diminta</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
                <div>
                    <p class="ob-stat__label">Item Disetujui</p>
                    <p class="ob-stat__value">{{ number_format($stats['approved_items'], 0, ',', '.') }}</p>
                    <p class="wh-stat-sub">qty disetujui lebih dari 0</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-truck-loading"></i></span>
                <div>
                    <p class="ob-stat__label">Item Terpenuhi</p>
                    <p class="ob-stat__value">{{ number_format($stats['fulfilled_items'], 0, ',', '.') }}</p>
                    <p class="wh-stat-sub">sudah dikirim penuh</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-calendar-alt"></i></span>
                <div>
                    <p class="ob-stat__label">Dibutuhkan</p>
                    <p class="ob-stat__value ro-stat-date">{{ $requestOrder->needed_at?->translatedFormat('d M Y') ?? '-' }}</p>
                    <p class="wh-stat-sub">{{ $isOverdue ? 'sudah lewat tanggal' : ($requestOrder->needed_at ? 'tanggal dibutuhkan peminta' : 'tidak ditentukan') }}</p>
                </div>
            </div>
        </div>

        <div class="wh-detail-grid">
            {{-- Kolom utama --}}
            <div>
                @if ($canApprove)
                    <form id="approveRequestOrderForm" action="{{ route('warehouse/request-order/approve', $requestOrder->id) }}" method="POST">
                        @csrf
                @endif

                <div class="ob-card">
                    <div class="wh-card__head">
                        <div>
                            <h2 class="wh-card__title">
                                <i class="fas {{ $canApprove ? 'fa-user-check' : 'fa-boxes' }}" aria-hidden="true"></i>
                                {{ $canApprove ? 'Review Item' : 'Item Request' }} <span class="wh-count">{{ $requestOrder->items->count() }}</span>
                            </h2>
                            <p class="wh-card__desc">
                                {{ $canApprove
                                    ? 'Isi qty disetujui dalam satuan dasar bahan. Isi 0 bila item tidak disetujui.'
                                    : 'Qty disetujui dan terpenuhi dicatat dalam satuan dasar bahan.' }}
                            </p>
                        </div>
                        @if ($requestOrder->items->isNotEmpty())
                            <input id="requestItemSearch" type="search" class="form-control ro-search" placeholder="Cari bahan atau kode" aria-label="Cari bahan">
                        @endif
                    </div>

                    @if ($requestOrder->items->isEmpty())
                        <div class="wh-empty">
                            <span class="wh-empty__icon" aria-hidden="true"><i class="fas fa-boxes"></i></span>
                            <p class="wh-empty__title">Belum ada item</p>
                            <p class="wh-empty__text">Tambahkan bahan baku lewat tombol Edit sebelum request diajukan.</p>
                        </div>
                    @else
                        <div class="ob-table-scroll">
                            <table class="wh-table" id="request-order-items-detail-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Bahan Baku</th>
                                        <th scope="col" class="text-end">Diminta</th>
                                        <th scope="col" class="text-end">Disetujui</th>
                                        <th scope="col" class="text-end">Terpenuhi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($requestOrder->items as $item)
                                        @php
                                            $material = $item->rawMaterial;
                                            $baseUnit = $item->requested_base_satuan_name;
                                            $sameUnit = (int) $item->requested_satuan_id === (int) $item->requested_base_satuan_id;
                                        @endphp
                                        <tr data-search="{{ mb_strtolower(($material?->name ?? '') . ' ' . ($material?->code ?? '')) }}">
                                            <td>
                                                <div class="wh-cell-stack">
                                                    <span class="wh-strong">{{ $material?->name ?? 'Bahan #' . $item->raw_material_id }}</span>
                                                    <span class="ob-cell-meta">{{ $material?->code ?: 'Tanpa kode' }}</span>
                                                    @if ($item->notes)
                                                        <span class="ob-cell-meta ro-item-notes">{{ $item->notes }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <div class="wh-cell-stack wh-cell-stack--end">
                                                    <span class="wh-num wh-strong">{{ $qty($item->requested_qty) }} <span class="ro-unit">{{ $item->requested_satuan_name }}</span></span>
                                                    @unless ($sameUnit)
                                                        <span class="ob-cell-meta wh-num">= {{ $qty($item->requested_base_qty) }} {{ $baseUnit }}</span>
                                                    @endunless
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                @if ($canApprove)
                                                    @php
                                                        $approvalField = 'items.' . $item->id . '.approved_base_qty';
                                                        $approvalValue = old($approvalField, $plain($item->approved_base_qty ?? $item->requested_base_qty));
                                                    @endphp
                                                    <div class="ro-approval">
                                                        <div class="input-group">
                                                            <input type="number" name="items[{{ $item->id }}][approved_base_qty]"
                                                                class="form-control text-end ob-input-suffixed @error($approvalField) is-invalid @enderror"
                                                                value="{{ $approvalValue }}" min="0" max="{{ $plain($item->requested_base_qty) }}" step="any"
                                                                aria-label="Qty disetujui {{ $material?->name }}" required>
                                                            <span class="input-group-text ob-input-suffix">{{ $baseUnit }}</span>
                                                        </div>
                                                        <small class="text-muted">Maks. {{ $qty($item->requested_base_qty) }} {{ $baseUnit }}</small>
                                                        @error($approvalField)
                                                            <div class="invalid-feedback d-block text-start">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                @elseif ($item->approved_base_qty !== null)
                                                    <span class="wh-num {{ (float) $item->approved_base_qty > 0 ? 'wh-strong' : 'ro-rejected-qty' }}">
                                                        {{ $qty($item->approved_base_qty) }} <span class="ro-unit">{{ $item->approved_base_satuan_name }}</span>
                                                    </span>
                                                @else
                                                    <span class="ob-cell-meta">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if ((float) $item->fulfilled_base_qty > 0)
                                                    <span class="wh-num wh-strong">{{ $qty($item->fulfilled_base_qty) }} <span class="ro-unit">{{ $item->fulfilled_base_satuan_name }}</span></span>
                                                @else
                                                    <span class="ob-cell-meta">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="wh-side-empty ro-filter-empty" hidden>Tidak ada bahan yang cocok dengan pencarian.</p>
                    @endif

                    @if ($canApprove)
                        <div class="ro-form-foot">
                            <button type="submit" class="btn ob-btn ob-btn-primary">
                                <i class="fas fa-check-circle" aria-hidden="true"></i> Setujui Request
                            </button>
                        </div>
                    @endif
                </div>

                @if ($canApprove)
                    </form>
                @endif
            </div>

            {{-- Kolom samping --}}
            <aside>
                <div class="ob-card">
                    <div class="wh-card__head">
                        <h2 class="wh-card__title"><i class="fas fa-id-card" aria-hidden="true"></i> Profil Request</h2>
                    </div>
                    <div class="wh-card__body">
                        <dl class="wh-dl">
                            <div class="wh-dl__row"><dt>Nomor</dt><dd>{{ $requestOrder->request_number }}</dd></div>
                            <div class="wh-dl__row"><dt>Peminta</dt><dd>{{ $requestOrder->requesterInventory?->name }}</dd></div>
                            <div class="wh-dl__row"><dt>Pemenuh</dt><dd>{{ $requestOrder->fulfillmentInventory?->name ?? 'Belum ditentukan' }}</dd></div>
                            <div class="wh-dl__row"><dt>Catatan</dt><dd class="ro-notes">{{ $requestOrder->notes }}</dd></div>
                        </dl>
                    </div>
                </div>

                <div class="ob-card">
                    <div class="wh-card__head">
                        <h2 class="wh-card__title"><i class="fas fa-history" aria-hidden="true"></i> Riwayat</h2>
                    </div>
                    <ul class="wh-list">
                        <li class="wh-list__item">
                            <span class="wh-strong">Dibuat</span>
                            <span class="ob-cell-meta">{{ $requestOrder->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}</span>
                        </li>
                        @unless ($isDraft)
                            <li class="wh-list__item">
                                <span class="wh-strong">Diajukan</span>
                                <span class="ob-cell-meta">oleh {{ $requestOrder->requestedBy?->name ?? '-' }}</span>
                            </li>
                        @endunless
                        @if ($requestOrder->approved_at)
                            <li class="wh-list__item">
                                <span class="wh-strong">Disetujui</span>
                                <span class="ob-cell-meta">oleh {{ $requestOrder->approvedBy?->name ?? '-' }} · {{ $requestOrder->approved_at->translatedFormat('d M Y, H:i') }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </aside>
        </div>
    </div>

    @push('js')
        <script>
            $(function () {
                // Pencarian item di sisi browser (semua baris sudah dimuat)
                $('#requestItemSearch').on('input search', function () {
                    const keyword = (this.value || '').toLowerCase().trim();
                    let visible = 0;

                    $('#request-order-items-detail-table tbody tr').each(function () {
                        const show = !keyword || $(this).data('search').toString().includes(keyword);
                        $(this).toggle(show);
                        if (show) visible++;
                    });

                    $('.ro-filter-empty').prop('hidden', visible > 0);
                });

                function confirmAjax(button, options) {
                    Swal.fire({
                        title: options.title,
                        text: options.text,
                        icon: options.icon,
                        showCancelButton: true,
                        confirmButtonText: options.confirm,
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: button.href,
                            method: options.method,
                            data: { _token: @json(csrf_token()) },
                            success: function (response) {
                                showToast(response.status || 'success', response.message || options.success);
                                options.after();
                            },
                            error: function (xhr) {
                                const errors = xhr.responseJSON?.errors;
                                const message = errors ? Object.values(errors).flat()[0] : xhr.responseJSON?.message;
                                showToast('error', message || options.failed);
                            }
                        });
                    });
                }

                $('.main-content').on('click', '.submit-request-order-detail', function (event) {
                    event.preventDefault();
                    confirmAjax(this, {
                        title: 'Ajukan request?',
                        text: 'Request ini akan masuk tahap review dan tidak bisa diedit lagi.',
                        icon: 'question',
                        confirm: 'Ya, ajukan',
                        method: 'POST',
                        success: 'Request order berhasil diajukan.',
                        failed: 'Gagal mengajukan request order',
                        after: () => location.reload()
                    });
                });

                $('.main-content').on('click', '.delete-request-order-detail', function (event) {
                    event.preventDefault();
                    confirmAjax(this, {
                        title: 'Hapus draft?',
                        text: 'Draft ini akan dihapus dari daftar request.',
                        icon: 'warning',
                        confirm: 'Ya, hapus',
                        method: 'DELETE',
                        success: 'Request order draft berhasil dihapus.',
                        failed: 'Gagal menghapus request order',
                        after: () => { window.location.href = @json(route('warehouse/request-order')); }
                    });
                });

                $('#approveRequestOrderForm').on('submit', function (event) {
                    event.preventDefault();
                    const form = this;

                    if (!form.reportValidity()) return;

                    Swal.fire({
                        title: 'Setujui request?',
                        text: 'Qty disetujui akan disimpan dan status berubah menjadi Disetujui.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, setujui',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            HTMLFormElement.prototype.submit.call(form);
                        }
                    });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .ob-page .ob-stats .ob-stat { margin-bottom: 0; }
            .ro-notice { margin-bottom: 1rem; }
            .ro-notice--error { border-color: var(--ob-danger); color: var(--ob-danger); background: var(--ob-danger-soft); }
            .ro-overdue { font-weight: 600; color: var(--ob-danger); }
            .ro-stat-date { font-size: 1.15rem; }

            .ro-search { width: 220px; height: 36px; font-size: .82rem; }
            .ro-unit { font-size: .75rem; font-weight: 500; color: var(--ob-muted); }
            .ro-item-notes { white-space: pre-line; max-width: 320px; }
            .ro-rejected-qty { color: var(--ob-muted); text-decoration: line-through; }
            .ro-notes { white-space: pre-line; font-weight: 400 !important; }

            #request-order-items-detail-table td { vertical-align: top; }
            #request-order-items-detail-table td:first-child { min-width: 240px; }
            .ro-approval { display: inline-flex; flex-direction: column; align-items: flex-end; gap: .25rem; width: 190px; }
            .ro-approval .input-group { flex-wrap: nowrap; }

            .ro-form-foot { display: flex; justify-content: flex-end; padding: 1rem 1.25rem; border-top: 1px solid var(--ob-border); }

            @media (max-width: 575.98px) {
                .ro-search { width: 100%; }
            }
        </style>
    @endpush
@endsection
