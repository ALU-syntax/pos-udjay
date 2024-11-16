@extends('layouts.app')
@section('content')
    <div class="main-content">
        <div class="card text-center">
            <h5 class="card-header">Role</h5>
        </div>
        @if (session()->has('success'))
            <div class="alert alert-success mt-2" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger mt-2" role="alert">
                {{ session('error') }}
            </div>
        @endif
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-end" style="zoom: 0.8">
                @can('create konfigurasi/menu')
                    <a href="{{ route('konfigurasi/roles/create') }}" type="button" class="btn btn-primary ">Tambah</a>
                @endcan
            </div>
            <div class="card-body">
                {!! $dataTable->table() !!}
            </div>
        </div>
    </div>

    @push('js')
    {!! $dataTable->scripts() !!}

    <script>
        var success = "{{ session('success') }}";
        const datatable = 'role-table';

        handleDelete(datatable);
        if (success) {
            Swal.fire({
                title: 'Success!',
                // text: 'Data User Berhasil Disimpan',
                text: success,
                icon: 'success',
                type: 'success',
                customClass: {
                    confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
            });
        }
    </script>
    @endpush
@endsection
