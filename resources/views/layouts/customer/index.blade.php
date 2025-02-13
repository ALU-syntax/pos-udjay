@extends('layouts.app')
@section('content')
    <style>
        .fancybox {
            z-index: 1100;
            /* Pastikan z-index Fancybox lebih tinggi dari modal */
        }

        .modal {
            z-index: 1050;
            /* Set z-index untuk modal */
        }

        .modal-backdrop {
            z-index: 1049;
        }
    </style>
    <div class="main-content">
        <div class="card text-center">
            <h5 class="card-header">Customer</h5>
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
                @can('create membership/customer')
                    <a href="{{ route('membership/customer/create') }}" type="button"
                        class="btn btn-primary btn-round ms-auto action"><i class="fa fa-plus"></i>Tambah Customer</a>
                @endcan
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
            const datatable = 'customer-table';

            $(".select2InsideModal").select2({
                dropdownParent: $("#modal_action")
            });

            handleAction(datatable);
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
