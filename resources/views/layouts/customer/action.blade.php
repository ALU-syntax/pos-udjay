<div class="btn-group" role="group">
    <button id="btnGroupDrop1" type="button" class="btn btn-primary dropdown-toggle btn-sm" data-bs-toggle="dropdown"
        aria-expanded="false">
        Action
    </button>
    <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
        <li><a class="dropdown-item action" href="{{$detail}}">Detail Customer</a></li>
        <li><a class="dropdown-item action" href="{{$edit}}">Edit</a></li>
        <li><a class="dropdown-item delete" href="{{$routeDelete}}" style="color: red">Hapus</a></li>
        <li><a class="dropdown-item action" href="{{$listReferee}}">List Referee</a></li>
        
    </ul>
</div>
