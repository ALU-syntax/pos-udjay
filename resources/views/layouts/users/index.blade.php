@extends('layouts.app')
@section('content')
    <div class="main-content">
        <div class="card text-center">
            <h5 class="card-header">Users</h5>
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
            <div class="card-header d-flex justify-content-end">
                <a href="{{ route('employee/user/create') }}" type="button" class="btn btn-primary ">Tambah User</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    {!! $dataTable->table() !!}
                </div>
            </div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}

        <script>
            var success = "{{ session('success') }}";
            const datatable = 'user-table';

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
