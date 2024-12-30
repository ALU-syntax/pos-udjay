<x-modal title="Tambah Payment" action="{{ $action }}" method="POST">
    @if ($data->id)
        @method('put')
    @endif
    <div class="col-sm-12">
        <div class="form-group">
            <label>Name <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{ $data->name }}" type="text" class="form-control"
                placeholder="Name" required>
        </div>
    </div>
    <div class="col-12">
        <div class="form-group">
            <label for="category_payment_id">Category Payment <span class="text-danger ">*</span></label>
            <select name="category_payment_id" id="category_payment_id" class="select2InsideModal form-select w-100"
                style="width: 100% !important;" required>
                <option selected disabled>Pilih Category Payment</option>
                @foreach ($categoryPayment as $category)
                    <option value="{{ $category->id }}" @if ($data->category_payment_id == $category->id) selected @endif>
                        {{ $category->name }}</option>
                @endforeach
            </select>
            {{-- <small id="category_id_feedback" class="d-none text-danger"><i>*Pilih Category Terlebih
                    Dahulu</i></small> --}}
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
