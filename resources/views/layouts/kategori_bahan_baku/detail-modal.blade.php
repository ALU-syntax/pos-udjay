<x-modal addStyle="modal-xl" title="Detail Bahan Baku: {{ $category->name }}" action="#" method="GET" customSubmit="true">
    @php
        $storageLabels = [
            'dry' => 'Dry',
            'chilled' => 'Chilled',
            'frozen' => 'Frozen',
            'other' => 'Lainnya',
        ];

        $activeMaterials = $materials->where('is_active', true)->count();
        $stockableMaterials = $materials->where('is_stockable', true)->count();
    @endphp

    <div class="col-12">
        <div class="raw-detail-summary">
            <div class="raw-detail-item">
                <span class="raw-detail-icon"><i class="fa fa-cubes"></i></span>
                <div>
                    <small class="text-muted d-block">Total Bahan</small>
                    <strong>{{ $materials->count() }}</strong>
                </div>
            </div>
            <div class="raw-detail-item">
                <span class="raw-detail-icon"><i class="fa fa-check-circle"></i></span>
                <div>
                    <small class="text-muted d-block">Aktif</small>
                    <strong>{{ $activeMaterials }}</strong>
                </div>
            </div>
            <div class="raw-detail-item">
                <span class="raw-detail-icon"><i class="fa fa-warehouse"></i></span>
                <div>
                    <small class="text-muted d-block">Stockable</small>
                    <strong>{{ $stockableMaterials }}</strong>
                </div>
            </div>
            <div class="raw-detail-item">
                <span class="raw-detail-icon"><i class="fa fa-toggle-on"></i></span>
                <div>
                    <small class="text-muted d-block">Status Kategori</small>
                    <strong>{{ $category->is_active ? 'Aktif' : 'Tidak Aktif' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 mt-3">
        @if ($materials->isEmpty())
            <div class="raw-detail-empty">
                <span><i class="fa fa-inbox"></i></span>
                <h6 class="mb-1">Belum ada bahan baku</h6>
                <p class="text-muted mb-0">Kategori ini belum digunakan oleh master bahan baku mana pun.</p>
            </div>
        @else
            <div class="table-responsive raw-detail-table">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Bahan</th>
                            <th>Satuan Dasar</th>
                            <th>Penyimpanan</th>
                            <th>Stockable</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($materials as $material)
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $material->code ?: '-' }}</span>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $material->name }}</div>
                                    @if ($material->notes)
                                        <small class="text-muted">{{ \Illuminate\Support\Str::limit($material->notes, 70) }}</small>
                                    @endif
                                </td>
                                <td>{{ optional($material->baseUnit)->symbol ?: optional($material->baseUnit)->name ?: '-' }}</td>
                                <td>{{ $storageLabels[$material->storage_type] ?? ucfirst($material->storage_type ?? '-') }}</td>
                                <td>
                                    @if ($material->is_stockable)
                                        <span class="badge badge-success">Ya</span>
                                    @else
                                        <span class="badge badge-secondary">Tidak</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($material->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-secondary">Tidak Aktif</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="col-12 mt-3">
        <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
        </div>
    </div>

    <style>
        .raw-detail-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .raw-detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border: 1px solid rgba(18, 38, 63, 0.08);
            border-radius: 8px;
            background: #fbfcfe;
        }

        .raw-detail-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: #eef4ff;
            color: #3157c9;
        }

        .raw-detail-table {
            border: 1px solid rgba(18, 38, 63, 0.08);
            border-radius: 8px;
        }

        .raw-detail-table thead th {
            background: #f7f8fa;
            color: #475467;
            font-size: 12px;
            text-transform: uppercase;
        }

        .raw-detail-empty {
            padding: 36px 16px;
            border: 1px dashed rgba(18, 38, 63, 0.16);
            border-radius: 8px;
            text-align: center;
            background: #fbfcfe;
        }

        .raw-detail-empty span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            margin-bottom: 10px;
            border-radius: 8px;
            background: #f3f6fb;
            color: #667085;
        }

        @media (max-width: 767.98px) {
            .raw-detail-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>
</x-modal>
