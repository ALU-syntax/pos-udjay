<?php

namespace App\Services\B2b\Reminder;

use RuntimeException;
use Throwable;

class WhatsAppGatewayException extends RuntimeException
{
    /** Kode dari gateway yang berarti mencoba ulang tidak akan berhasil. */
    private const PERMANENT_CODES = ['not_on_whatsapp', 'invalid_phone', 'invalid_text', 'invalid_document', 'unauthorized', 'not_configured'];

    public function __construct(string $message, public readonly string $errorCode, public readonly int $httpStatus = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function isPermanent(): bool
    {
        return in_array($this->errorCode, self::PERMANENT_CODES, true);
    }
}
