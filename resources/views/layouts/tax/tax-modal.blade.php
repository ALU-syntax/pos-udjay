<x-modal title="Tambah Tax"  action="{{ $action }}"
    method="POST">
    @if ($data->id)
        @method('put')
    @endif
    <div class="col-sm-12">
        <div class="form-group">
            <label>Name <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{ $data->name }}" type="text" class="form-control"
                placeholder="nama tax.." required>
        </div>
    </div>
    <div class="col-sm-12 px-4">
        <label>Amount <span class="text-danger">*</span></label>
        <div class="input-group mb-3">
            <input name="amount" type="text" value="{{ $data->amount }}" class="form-control" placeholder="Amount"
                aria-label="Amount" aria-describedby="basic-addon2">
            <input type="text" name="satuan" value="%" hidden >
            <span class="input-group-text" id="basic-addon2">%</span>
        </div>
    </div>
    <div class="col-md-12">
        <div class="form-group">
            <label for="outlet_id">Outlet <span class="text-danger ">*</span></label>
            <select name="outlet_id[]" id="outlet_id[]" class="select2InsideModal form-select w-100"
                style="width: 100% !important;" required multiple @if ($data->id) disabled @endif>
                <option disabled>Pilih Category</option>
                @foreach ($outlets as $outlet)
                    <option value="{{ $outlet->id }}" @if ($data->outlet_id == $outlet->id) selected @endif>
                        {{ $outlet->name }}</option>
                @endforeach
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
