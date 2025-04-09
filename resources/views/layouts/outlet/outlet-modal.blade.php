<x-modal title="Tambah Outlet" action="{{ $action }}"
    method="POST">
    @if ($data->id)
        @method('put')
    @endif
    <div class="col-sm-12">
        <div class="form-group">
            <label>Name <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{ $data->name }}" type="text" class="form-control"
                placeholder="nama toko.." required>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Address <span class="text-danger">*</span></label>
            <textarea id="address" name="address" value="{{ $data->address }}" type="text" class="form-control"
                placeholder="Alamat toko.." required aria-label="With textarea">{{ $data->address }}</textarea>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Nomor Telfon <span class="text-danger">*</span></label>
            <input id="phone" name="phone" value="{{ $data->phone }}" type="number" class="form-control"
                placeholder="Nomor telfon.." required>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Catatan Nota</label>
            <textarea id="catatan_nota" name="catatan_nota" value="{{ $data->catatan_nota }}" type="text" class="form-control"
                placeholder="note.." aria-label="With textarea">{{ $data->catatan_nota }}</textarea>
        </div>
    </div>
    <script>
        $(".select2InsideModal").select2({
            dropdownParent: $("#modal_action")
        });
    </script>
</x-modal>
