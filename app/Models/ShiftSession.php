<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftSession extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'last_sync_at' => 'datetime',
        'closed_at'    => 'datetime',
    ];

    public function pettyCash()
    {
        return $this->belongsTo(PettyCash::class, 'petty_cash_id', 'id');
    }

    public function outlet()
    {
        return $this->belongsTo(Outlets::class, 'outlet_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function parentSession()
    {
        return $this->belongsTo(ShiftSession::class, 'parent_session_id', 'id');
    }

    public function childSessions()
    {
        return $this->hasMany(ShiftSession::class, 'parent_session_id', 'id');
    }
}
