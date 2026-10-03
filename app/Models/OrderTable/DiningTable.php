<?php

namespace App\Models\OrderTable;

use App\Models\Outlets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiningTable extends Model
{
    use SoftDeletes;

    protected $table = 'ot_dining_tables';

    protected $guarded = ['id', 'qr_token'];

    protected $hidden = ['qr_token'];

    protected $casts = [
        'outlet_id' => 'integer',
        'qr_active' => 'boolean',
        'capacity' => 'integer',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlets::class, 'outlet_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TableSession::class, 'table_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'table_id');
    }
}
