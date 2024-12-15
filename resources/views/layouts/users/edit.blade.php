@extends('layouts.app')
@section('content')
    <div class="card mb-4">
        <h5 class="card-header">User Edit</h5>
        <div class="card-body">
            <form action="{{ route('employee/user/update', $data->id) }}" method="POST" enctype="multipart/form-data"
                class="needs-validation @if ($errors->any()) was-validated @endif">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Nama</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Masukan Nama"
                            value="{{ old('name', $data->name) }}" required />
                        @error('name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" name="username" id="username" class="form-control"
                            placeholder="Masukan Username" value="{{ old('username', $data->username) }}" required />
                        @error('username')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6 form-password-toggle">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-group input-group-merge">
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="············" aria-describedby="basic-default-password3"
                                value="{{ old('password') }}">
                            <span class="input-group-text cursor-pointer" id="basic-default-password3"><i
                                    class="bx bx-hide"></i></span>
                        </div>
                        <small><i>*Kosongkan jika tidak ingin merubah password</i></small>
                        @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="Masukan Email"
                            value="{{ old('email', $data->email) }}" required />
                        @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="dropdown-custom selectpicker w-100"
                            data-style="btn-default" style="color: black;">
                            {{-- <option value="{{ old($data->status) }}" selected>
                            {{ old('status', \App\Models\User::STATUS[$data->status]) }}</option> --}}
                            <option value="1" @if ($data->status == 1) seleccted @endif>Aktif</option>
                            <option value="0" @if ($data->status == 0) seleccted @endif>Tidak Aktif</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="role" class="form-label">Role</label>
                        <select id="role" name="role" class="dropdown-custom selectpicker w-100"
                            style="color:black;" data-style="btn-default">
                            {{-- <option value="{{ old($data->role) }}" selected>
                            {{ old('role', $data->getRoleNames()[0]) }}</option> --}}
                            <option disabled>Pilih Role</option>
                            @foreach ($roles as $item)
                                @if ($data->role == $item->id)
                                    <option value="{{ $item->id }}" selected>{{ $item->name }}</option>
                                @else
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('role')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="outlet_id">Outlet <span class="text-danger ">*</span></label>
                        <select name="outlet_id[]" id="outlet_id" class="select2InsideModal form-select w-100"
                            style="width: 100% !important;" required multiple>
                            <option disabled>Pilih Outlet</option>
                            @foreach ($outlets as $outlet)
                                
                                    <option value="{{ $outlet->id }}" @foreach (json_decode($data->outlet_id) as $outletData) @if ($outlet->id == $outletData) selected @endif @endforeach>
                                        {{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="pt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-sm-3 me-1">Update</button>
                    {{-- <button type="reset" class="btn btn-label-secondary">Cancel</button> --}}
                </div>
            </form>
        </div>
    </div>

    @push('js')
        <script>
            $(".select2InsideModal").select2({
                closeOnSelect: false
            });
        </script>
    @endpush()
@endsection
