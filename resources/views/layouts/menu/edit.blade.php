@extends('layouts.app')
@section('content')
    <div class="card mb-4">
        <h5 class="card-header">Menu Edit</h5>
        @if (session()->has('error'))
            <div class="alert alert-danger mt-2 mx-4" role="alert">
                {{ session('error') }}
            </div>
        @endif
        <div class="card-body">
            <form action="{{ route('konfigurasi/menu/update', $data->id) }}" method="POST" enctype="multipart/form-data"
                class="needs-validation ">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Nama</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Masukan Nama"
                            value="{{ old('name', $data->name) }}" required
                            style="@if ($errors->has('name')) border-color:red; @endif" />
                        @error('name')
                            <div style="color: red">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="url">URL</label>
                        <input type="text" name="url" id="url" class="form-control" placeholder="Masukan url"
                            value="{{ old('url', $data->url) }}" required
                            style="@if ($errors->has('url')) border-color:red; @endif"
                            value="{{ old('orders') }}" />
                        @error('url')
                            <div style="color: red">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="category">Category</label>
                        <input type="text" name="category" id="category" class="form-control"
                            placeholder="Masukan category" value="{{ old('category', $data->category) }}" required
                            style="@if ($errors->has('category')) border-color:red; @endif" />
                        @error('category')
                            <div style="color: red">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="icon">icon</label>
                        <input type="text" name="icon" id="icon" class="form-control" placeholder="Masukan icon"
                            value="{{ old('icon', $data->icon) }}" required
                            style="@if ($errors->has('icon')) border-color:red; @endif" />
                        @error('icon')
                            <div style="color: red">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="orders">No. Urut</label>
                        <input type="text" name="orders" id="orders" class="form-control"
                            placeholder="Masukan Nomor Urut"
                            style="@if ($errors->has('orders')) border-color:red; @endif"
                            value="{{ old('orders', $data->orders) }}" />
                        @error('orders')
                            <div style="color: red">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6 ">
                        <label class="d-block form-label">Level Menu</label>
                        <div class="form-check ">
                            <input name="level_menu" class="form-check-input" type="radio" value="main_menu"
                                id="mainMenu" {{ $data->main_menu_id == null ? 'checked' : '' }}>
                            <label class="form-check-label" for="collapsible-address-type-home">Main Menu</label>
                        </div>
                        <div class="form-check ">
                            <input name="level_menu" class="form-check-input" type="radio" value="sub_menu" id="subMenu"
                                {{ $data->main_menu_id != null ? 'checked' : '' }}>
                            <label class="form-check-label" for="collapsible-address-type-office">Sub Menu</label>
                        </div>
                    </div>
                    <div class="col-md-6 @if ($data->main_menu_id == null) d-none @endif" id="main_menu_wrapper">
                        <label for="main_menu" class="form-label">Main Menu</label>
                        <select id="main_menu" name="main_menu" class="selectpicker w-100" style="color:black;"
                            data-style="btn-default">
                            @if ($data->main_menu_id == null)
                                <option selected disabled>{{ old('main_menu', 'Pilih Main menu') }}</option>
                            @endif
                            @foreach ($mainMenus as $key => $value)
                                @if ($data->main_menu_id != null && $data->main_menu_id == $value)
                                    <option selected value="{{ $value }}">
                                        {{ old('main_menu', $key) }}</option>
                                @endif
                                <option value="{{ $value }}">{{ $key }}</option>
                            @endforeach
                        </select>
                        @error('main_menu')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    {{-- <div class="col-md-6">
                    <label for="" class="form-label d-block mb-2">Permissions</label>
                    <div class="col d-flex justify-content-between pe-5">
                        @foreach ($permissions as $key => $item)
                            <div class="form-check ">
                                <input class="form-check-input form-check-line" name="permissions[]" type="checkbox"
                                    id="{{ $key }}_permissions" value="{{ $key }}" @if ($item != null) checked @endif>
                                <label class="form-check-label" for="id">{{ $key }}</label>
                            </div>
                        @endforeach
                    </div>
                </div> --}}
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
            handleMenuChange();

            function handleMenuChange() {
                $('[name="level_menu"]').on('change', function() {
                    console.log(this.value)
                    if (this.value == 'sub_menu') {
                        $('#main_menu_wrapper').removeClass('d-none')
                    } else {
                        $('#main_menu_wrapper').addClass('d-none')
                    }
                });
            }
        </script>
    @endpush
@endsection
