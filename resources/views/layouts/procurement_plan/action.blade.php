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
        @if ($isDraft)
            <li>
                <a class="dropdown-item delete text-danger" href="{{ route('warehouse/procurement-plan/destroy', $procurementPlan->id) }}">
                    <i class="fa fa-trash me-2"></i>Hapus
                </a>
            </li>
        @endif
    </ul>
</div>
