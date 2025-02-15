<x-modal title="Tambah Community" action="{{ $action }}" method="POST">
    @if ($data->id)
        @method('put')
    @endif
    <div class="col-sm-12">
        <div class="form-group">
            <label>Name <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{ $data->name }}" type="text" class="form-control"
                placeholder="nama" required>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Email <span class="text-danger">*</span></label>
            <input id="email" name="email" value="{{ $data->email }}" type="text" class="form-control"
                placeholder="email" required>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Domisili <span class="text-danger">*</span></label>
            <input id="domisili" name="domisili" value="{{ $data->domisili }}" type="text" class="form-control"
                placeholder="domisili" required>
        </div>
    </div>
    <div class="col-md-12">
        <div class="form-group">
            <label for="customer_id">Team Leader <span class="text-danger ">*</span></label>
            <select name="customer_id" class="select2InsideModal form-select w-100" style="width: 100% !important;"
                required>
                <option selected disabled>Pilih Team Leader</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @if ($data->customer_id == $customer->id) selected @endif>
                        {{ $customer->name }} - {{ $customer->telfon }}</option>
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
</x-modal>

<script>
    $(".select2InsideModal").select2({
        dropdownParent: $("#modal_action")
    });
</script>
