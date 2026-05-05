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

        .customer-member-link {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: inherit;
            text-decoration: none;
            min-width: 220px;
        }

        .customer-member-link:hover .customer-member-name {
            color: var(--bs-primary, #0d6efd);
        }

        .customer-member-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 40px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #eef4ff;
            color: #2563eb;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .customer-member-info {
            display: flex;
            flex-direction: column;
            min-width: 0;
            line-height: 1.25;
        }

        .customer-member-name {
            color: #1f2937;
            font-weight: 600;
            transition: color .15s ease;
        }

        .customer-member-email {
            color: #6b7280;
            font-size: 12px;
            word-break: break-word;
        }

        .customer-progress-info,
        .customer-membership-info,
        .customer-date-info {
            display: inline-flex;
            flex-direction: column;
            gap: 4px;
            line-height: 1.2;
        }

        .customer-point-link {
            display: inline-flex;
            align-items: baseline;
            gap: 6px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 700;
        }

        .customer-point-link:hover {
            color: var(--bs-primary, #0d6efd);
            text-decoration: none;
        }

        .customer-exp-chip {
            display: inline-flex;
            align-items: baseline;
            gap: 6px;
            color: #475569;
        }

        .customer-progress-value {
            font-weight: 700;
        }

        .customer-progress-label {
            color: #6b7280;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .customer-membership-level {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: fit-content;
            min-height: 26px;
            padding: 5px 11px;
            border: 1px solid transparent;
            border-radius: 999px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .18);
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0;
            text-decoration: none;
            transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
        }

        .customer-membership-level:hover {
            box-shadow: 0 5px 12px rgba(15, 23, 42, .22);
            filter: brightness(.97);
            text-decoration: none;
            transform: translateY(-1px);
        }

        .customer-membership-level:focus {
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .22), 0 1px 2px rgba(15, 23, 42, .18);
            outline: none;
        }

        .customer-membership-community,
        .customer-date-time {
            color: #6b7280;
            font-size: 12px;
        }

        .customer-date-main {
            color: #1f2937;
            font-weight: 600;
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
