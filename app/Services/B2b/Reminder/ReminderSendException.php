<?php

namespace App\Services\B2b\Reminder;

use RuntimeException;

/**
 * Kegagalan kirim reminder yang tidak boleh dicoba ulang otomatis, mis. nomor tidak terdaftar di WhatsApp,
 * atau sebagian pesan sudah terkirim (mencoba ulang akan mengirim dobel ke customer).
 */
class ReminderSendException extends RuntimeException
{
    public static function permanent(string $message): self
    {
        return new self($message);
    }
}
