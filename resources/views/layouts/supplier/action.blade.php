<div class="btn-group" role="group">
    <button id="btnGroupDrop1" type="button" class="btn btn-primary dropdown-toggle btn-sm" data-bs-toggle="dropdown"
        aria-expanded="false">
        Action
    </button>
    <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
        <li><a class="dropdown-item" href="{{ route('warehouse/supplier/show', $supplier->id) }}">Detail Supplier</a></li>
        <li><a class="dropdown-item action" href="{{ route('warehouse/supplier/edit', $supplier->id) }}">Edit</a></li>
        <li><a class="dropdown-item delete" href="{{ route('warehouse/supplier/destroy', $supplier->id) }}" style="color:red">Hapus</a></li>
    </ul>
</div>
