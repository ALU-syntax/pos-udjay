<x-modal title="Tambah Note Receipt Scheduling" action="{{ $action }}" method="POST" update="true">
    @if ($data->id)
        @method('put')
    @endif
    <div class="col-sm-12">
        <div class="form-group">
            <label>Name <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{ $data->name }}" type="text" class="form-control"
                placeholder="Nama nota" required>
        </div>
    </div>

    <div class="col-md-12 mb-1">
        <div class="form-group">
            <label for="message" class="form-label">Pesan</label>
            <textarea class="form-control" name="message" id="message" style="width: 100%; height: 150px; resize: vertical;"
                rows="4" placeholder="Masukkan Pesan">{{ $data->message }}
            </textarea>
        </div>
    </div>

    <div class="col-md-12">
        <div class="form-group row">
            <label for="schedule_promo">Jadwal Dari - Sampai</label>
            <div class="col-5">
                <div class="input-group date" id="timePicker">
                    <input type="time" class="form-control timePicker" value="{{ \Carbon\Carbon::parse($data->start)->format('H:i') }}" name="start_hour"
                        id="start_hour" required="">
                    <span class="input-group-addon"><i class="fa fa-clock-o" aria-hidden="true"></i></span>
                </div>
            </div>

            <div class="col-2 d-flex justify-content-center align-items-center">
                <span style="font-size: 30px">-</span>
            </div>

            <div class="col-5">
                <div class="input-group date" id="timePicker">
                    <input type="time" class="form-control timePicker" value="{{\Carbon\Carbon::parse($data->end)->format('H:i')}}" name="end_hour" id="end_hour"
                        required="">
                    <span class="input-group-addon"><i class="fa fa-clock-o" aria-hidden="true"></i></span>
                </div>
            </div>

        </div>
    </div>


    @if (!$data->id)
        <div class="col-md-12" @if ($data->id) hidden @endif>
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
    @endif


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
            dropdownParent: $("#modal_action"), // Pastikan parent diatur untuk modal
            // Callback setelah dropdown dibuka
            closeOnSelect: false,
            width: '100%',
        }).on("select2:open", function() {
            // const selectElement = $(this);
            // const dropdown = $(".select2-container--open");

            // // Hitung posisi elemen input
            // const offset = selectElement.offset();
            // const height = selectElement.outerHeight();

            // // Atur posisi dropdown ke posisi fixed
            // dropdown.css({
            //     position: "fixed",
            //     top: offset.top + height - $(window).scrollTop(), // Hitung posisi relatif terhadap layar
            //     left: offset.left,
            //     width: selectElement.outerWidth() - 50,
            //     zIndex: 9999, // Pastikan lebih tinggi dari modal
            // });
        }).on("select2:close", function() {
            console.log(this);

            const dropdown = $(".select2-container");

            // Hapus style yang diterapkan saat dropdown ditutup
            dropdown.css({
                position: "",
                top: "",
                left: "",
            });

        });
    </script>
</x-modal>
