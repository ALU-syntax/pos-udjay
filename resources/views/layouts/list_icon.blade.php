@extends('layouts.app')
@section('content')
    @push('css')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
            integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
            crossorigin="anonymous" referrerpolicy="no-referrer" />
    @endpush
    <div class="main-content">
        <div class="card text-center">
            <h5 class="card-header">List Icon</h5>
        </div>

        <div class="card-body">
            <div class="container py-4">

                {{-- Header --}}
                <div class="row mb-4">
                    <div class="col">
                        <h3 class="fw-bold">Font Awesome Icon List</h3>
                        <p class="text-muted mb-0">
                            Total: {{ count(config('fontawesome')) }} icons
                        </p>
                    </div>
                </div>

                {{-- Icon Grid --}}
                <div class="row g-3">
                    @foreach (config('fontawesome') as $class => $label)
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                            <div class="card h-100 shadow-sm icon-card text-center p-3 mb-0">
                                <div class="icon-preview mb-2">
                                    <i class="{{ $class }} fa-2x"></i>
                                </div>

                                <div class="small fw-semibold text-truncate">
                                    {{ $label }}
                                </div>

                                <div class="text-muted small text-truncate">
                                    {{ $class }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>

    </div>
@endsection
