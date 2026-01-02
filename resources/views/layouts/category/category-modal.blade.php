<x-modal title="Tambah Category" description="Tambah Category Untuk Product" action="{{$action}}" method="POST">
    @if ($data->id)
        @method('put')
    @endif
    <div class="col-sm-12">
        <div class="form-group">
            <label>Name <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{$data->name}}" type="text" class="form-control" placeholder="nama category.." required>
        </div>
    </div>
    <div class="col-md-12">
        <div class="form-group">
            <label for="reward_categories">Reward Category <span class="text-danger">*</span></label>
            <select name="reward_categories" id="reward_categories" class="form-select" required>
                <option disabled selected>Apakah Reward Kategori?</option>
                <option value="1" @if($data->reward_categories == 1) selected @endif>Iya</option>
                <option value="0" @if($data->reward_categories == 0) selected @endif>Tidak</option>
            </select>
        </div>
    </div>
    <div class="col-md-12">
        <div class="form-group">
            <label for="status">Status <span class="text-danger">*</span></label>
            <select name="status" id="status" class="form-select" required>
                <option disabled selected>Pilih Status</option>
                <option value="1" @if($data->status == 1) selected @endif>Aktif</option>
                <option value="0" @if($data->status == 0) selected @endif>Tidak Aktif</option>
            </select>
        </div>
    </div>
</x-modal>
