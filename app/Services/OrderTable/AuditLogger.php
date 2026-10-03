<?php

namespace App\Services\OrderTable;

use App\Models\OrderTable\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    private const REDACTED = '[REDACTED]';

    public function __construct(private readonly Request $request) {}

    public function log(
        string $action,
        Model $model,
        ?array $before = null,
        ?array $after = null,
        ?array $metadata = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $this->request->user()?->getAuthIdentifier(),
            'action' => $action,
            'subject_type' => $model->getMorphClass(),
            'subject_id' => $model->getKey(),
            'before' => $this->redact($before),
            'after' => $this->redact($after),
            'metadata' => $this->redact($metadata),
            'ip_address' => $this->request->ip(),
            'user_agent' => mb_substr((string) $this->request->userAgent(), 0, 500),
        ]);
    }

    private function redact(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach ($values as $key => $value) {
            if (is_string($key) && preg_match('/password|secret|token|api.?key|authorization|card|cvv|pin/i', $key)) {
                $values[$key] = self::REDACTED;

                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->redact($value);
            }
        }

        return $values;
    }
}
