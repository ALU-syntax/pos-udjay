<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function itemTransaction(){
        return $this->hasMany(TransactionItem::class, 'transaction_id', 'id');
    }

    public function payments(){
        return $this->belongsTo(Payment::class, 'tipe_pembayaran', 'id');
    }

    public function outlet(){
        return $this->belongsTo(Outlets::class, 'outlet_id', 'id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function pajak(){
        $pajakIds = json_decode($this->total_pajak, true);

        if(!empty($pajakIds)){
            $tmpIdPajak = [];
            foreach($pajakIds as $data){
                array_push($tmpIdPajak, $data['id']);
            }
            return Taxes::whereIn('id', $tmpIdPajak)->get();
        }

        return collect([]);
    }

    public function customer(){
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function refundTransactions(){
        return $this->hasMany(RefundTransaction::class, 'transaction_id', 'id');
    }

}
