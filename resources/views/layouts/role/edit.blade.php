@extends('layouts.app')
@section('content')
    <div class="card mb-4">
        <h5 class="card-header">Role Edit</h5>
        @if (session()->has('error'))
            <div class="alert alert-danger mt-2 mx-4" role="alert">
                {{ session('error') }}
            </div>
        @endif
        <div class="card-body">
            <form action="{{ route('konfigurasi/roles/update', $data->id) }}" method="POST" enctype="multipart/form-data"
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
                        <label class="form-label" for="guard_name">Guard Name</label>
                        <input type="text" name="guard_name" id="guard_name" class="form-control"
                            placeholder="Masukan Guard Name" value="{{ old('guard_name', $data->guard_name) }}" required
                            style="@if ($errors->has('guard_name')) border-color:red; @endif" />
                        @error('guard_name')
                            <div style="color: red">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
                <div class="pt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-sm-3 me-1">Submit</button>
                    {{-- <button type="reset" class="btn btn-label-secondary">Cancel</button> --}}
                </div>
            </form>
        </div>
    </div>
@endsection
