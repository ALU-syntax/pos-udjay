<?php

namespace App\Models\OrderTable;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'ot_orders';

    protected $guarded = ['id'];
}
