<?php

namespace App\Models\OrderTable;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ot_audit_logs';

    protected $guarded = ['id'];

    protected $casts = [
        'user_id' => 'integer',
        'subject_id' => 'integer',
        'before' => 'array',
        'after' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
