@php
    $isDraft = $procurementPlan->status?->code === \App\Models\ProcurementPlanStatus::DRAFT;
@endphp

<div class="ob-row-actions" role="group" aria-label="Aksi plan {{ $procurementPlan->plan_number }}">
    @can('read warehouse/procurement-plan')
        <a href="{{ route('warehouse/procurement-plan/detail', $procurementPlan->id) }}" class="ob-icon-btn ob-icon-btn--sm"
            title="Detail plan" aria-label="Detail plan"><i class="fas fa-eye" aria-hidden="true"></i></a>
        <a href="{{ route('warehouse/procurement-plan/purchase-orders', $procurementPlan->id) }}" class="ob-icon-btn ob-icon-btn--sm"
            title="Purchase Order" aria-label="Purchase Order"><i class="fas fa-file-invoice" aria-hidden="true"></i></a>
    @endcan
    @if ($isDraft)
        @can('delete warehouse/procurement-plan')
            <a href="{{ route('warehouse/procurement-plan/destroy', $procurementPlan->id) }}" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger delete-procurement-plan"
                data-number="{{ $procurementPlan->plan_number }}" title="Hapus draft" aria-label="Hapus draft"><i class="fas fa-trash" aria-hidden="true"></i></a>
        @endcan
    @endif
</div>
