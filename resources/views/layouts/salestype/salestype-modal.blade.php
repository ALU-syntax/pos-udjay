<x-modal title="Tambah Sales Type"  action="{{ $action }}"
    method="POST">
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
    <script>
        $(".select2InsideModal").select2({
            dropdownParent: $("#modal_action"),
            closeOnSelect: false
        });
    </script>
</x-modal>
