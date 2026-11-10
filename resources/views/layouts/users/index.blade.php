@extends('layouts.app')

@section('content')
    @include('layouts.reports.partials.ui-styles')

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Employee</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-users"></i></span>
                    Users
                </h1>
                <p class="ob-header__desc">Kelola akun back office dan kasir, beserta role dan outlet yang dapat diakses.</p>
            </div>
            @can('create employee/users')
                <a href="{{ route('employee/user/create') }}" class="btn ob-btn ob-btn-primary">
                    <i class="fas fa-plus" aria-hidden="true"></i> Tambah User
                </a>
            @endcan
        </div>

        {{-- Tabel --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Daftar User</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> User yang dihapus otomatis dinonaktifkan dan tidak tampil di daftar.</span>
                </div>
                <div class="ob-list-filters">
                    <div class="ob-field ob-list-filter">
                        <label for="role-filter"><i class="fas fa-user-tag" aria-hidden="true"></i> Role</label>
                        <select id="role-filter" class="form-control">
                            <option value="">Semua role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ob-field ob-list-filter">
                        <label for="status-filter"><i class="fas fa-toggle-on" aria-hidden="true"></i> Status</label>
                        <select id="status-filter" class="form-control">
                            <option value="">Semua status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
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
                const table = window.LaravelDataTables['user-table'];

                $('#role-filter, #status-filter').select2({ width: '100%', minimumResultsForSearch: 8 })
                    .on('change', () => table.ajax.reload());

                const success = @json(session('success'));
                if (success) {
                    showToast('success', success);
                }

                $('.main-content').on('click', '.delete-user', function (event) {
                    event.preventDefault();
                    const button = this;

                    Swal.fire({
                        title: 'Hapus user?',
                        text: 'User ' + $(button).data('name') + ' akan dinonaktifkan dan tidak bisa login lagi.',
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
                                showToast(response.status || 'success', response.message || 'User berhasil dihapus.');
                                table.ajax.reload(null, false);
                            },
                            error: function (xhr) {
                                showToast('error', xhr.responseJSON?.message || 'Gagal menghapus user');
                            }
                        });
                    });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            /* Sel user: inisial + nama + username */
            .ob-user-cell {
                display: flex;
                align-items: center;
                gap: .7rem;
                min-width: 200px;
            }

            .ob-user-avatar {
                display: inline-grid;
                place-items: center;
                flex-shrink: 0;
                width: 38px;
                height: 38px;
                border-radius: 50%;
                background: var(--ob-primary-soft);
                color: var(--ob-primary);
                font-size: .8rem;
                font-weight: 700;
            }

            .ob-user-name {
                font-weight: 600;
                color: var(--ob-text);
            }

            .ob-cell-stack {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: .2rem;
                white-space: nowrap;
            }
        </style>
    @endpush
@endsection
