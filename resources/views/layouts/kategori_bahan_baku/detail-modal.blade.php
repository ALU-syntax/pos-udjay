{{-- ob-page memberi token & gaya report ke modal (modal dirender di luar .main-content) --}}
<x-modal addStyle="modal-xl ob-page ob-modal kb-detail-modal" title="Detail Bahan Baku: {{ $category->name }}" action="#" method="GET" customSubmit="true">
    @php
        $activeMaterials = $materials->where('is_active', true)->count();
        $inactiveMaterials = $materials->count() - $activeMaterials;
        $unitLabel = fn ($unit) => $unit?->symbol ?: $unit?->name ?: '-';
    @endphp

    <div class="col-12">
        <p class="ob-modal__desc">
            Status kategori:
            @if ($category->is_active)
                <span class="badge badge-success kb-badge-inline">Aktif</span>
            @else
                <span class="badge badge-muted kb-badge-inline">Nonaktif</span>
            @endif
            @if ($category->notes)
                <span class="kb-detail__notes">{{ $category->notes }}</span>
            @endif
        </p>
    </div>

    {{-- Ringkasan --}}
    <div class="col-12">
        <div class="kb-detail__stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-cubes"></i></span>
                <div>
                    <p class="ob-stat__label">Total Bahan</p>
                    <p class="ob-stat__value">{{ number_format($materials->count(), 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
                <div>
                    <p class="ob-stat__label">Aktif</p>
                    <p class="ob-stat__value">{{ number_format($activeMaterials, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-pause-circle"></i></span>
                <div>
                    <p class="ob-stat__label">Nonaktif</p>
                    <p class="ob-stat__value">{{ number_format($inactiveMaterials, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Daftar bahan baku --}}
    <div class="col-12">
        <div class="ob-card kb-detail__card">
            @if ($materials->isEmpty())
                <div class="kb-empty">
                    <span class="kb-empty__icon" aria-hidden="true"><i class="fas fa-inbox"></i></span>
                    <p class="kb-empty__title">Belum ada bahan baku</p>
                    <p class="kb-empty__text">Kategori ini belum dipakai oleh bahan baku mana pun. Pilih kategori ini saat menambah bahan baku di menu Library / Bahan Baku.</p>
                </div>
            @else
                <div class="ob-table-scroll">
                    <table class="kb-table">
                        <thead>
                            <tr>
                                <th scope="col">Bahan Baku</th>
                                <th scope="col">Satuan Dasar</th>
                                <th scope="col">Penyimpanan</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($materials as $material)
                                <tr class="{{ $material->is_active ? '' : 'kb-row-inactive' }}">
                                    <td>
                                        <div class="kb-cell-stack">
                                            <span class="kb-strong">{{ $material->name }}</span>
                                            <span class="ob-cell-meta">{{ $material->code ?: 'Tanpa kode' }}</span>
                                            @if ($material->notes)
                                                <span class="ob-cell-meta kb-truncate" title="{{ $material->notes }}">{{ $material->notes }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td><span class="ob-chip">{{ $unitLabel($material->baseUnit) }}</span></td>
                                    <td>{{ $material->storageType?->name ?: '-' }}</td>
                                    <td>
                                        @if ($material->is_active)
                                            <span class="badge badge-success">Aktif</span>
                                        @else
                                            <span class="badge badge-muted">Nonaktif</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="col-12">
        <div class="kb-detail__foot">
            <button type="button" class="btn ob-btn ob-btn-outline" data-bs-dismiss="modal">Tutup</button>
        </div>
    </div>

    <style>
        /* Footer bawaan komponen kosong (customSubmit), tombol tutup ada di body */
        .kb-detail-modal .modal-footer { display: none; }
        .kb-detail-modal .modal-body { padding-bottom: 1.25rem; }

        .kb-detail-modal .kb-badge-inline { margin-left: .35rem; vertical-align: 1px; }
        .kb-detail__notes { display: block; margin-top: .5rem; white-space: pre-line; }

        .kb-detail__stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; margin-bottom: 1rem; }
        .kb-detail__stats .ob-stat { margin-bottom: 0; }
        .kb-detail-modal .kb-detail__card { margin-bottom: 1rem; overflow: hidden; }

        .kb-table { width: 100%; margin: 0; border-collapse: collapse; font-size: .85rem; }
        .kb-table th { padding: .65rem 1rem; background: var(--ob-surface-2); border-bottom: 1px solid var(--ob-border); font-size: .7rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--ob-muted); white-space: nowrap; }
        .kb-table td { padding: .8rem 1rem; border-bottom: 1px solid var(--ob-border); color: var(--ob-text-2); vertical-align: middle; }
        .kb-table th:first-child, .kb-table td:first-child { padding-left: 1.25rem; }
        .kb-table th:last-child, .kb-table td:last-child { padding-right: 1.25rem; }
        .kb-table tbody tr:last-child td { border-bottom: 0; }
        .kb-table tbody tr:hover td { background: var(--ob-surface-2); }
        .kb-row-inactive { opacity: .6; }

        .kb-cell-stack { display: flex; flex-direction: column; align-items: flex-start; gap: .2rem; }
        .kb-strong { font-weight: 600; color: var(--ob-text); }
        .kb-truncate { display: inline-block; max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .kb-empty { display: flex; flex-direction: column; align-items: center; gap: .15rem; padding: 2rem 1.25rem; text-align: center; }
        .kb-empty__icon { display: inline-grid; place-items: center; width: 44px; height: 44px; margin-bottom: .5rem; border-radius: 12px; background: var(--ob-surface-2); border: 1px solid var(--ob-border); color: var(--ob-muted); }
        .kb-empty__title { margin: 0; font-size: .9rem; font-weight: 600; color: var(--ob-text); }
        .kb-empty__text { margin: 0; max-width: 46ch; font-size: .8rem; line-height: 1.5; color: var(--ob-muted); }

        .kb-detail__foot { display: flex; justify-content: flex-end; }

        @media (max-width: 767.98px) {
            .kb-detail__stats { grid-template-columns: 1fr; }
        }
    </style>
</x-modal>
