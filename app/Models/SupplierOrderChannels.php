<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierOrderChannels extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function channelType()
    {
        return $this->belongsTo(SupplierChannelType::class, 'channel_type_id');
    }
}
