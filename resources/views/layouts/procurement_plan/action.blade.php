@php
    $isDraft = optional($procurementPlan->status)->code === 'draft';
@endphp

<div class="btn-group" role="group">
    <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
        Action
    </button>
    <ul class="dropdown-menu">
        <li>
            <a class="dropdown-item" href="{{ route('warehouse/procurement-plan/detail', $procurementPlan->id) }}">
                <i class="fa fa-eye me-2"></i>Detail
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ route('warehouse/procurement-plan/purchase-orders', $procurementPlan->id) }}">
                <i class="fa fa-file-invoice me-2"></i>Detail PO
            </a>
        </li>
        @if ($isDraft)
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item delete text-danger" href="{{ route('warehouse/procurement-plan/destroy', $procurementPlan->id) }}">
                    <i class="fa fa-trash me-2"></i>Hapus
                </a>
            </li>
        @endif
    </ul>
</div>
