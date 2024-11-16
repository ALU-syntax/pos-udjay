<div class="btn-group" role="group">
    <button id="btnGroupDrop1" type="button" class="btn btn-primary dropdown-toggle btn-sm" data-bs-toggle="dropdown"
        aria-expanded="false">
        Action
    </button>
    <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
        @if($showEdit)
            <li><a class="dropdown-item" href="{{ $routeEdit }}">Edit</a></li>
        @endif
        @if($showDelete)
            <li>
                <form action="{{ $routeDelete }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('delete')
                    <button class="dropdown-item" type="submit">Delete</button>
                </form>
            </li>
        @endif
    </ul>
</div>