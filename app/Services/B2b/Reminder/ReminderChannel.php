<?php

namespace App\Services\B2b\Reminder;

use App\Models\B2b\Customer;
use App\Models\B2b\ReminderLog;
use Illuminate\Support\Collection;

/** Pengirim reminder per channel (email sekarang, WhatsApp/Baileys menyusul). */
interface ReminderChannel
{
    /**
     * Kirim reminder. Melempar exception kalau gagal supaya job mencoba ulang.
     *
     * @param  Collection<int, array{invoice: \App\Models\B2b\Invoice, offset: int}>  $items
     * @param  callable(array{subject: ?string, body: ?string, attachments: list<string>}): void  $beforeSend
     *                                                                                                         dipanggil dengan isi pesan sebelum dikirim, supaya log tetap menyimpan isi walau pengiriman gagal
     * @return string|null id pesan dari provider (kalau ada)
     */
    public function send(ReminderLog $log, Customer $customer, Collection $items, callable $beforeSend): ?string;
}
