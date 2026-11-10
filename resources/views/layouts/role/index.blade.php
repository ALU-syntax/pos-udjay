@extends('layouts.app')

@section('content')
    @include('layouts.reports.partials.ui-styles')

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Employee</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-user-tag"></i></span>
                    Role
                </h1>
                <p class="ob-header__desc">Kelompokkan user berdasarkan jabatan. Akses tiap role diatur di menu Hak Akses.</p>
            </div>
            @can('create employee/roles')
                <a href="{{ route('employee/roles/create') }}" class="btn ob-btn ob-btn-primary">
                    <i class="fas fa-plus" aria-hidden="true"></i> Tambah Role
                </a>
            @endcan
        </div>

        <div class="ob-notice" role="note">
            <i class="fas fa-lock" aria-hidden="true"></i>
            <span>Role bertanda <strong>Role sistem</strong> dipakai langsung oleh aplikasi, sehingga tidak dapat diubah namanya atau dihapus.</span>
        </div>

        {{-- Tabel --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Daftar Role</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Role yang masih dipakai user tidak dapat dihapus.</span>
                </div>
            </div>
            <div class="ob-table-wrap">
                {!! $dataTable->table(['class' => 'table w-100']) !!}
            </div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            $(function () {
                const table = window.LaravelDataTables['role-table'];

                const success = @json(session('success'));
                const error = @json(session('error'));
                if (success) showToast('success', success);
                if (error) showToast('error', error);

                $('.main-content').on('click', '.delete-role', function (event) {
                    event.preventDefault();
                    const button = this;

                    Swal.fire({
                        title: 'Hapus role?',
                        text: 'Role ' + $(button).data('name') + ' beserta pengaturan hak aksesnya akan dihapus.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: button.href,
                            method: 'DELETE',
                            data: { _token: @json(csrf_token()) },
                            success: function (response) {
                                showToast(response.status || 'success', response.message || 'Role berhasil dihapus.');
                                table.ajax.reload(null, false);
                            },
                            error: function (xhr) {
                                showToast('error', xhr.responseJSON?.message || 'Gagal menghapus role');
                            }
                        });
                    });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            /* Sel dua baris: nama role + penanda role sistem */
            .ob-cell-stack {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: .3rem;
                white-space: nowrap;
            }

            .ob-role-name {
                font-weight: 600;
                color: var(--ob-text);
            }

            .ob-count-cell {
                color: var(--ob-text-2);
                font-variant-numeric: tabular-nums;
                white-space: nowrap;
            }

            .ob-icon-btn--disabled {
                opacity: .4;
                cursor: not-allowed;
            }
        </style>
    @endpush
@endsection
