<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Modifiers extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        // Tampil di menu Order Table (pelanggan); kasir tetap melihat semuanya.
        'show_in_order_table' => 'boolean',
    ];

    public function modifierGroup(){
        return $this->belongsTo(ModifierGroup::class, "modifiers_group_id", 'id');
    }

    public function getItemTransactionsAttribute()
    {
        if (!$this->id) {
            return collect(); // tidak ada id, kembalikan collection kosong
        }

        return TransactionItem::withoutGlobalScopes()
            ->whereRaw("JSON_CONTAINS(modifier_id, JSON_OBJECT('id', ?), '$')", [$this->id])
            ->get();
    }

}
