<?php

namespace App\Services\OrderTable;

use RuntimeException;

class CashierError extends RuntimeException
{
    /** @param array<string, mixed> $details Data bisnis yang aman diteruskan ke kasir (mis. alasan voucher gugur). */
    public function __construct(
        public readonly string $businessCode,
        public readonly int $status = 409,
        public readonly array $details = [],
    ) {
        parent::__construct($businessCode);
    }

    public function render()
    {
        return response()->json(array_filter([
            'status' => 'error', 'code' => $this->businessCode,
            'message' => $this->details['message'] ?? $this->businessCode,
            'details' => $this->details ?: null,
        ], fn ($value) => $value !== null), $this->status);
    }

    public function report(): bool
    {
        return true;
    }
}
