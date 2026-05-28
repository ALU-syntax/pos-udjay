@php
    $isDraft = optional($requestOrder->status)->code === 'draft';
@endphp

<div class="btn-group" role="group">
    <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
        Action
    </button>
    <ul class="dropdown-menu">
        <li>
            <a class="dropdown-item" href="{{ route('warehouse/request-order/detail', $requestOrder->id) }}">
                <i class="fa fa-eye me-2"></i>Detail
            </a>
        </li>
        @if ($isDraft)
            <li>
                <a class="dropdown-item" href="{{ route('warehouse/request-order/edit', $requestOrder->id) }}">
                    <i class="fa fa-edit me-2"></i>Edit
                </a>
            </li>
            <li>
                <a class="dropdown-item submit-request-order" href="{{ route('warehouse/request-order/submit', $requestOrder->id) }}">
                    <i class="fa fa-paper-plane me-2"></i>Submit
                </a>
            </li>
            <li>
                <a class="dropdown-item delete text-danger" href="{{ route('warehouse/request-order/destroy', $requestOrder->id) }}">
                    <i class="fa fa-trash me-2"></i>Hapus
                </a>
            </li>
        @endif
    </ul>
</div>
