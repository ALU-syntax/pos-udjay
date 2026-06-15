@extends('layouts.app')
@push('css')
    <style>
        .hak-akses-avatar-group .avatar-sm + .avatar-sm {
            margin-left: -.85rem;
        }
    </style>
@endpush

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
                                @php
                                    $avatarColors = ['bg-primary', 'bg-success', 'bg-warning'];
                                @endphp
                                <ul class="list-unstyled d-flex align-items-center avatar-group hak-akses-avatar-group mb-0">
                                    @foreach ($data->users->take(3) as $user)
                                        @php
                                            $userName = trim($user->name ?? '');
                                            $initials = collect(preg_split('/\s+/', $userName))
                                                ->filter()
                                                ->take(2)
                                                ->map(fn($word) => mb_substr($word, 0, 1))
                                                ->implode('');
                                            $initials = strtoupper($initials ?: '?');
                                        @endphp
                                        <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                            class="avatar avatar-sm pull-up" aria-label="{{ $userName ?: 'User' }}"
                                            data-bs-original-title="{{ $userName ?: 'User' }}">
                                            <span
                                                class="avatar-title rounded-circle border border-white {{ $avatarColors[$loop->index % count($avatarColors)] }}">
                                                {{ $initials }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="d-flex justify-content-between align-items-end">
                                <div class="role-heading">
                                    <h4 class="mb-1">{{ $data->name }}</h4>
                                    <a href="{{ route('employee/hak-akses/role/edit', $data->id) }}">Edit Hak Akses
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
        </script>
    @endpush
@endsection
