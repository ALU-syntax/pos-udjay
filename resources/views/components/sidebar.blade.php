@props(['id', 'action', 'title' => false, 'description' => null])
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel" role="document" >
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasMenuLabel">{{ $title }}</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
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
                <button type="submit" id="addRowButton" class="btn btn-primary">Add</button>
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>

            </div>
        </form>
    </div>
</div>