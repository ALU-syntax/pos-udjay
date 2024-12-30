@extends('layouts.app')
@section('content')
    <div class="card mb-4">
        <h5 class="card-header">User Create</h5>
        <div class="card-body">
            <form action="{{ route('employee/user/store') }}" method="POST" enctype="multipart/form-data"
                class="needs-validation ">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Nama</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Masukan Nama"
                            value="{{ old('name') }}" required
                            style="@if ($errors->has('name')) border-color:red; @endif" />
                        @error('name')
                            <div style="color: red">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" name="username" id="username" class="form-control"
                            placeholder="Masukan Username" value="{{ old('username') }}" required
                            style="@if ($errors->has('username')) border-color:red; @endif" />
                        @error('username')
                            <div style="color: red">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6 form-password-toggle">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-group input-group-merge">
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="············" aria-describedby="basic-default-password3"
                                value="{{ old('password') }}" required
                                style="@if ($errors->has('password')) border-color:red; @endif">
                            <span class="input-group-text cursor-pointer" id="basic-default-password3"><i
                                    class="bx bx-hide"></i></span>
                        </div>
                        @error('password')
                            <div style="color:red">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="Masukan Email"
                            value="{{ old('email') }}" required
                            style="@if ($errors->has('email')) border-color:red; @endif" />
                        @error('email')
                            <div style="color:red">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="dropdown-custom selectpicker w-100"
                            data-style="btn-default" style="@if ($errors->has('status')) border-color:red; @endif">
                            <option selected disabled>{{ old('status', 'Pilih Status') }}</option>
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                        @error('status')
                            <div style="color:red">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="role" class="form-label">Role</label>
                        <select id="role" name="role" class="selectpicker w-100 dropdown-custom"
                            style="@if ($errors->has('role')) border-color:red; @endif" data-style="btn-default">
                            <option selected disabled>{{ old('role', 'Pilih Role') }}</option>
                            @foreach ($listRole as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('role')
                            <div style="color:red">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6 " id="selectOutletMultiple">
                        <label for="outlet_id">Outlet <span class="text-danger ">*</span></label>
                        <select  name="outlet_id[]"  id="outlet_id_multiple" class="select2InsideModal form-select w-100"
                            style="width: 100% !important;" required multiple>
                            <option disabled>Pilih Outlet</option>
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}">
                                    {{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6  d-none" id="selectOutletSingle">
                        <label for="outlet_id">Outlet <span class="text-danger ">*</span></label>
                        <select name="outlet_id[]" id="outlet_id_single" class="select2InsideModal form-select w-100"
                            style="width: 100% !important;" required>
                            <option disabled>Pilih Outlet</option>
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}">
                                    {{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="pt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-sm-3 me-1">Submit</button>
                    {{-- <button type="reset" class="btn btn-label-secondary">Cancel</button> --}}
                </div>
            </form>
        </div>
    </div>

    @push('js')
        <script>
            $(".select2InsideModal").select2({
                // closeOnSelect: false
            });

            // Pantau perubahan pada select role
            $('#role').on('change', function() {
                // Ambil nilai terpilih dan ubah ke huruf kecil
                const selectedRoleName = $('#role option:selected').text().toLowerCase();

                // Sembunyikan kedua elemen select
                $('#selectOutletMultiple').addClass('d-none');
                $('#selectOutletSingle').addClass('d-none');

                // Nonaktifkan atribut name kedua elemen
                $('#outlet_id_multiple').removeAttr('name');
                $('#outlet_id_single').removeAttr('name');

                if (selectedRoleName === 'kasir') {
                    // Tampilkan select single dan tambahkan atribut name
                    $('#selectOutletSingle').removeClass('d-none');
                    $('#outlet_id_single').attr('name', 'outlet_id[]');
                } else {
                    // Tampilkan select multiple dan tambahkan atribut name
                    $('#selectOutletMultiple').removeClass('d-none');
                    $('#outlet_id_multiple').attr('name', 'outlet_id[]');
                }
            });
        </script>
    @endpush
@endsection
