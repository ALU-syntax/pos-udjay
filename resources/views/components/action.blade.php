<div class="btn-group" role="group">
    <button id="btnGroupDrop1" type="button" class="btn btn-primary dropdown-toggle btn-sm" data-bs-toggle="dropdown"
        aria-expanded="false">
        Action
    </button>
    <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
        @if ($showEdit)
            <li><a type="button" class="dropdown-item action" href="{{ $routeEdit }}">Edit</a></li>
        @endif
        @if ($showDelete)
            <li>
                @if ($softDelete)
                    <form action="{{ $routeDelete }}" method="POST" enctype="multipart/form-data" class="delete">
                        @csrf
                        <button class="dropdown-item" type="submit">Delete</button>
                    </form>
                @else
                    <form action="{{ $routeDelete }}" method="POST" enctype="multipart/form-data" class="delete">
                        @csrf
                        @method('delete')
                        <button class="dropdown-item" type="submit">Delete</button>
                    </form>
                @endif

            </li>
        @endif
    </ul>
</div>
