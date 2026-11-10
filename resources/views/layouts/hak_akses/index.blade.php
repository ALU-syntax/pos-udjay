@extends('layouts.app')

@section('content')
    @include('layouts.reports.partials.ui-styles')

    @php
        $initials = fn ($name) => mb_strtoupper(collect(preg_split('/\s+/', trim((string) $name)))
            ->filter()->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->implode('') ?: '?');
    @endphp

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Employee</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-key"></i></span>
                    Hak Akses
                </h1>
                <p class="ob-header__desc">Atur menu dan aksi yang boleh dipakai setiap role, serta akses tambahan untuk user tertentu.</p>
            </div>
        </div>

        {{-- Role --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-user-tag" aria-hidden="true"></i> Hak Akses Role</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Perubahan berlaku untuk semua user dengan role tersebut.</span>
                </div>
            </div>
            <div class="ob-role-grid">
                @foreach ($roles as $role)
                    @php
                        $members = $usersByRole->get($role->id, collect());
                        $superAdmin = strtolower($role->name) === 'super admin';
                    @endphp
                    <div class="ob-role-card">
                        <div class="ob-role-card__head">
                            <div>
                                <h3 class="ob-role-card__name">{{ $role->name }}</h3>
                                @if ($role->isSystemRole())
                                    <span class="ob-chip"><i class="fas fa-lock" aria-hidden="true"></i> Role sistem</span>
                                @endif
                            </div>
                            @if ($members->isNotEmpty())
                                <ul class="ob-avatar-stack" aria-label="{{ $members->count() }} user dengan role {{ $role->name }}">
                                    @foreach ($members->take(3) as $member)
                                        <li class="ob-avatar" title="{{ $member->name }}">{{ $initials($member->name) }}</li>
                                    @endforeach
                                    @if ($members->count() > 3)
                                        <li class="ob-avatar ob-avatar--more">+{{ $members->count() - 3 }}</li>
                                    @endif
                                </ul>
                            @endif
                        </div>
                        <dl class="ob-role-card__stats">
                            <div>
                                <dt>User</dt>
                                <dd>{{ number_format($members->count(), 0, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt>Hak Akses</dt>
                                <dd>{{ $superAdmin ? 'Semua' : number_format($role->permissions_count, 0, ',', '.') }}</dd>
                            </div>
                        </dl>
                        <div class="ob-role-card__foot">
                            @if ($superAdmin)
                                <span class="ob-cell-meta"><i class="fas fa-shield-alt" aria-hidden="true"></i> Selalu memiliki semua akses</span>
                            @else
                                @can('update employee/hak-akses')
                                    <a href="{{ route('employee/hak-akses/role/edit', $role->id) }}" class="btn ob-btn ob-btn-outline ob-btn-sm">
                                        <i class="fas fa-key" aria-hidden="true"></i> Atur Hak Akses
                                    </a>
                                @endcan
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- User --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-user-plus" aria-hidden="true"></i> Akses Tambahan User</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Akses tambahan menambah akses di luar role, tanpa mengubah role user.</span>
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
                const table = window.LaravelDataTables['user-permission-table'];

                $('#role-filter').select2({ width: '100%', minimumResultsForSearch: 8 })
                    .on('change', () => table.ajax.reload());

                const success = @json(session('success'));
                const error = @json(session('error'));
                if (success) showToast('success', success);
                if (error) showToast('error', error);
            });
        </script>
    @endpush

    @push('css')
        <style>
            .ob-role-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 1rem;
                padding: 1.25rem;
            }

            .ob-role-card {
                display: flex;
                flex-direction: column;
                gap: .9rem;
                padding: 1rem 1.1rem;
                border: 1px solid var(--ob-border);
                border-radius: var(--ob-radius-sm);
                background: var(--ob-surface-2);
            }

            .ob-role-card__head {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: .75rem;
            }

            .ob-role-card__name {
                margin: 0 0 .3rem;
                font-size: 1rem;
                font-weight: 700;
                color: var(--ob-text);
                overflow-wrap: anywhere;
            }

            .ob-role-card__stats {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: .5rem;
                margin: 0;
            }

            .ob-role-card__stats dt {
                font-size: .7rem;
                font-weight: 600;
                letter-spacing: .05em;
                text-transform: uppercase;
                color: var(--ob-muted);
            }

            .ob-role-card__stats dd {
                margin: 0;
                font-size: 1.15rem;
                font-weight: 700;
                color: var(--ob-text);
                font-variant-numeric: tabular-nums;
            }

            .ob-role-card__foot {
                margin-top: auto;
            }

            .ob-avatar-stack {
                display: flex;
                margin: 0;
                padding: 0;
                list-style: none;
            }

            .ob-avatar {
                display: inline-grid;
                place-items: center;
                width: 30px;
                height: 30px;
                border: 2px solid var(--ob-surface-2);
                border-radius: 50%;
                background: var(--ob-primary-soft);
                color: var(--ob-primary);
                font-size: .68rem;
                font-weight: 700;
            }

            .ob-avatar + .ob-avatar {
                margin-left: -.55rem;
            }

            .ob-avatar--more {
                background: var(--ob-border);
                color: var(--ob-text-2);
            }

            .ob-cell-stack {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: .2rem;
                white-space: nowrap;
            }

            .ob-user-name {
                font-weight: 600;
                color: var(--ob-text);
            }
        </style>
    @endpush
@endsection
