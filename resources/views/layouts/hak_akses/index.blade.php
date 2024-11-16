@extends('layouts.app')
@section('content')
    <div class="main-content">
        <div class="card text-center">
            <h5 class="card-header">Hak Akses</h5>
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

        <div class="row mt-2">
            @foreach ($roles as $data)
                <div class="col-xl-4 col-lg-6 col-md-6 mt-2">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <h6 class="fw-normal">Total {{ count($data->users) }} User</h6>
                                <ul class="list-unstyled d-flex align-items-center avatar-group mb-0">
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-sm pull-up" aria-label="Vinnie Mostowy"
                                        data-bs-original-title="Vinnie Mostowy">
                                        <img class="rounded-circle" src="/img/avatars/1.png" alt="Avatar">
                                    </li>
                                </ul>
                            </div>
                            <div class="d-flex justify-content-between align-items-end">
                                <div class="role-heading">
                                    <h4 class="mb-1">{{ $data->name }}</h4>
                                    <a href="{{ route('konfigurasi/hak-akses/role/edit', $data->id) }}">Edit Hak Akses
                                        Role</a>
                                </div>
                                <a href="javascript:void(0);" class="text-muted"><i class="bx bx-copy"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card mt-4">
            <div class="card-header d-flex justify-content-start" style="zoom: 0.8">
                <h4>Hak Akses User</h4>
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
        </script>
    @endpush
@endsection
