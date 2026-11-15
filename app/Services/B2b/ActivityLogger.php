<?php

namespace App\Services\B2b;

use App\Models\B2b\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public function log(string $action, Model $subject, ?array $changes = null, ?int $userId = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $userId ?? auth()->id(),
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }
}
