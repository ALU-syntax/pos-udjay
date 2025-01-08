@props([
    'id',
    'action',
    'title' => false,
    'update' => false,
    'description' => null,
    'addStyle' => '',
    'customSubmit' => false,
])
<div class="modal-dialog modal-dialog-centered {{ $addStyle }}" role="document">
    <div class="modal-content">
        <form id="form_action" action="{{ $action }}" method="POST">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <span class="fw-mediumbold">
                        {{ $title }}</span>

                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @csrf
                <div class="row">
                    @if ($description)
                        <div class="col-12">
                            <span class="small ms-2">{{ $description }}</span>
                        </div>
                    @endif
                    {{ $slot }}
                </div>

            </div>
            <div class="modal-footer border-0">
                @if (!$customSubmit)
                    <button type="submit" id="addRowButton" class="btn btn-primary">
                        @if ($update)
                            Update
                        @else
                            Add
                        @endif
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>

                @endif

            </div>

        </form>
    </div>
</div>
