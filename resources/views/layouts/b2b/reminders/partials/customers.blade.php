{{-- Tab Customer menu Reminder: jenis pengiriman per customer dan data kontak yang perlu dilengkapi. Butuh $customers dari ReminderController. --}}
@php
    $rupiah = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
    $needsFix = $customers->filter(fn ($customer) => $customer->issues !== [])->count();
    $notConfigured = $customers->where('not_configured', true)->count();
    $active = $customers->where('reminder_enabled', true)->count();
    $canEdit = auth()->user()->can('update b2b/customers');
@endphp

<div class="ob-card">
    <div class="b2b-card__head">
        <div>
            <h2 class="b2b-card__title"><i class="fas fa-building" aria-hidden="true"></i> Pengaturan reminder per customer</h2>
            <p class="b2b-card__desc">Customer dengan reminder aktif, ditambah customer yang reminder-nya mati tapi masih punya tagihan tempo. Jadwal dan channel diatur di data customer.</p>
        </div>
    </div>

    <div class="b2b-reminder-summary">
        <span class="b2b-reminder-summary__item"><strong>{{ $active }}</strong> reminder aktif</span>
        <span class="b2b-reminder-summary__item {{ $needsFix ? 'is-danger' : '' }}"><strong>{{ $needsFix }}</strong> perlu dilengkapi</span>
        <span class="b2b-reminder-summary__item {{ $notConfigured ? 'is-warning' : '' }}"><strong>{{ $notConfigured }}</strong> belum diatur, masih punya tagihan</span>
    </div>

    @if ($customers->isEmpty())
        <div class="b2b-empty">
            <span class="b2b-empty__icon" aria-hidden="true"><i class="fas fa-bell-slash"></i></span>
            <p class="b2b-empty__text">Belum ada customer dengan reminder aktif atau tagihan tempo terbuka.</p>
        </div>
    @else
        <div class="ob-table-scroll">
            <table class="b2b-table b2b-reminder-customers">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Reminder</th>
                        <th>Kontak tujuan</th>
                        <th class="text-end">Tagihan terbuka</th>
                        <th>Kiriman terakhir</th>
                        <th><span class="visually-hidden">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        @php
                            $channels = (array) ($customer->reminder_channels ?? []);
                            $last = $customer->lastReminder;
                        @endphp
                        <tr class="{{ $customer->issues ? 'is-issue' : '' }}">
                            <td>
                                <div class="b2b-cust-cell">
                                    <span class="b2b-avatar b2b-avatar--sm b2b-avatar--{{ $customer->type }}" aria-hidden="true">{{ $customer->initials() }}</span>
                                    <div class="ob-cell-stack">
                                        @can('read b2b/customers')
                                            <a href="{{ route('b2b/customers/show', $customer->id) }}" class="b2b-cust-cell__name">{{ $customer->name }}</a>
                                        @else
                                            <span class="b2b-cust-cell__name">{{ $customer->name }}</span>
                                        @endcan
                                        <span class="ob-cell-meta b2b-mono">{{ $customer->code }}{{ $customer->is_active ? '' : ' · nonaktif' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="ob-cell-stack">
                                    @if ($customer->reminder_enabled)
                                        <span>
                                            @foreach ($channels as $channel)
                                                <span class="badge badge-primary">{{ \App\Models\B2b\Customer::REMINDER_CHANNELS[$channel] ?? $channel }}</span>
                                            @endforeach
                                        </span>
                                        <span class="ob-cell-meta">{{ \Illuminate\Support\Str::after($customer->reminderSummary(), '· ') }}</span>
                                        @if (in_array('whatsapp', $channels, true) && ! in_array('whatsapp', \App\Services\B2b\ReminderService::SUPPORTED_CHANNELS, true))
                                            <span class="ob-cell-meta">WhatsApp belum dikirim (pengirim belum dipasang)</span>
                                        @endif
                                    @else
                                        <span><span class="badge badge-warning">Belum diatur</span></span>
                                        <span class="ob-cell-meta">Tidak ada reminder otomatis</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="ob-cell-stack b2b-reminder-contact">
                                    <span class="{{ in_array('email', $channels, true) && ! filter_var(trim((string) $customer->email), FILTER_VALIDATE_EMAIL) && $customer->reminder_enabled ? 'text-danger' : '' }}">
                                        <i class="fas fa-envelope" aria-hidden="true"></i> {{ $customer->email ?: 'Email kosong' }}</span>
                                    <span class="{{ in_array('whatsapp', $channels, true) && blank($customer->phone) && $customer->reminder_enabled ? 'text-danger' : '' }}">
                                        <i class="fas fa-phone" aria-hidden="true"></i> {{ $customer->phone ?: 'Telepon kosong' }}</span>
                                    @foreach ($customer->issues as $issue)
                                        <span class="b2b-due b2b-due--late"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> {{ $issue }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="ob-cell-stack align-items-end">
                                    <span class="b2b-money fw-bold">{{ $rupiah($customer->open_outstanding) }}</span>
                                    <span class="ob-cell-meta">{{ (int) $customer->open_invoices_count }} invoice</span>
                                </div>
                            </td>
                            <td>
                                @if ($last)
                                    <div class="b2b-last-reminder">
                                        <div class="ob-cell-stack">
                                            <span><span class="badge badge-{{ $last->statusVariant() }}">{{ $last->statusLabel() }}</span></span>
                                            <span class="ob-cell-meta text-nowrap">{{ $last->reminder_date->translatedFormat('d M Y') }} · {{ $last->channelLabel() }}</span>
                                        </div>
                                        <button type="button" class="ob-icon-btn ob-icon-btn--sm" data-reminder-detail="{{ route('b2b/reminders/show', $last->id) }}"
                                            title="Lihat kiriman terakhir" aria-label="Lihat kiriman terakhir {{ $customer->name }}"><i class="fas fa-eye" aria-hidden="true"></i></button>
                                    </div>
                                @else
                                    <span class="ob-cell-meta">Belum pernah</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($canEdit)
                                    <a href="{{ route('b2b/customers/edit', $customer->id) }}" class="ob-icon-btn ob-icon-btn--sm" title="Ubah data & jadwal reminder" aria-label="Ubah data {{ $customer->name }}">
                                        <i class="fas fa-pen" aria-hidden="true"></i></a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@push('css')
    <style>
        .b2b-reminder-summary { display: flex; flex-wrap: wrap; gap: .5rem; padding: 0 1.25rem 1rem; }
        .b2b-reminder-summary__item { padding: .35rem .8rem; border: 1px solid var(--ob-border); border-radius: 999px; background: var(--ob-surface-2); font-size: .8rem; color: var(--ob-text-2); }
        .b2b-reminder-summary__item.is-danger { border-color: var(--ob-danger); background: #fef2f2; color: var(--ob-danger); }
        .b2b-reminder-summary__item.is-warning { border-color: #f59e0b; background: #fffbeb; color: #b45309; }
        .b2b-reminder-customers { min-width: 900px; }
        .b2b-reminder-customers td { vertical-align: middle; }
        .b2b-reminder-customers tr.is-issue td { background: #fff7f7; }
        .b2b-reminder-customers .badge { margin-left: 0 !important; margin-right: .25rem; }
        .b2b-reminder-contact { font-size: .82rem; }
        .b2b-reminder-contact .fas { width: 14px; color: var(--ob-muted); }
        .b2b-reminder-contact .text-danger .fas { color: inherit; }
        .b2b-last-reminder { display: flex; align-items: center; gap: .5rem; }
    </style>
@endpush
