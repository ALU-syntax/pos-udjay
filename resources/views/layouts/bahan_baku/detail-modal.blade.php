<x-modal addStyle="modal-lg" title="Detail Bahan Baku" action="#" method="GET" customSubmit="true">
    <div class="col-12">
        <div class="material-detail-hero">
            <div>
                <span class="badge bg-light text-dark border mb-2">{{ $rawMaterial->code ?: '-' }}</span>
                <h4 class="mb-1">{{ $rawMaterial->name }}</h4>
                <p class="text-muted mb-0">{{ $rawMaterial->notes ?: 'Belum ada catatan tambahan.' }}</p>
            </div>
            <div class="text-end">
                @if ($rawMaterial->is_active)
                    <span class="badge badge-success">Aktif</span>
                @else
                    <span class="badge badge-secondary">Tidak Aktif</span>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 mt-3">
        <div class="material-detail-grid">
            <div class="material-detail-item">
                <span class="material-detail-icon"><i class="fa fa-layer-group"></i></span>
                <div>
                    <small class="text-muted d-block">Kategori</small>
                    <strong>{{ optional($rawMaterial->category)->name ?: 'Tanpa kategori' }}</strong>
                </div>
            </div>
            <div class="material-detail-item">
                <span class="material-detail-icon"><i class="fa fa-balance-scale"></i></span>
                <div>
                    <small class="text-muted d-block">Satuan Dasar</small>
                    <strong>{{ optional($rawMaterial->baseUnit)->symbol ?: optional($rawMaterial->baseUnit)->name ?: '-' }}</strong>
                </div>
            </div>
            <div class="material-detail-item">
                <span class="material-detail-icon"><i class="fa fa-warehouse"></i></span>
                <div>
                    <small class="text-muted d-block">Tipe Penyimpanan</small>
                    <strong>{{ optional($rawMaterial->storageType)->name ?: '-' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 mt-3">
        <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
        </div>
    </div>

    <style>
        .material-detail-hero {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 16px;
            border: 1px solid rgba(18, 38, 63, 0.08);
            border-radius: 8px;
            background: #fbfcfe;
        }

        .material-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .material-detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border: 1px solid rgba(18, 38, 63, 0.08);
            border-radius: 8px;
            background: #fff;
        }

        .material-detail-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: #eef4ff;
            color: #3157c9;
        }

        @media (max-width: 767.98px) {
            .material-detail-hero {
                flex-direction: column;
            }

            .material-detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</x-modal>
