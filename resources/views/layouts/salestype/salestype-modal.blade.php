<x-modal title="Tambah Sales Type" action="{{ $action }}" method="POST">
    @if ($data->id)
        @method('put')
    @endif
    <div class="col-sm-12">
        <div class="form-group">
            <label>Name <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{ $data->name }}" type="text" class="form-control"
                placeholder="Nama sales type" required>
        </div>
    </div>
    
    <div class="col-12" @if ($data->id) hidden @endif>
        <div class="form-group">
            <label for="outlet_id">Outlet <span class="text-danger ">*</span></label>
            <select @if ($data->id) name="outlet_id" @else name="outlet_id[]" @endif
                class="select2InsideModal form-select w-100" style="width: 100% !important;" required multiple
                @if ($data->id) hidden @endif>
                <option disabled>Pilih Category</option>
                @foreach (json_decode($outlets) as $outlet)
                    <option value="{{ $outlet->id }}" @if ($data->outlet_id == $outlet->id) selected @endif>
                        {{ $outlet->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-md-12">
        <div class="form-group">
            <label for="status">Status <span class="text-danger">*</span></label>
            <select name="status" id="status" class="form-select" required>
                <option disabled selected>Pilih Status</option>
                <option value="1" @if ($data->status == 1) selected @endif>Aktif</option>
                <option value="0" @if ($data->status == 0) selected @endif>Tidak Aktif</option>
            </select>
        </div>
    </div>

    <script>
        $(".select2InsideModal").select2({
            dropdownParent: $("#modal_action"),
            closeOnSelect: false
        });
    </script>
</x-modal>
