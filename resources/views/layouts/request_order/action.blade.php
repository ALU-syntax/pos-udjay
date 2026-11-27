@php
    $isDraft = $requestOrder->status?->code === \App\Models\RawMaterialRequestStatus::DRAFT;
    $isSubmitted = $requestOrder->status?->code === \App\Models\RawMaterialRequestStatus::SUBMITTED;
@endphp

<div class="ob-row-actions" role="group" aria-label="Aksi request {{ $requestOrder->request_number }}">
    @can('read warehouse/request-order')
        <a href="{{ route('warehouse/request-order/detail', $requestOrder->id) }}" class="ob-icon-btn ob-icon-btn--sm"
            title="{{ $isSubmitted ? 'Review request' : 'Detail request' }}" aria-label="{{ $isSubmitted ? 'Review request' : 'Detail request' }}">
            <i class="fas {{ $isSubmitted ? 'fa-user-check' : 'fa-eye' }}" aria-hidden="true"></i></a>
    @endcan
    @if ($isDraft)
        @can('update warehouse/request-order')
            <a href="{{ route('warehouse/request-order/edit', $requestOrder->id) }}" class="ob-icon-btn ob-icon-btn--sm"
                title="Edit draft" aria-label="Edit draft"><i class="fas fa-pen" aria-hidden="true"></i></a>
            <a href="{{ route('warehouse/request-order/submit', $requestOrder->id) }}" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--success submit-request-order"
                data-number="{{ $requestOrder->request_number }}" title="Ajukan untuk review" aria-label="Ajukan untuk review"><i class="fas fa-paper-plane" aria-hidden="true"></i></a>
        @endcan
        @can('delete warehouse/request-order')
            <a href="{{ route('warehouse/request-order/destroy', $requestOrder->id) }}" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger delete-request-order"
                data-number="{{ $requestOrder->request_number }}" title="Hapus draft" aria-label="Hapus draft"><i class="fas fa-trash" aria-hidden="true"></i></a>
        @endcan
    @endif
</div>
