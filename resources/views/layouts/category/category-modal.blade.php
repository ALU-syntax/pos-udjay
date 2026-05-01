<x-modal title="{{ $data->id ? 'Edit Category' : 'Tambah Category Baru' }}"
    description="{{ $data->id ? 'Ubah detail kategori produk' : 'Tambah kategori produk untuk sistem Anda' }}"
    update="{{ $data->id ? true : false }}"
    action="{{$action}}" method="POST">
    @if ($data->id)
        @method('put')
    @endif

    <!-- Name Field -->
    <div class="col-sm-12">
        <div class="form-group mb-3">
            <label class="form-label fw-semibold" for="name">
                Nama Category <span class="text-danger">*</span>
            </label>
            <input id="name" name="name" value="{{old('name', $data->name ?? '')}}" type="text"
                class="form-control @error('name') is-invalid @enderror"
                placeholder="Contoh: Elektronik, Pakaian, dll..." required
                maxlength="100">
            @error('name')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <small class="text-muted">Maksimal 100 karakter</small>
        </div>
    </div>

    <!-- Reward Category Field -->
    <div class="col-md-12">
        <div class="form-group mb-3">
            <label class="form-label fw-semibold" for="reward_categories">
                <i class="fa fa-star text-warning me-1"></i>Kategori Reward
                <span class="text-danger">*</span>
            </label>
            <select name="reward_categories" id="reward_categories"
                class="form-select @error('reward_categories') is-invalid @enderror" required>
                <option value="">-- Pilih Kategori Reward --</option>
                <option value="1" @if(old('reward_categories', $data->reward_categories ?? '') == 1) selected @endif>
                    <i class="fa fa-check"></i> Iya, Ini Reward Category
                </option>
                <option value="0" @if(old('reward_categories', $data->reward_categories ?? '') == 0) selected @endif>
                    Tidak, Kategori Biasa
                </option>
            </select>
            @error('reward_categories')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <small class="text-muted d-block mt-1">
                <i class="fa fa-info-circle"></i> Reward category dapat digunakan untuk program loyalitas
            </small>
        </div>
    </div>

    <!-- Status Field -->
    <div class="col-md-12">
        <div class="form-group mb-0">
            <label class="form-label fw-semibold" for="status">
                Status <span class="text-danger">*</span>
            </label>
            <select name="status" id="status"
                class="form-select @error('status') is-invalid @enderror" required>
                <option value="">-- Pilih Status --</option>
                <option value="1" @if(old('status', $data->status ?? '') == 1) selected @endif>
                    <i class="fa fa-check-circle text-success"></i> Aktif
                </option>
                <option value="0" @if(old('status', $data->status ?? '') == 0) selected @endif>
                    <i class="fa fa-times-circle text-danger"></i> Tidak Aktif
                </option>
            </select>
            @error('status')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Styling -->
    <style>
        .form-label {
            margin-bottom: 0.5rem;
            color: #333;
            font-size: 0.95rem;
        }

        .form-control, .form-select {
            border: 1px solid #e3e3e3;
            border-radius: 0.375rem;
            padding: 0.625rem 0.75rem;
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .form-control.is-invalid, .form-select.is-invalid {
            border-color: #dc3545;
        }

        .is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .invalid-feedback {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .text-muted {
            color: #6c757d;
            font-size: 0.85rem;
        }
    </style>
</x-modal>
