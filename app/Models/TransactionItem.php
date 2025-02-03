<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function product(){
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function transaction(){
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    // Method untuk mendapatkan relasi modifier  
    public function modifiers()  
    {  
        // Decode JSON modifier_id menjadi array  
        $modifierIds = json_decode($this->modifier_id, true);  

        // Jika modifierIds tidak kosong, ambil data Modifier  
        if (!empty($modifierIds)) {  
            $tmpIdModifier = [];
            foreach($modifierIds as $data){
                array_push($tmpIdModifier, $data['id']);
            }
            return Modifiers::whereIn('id', $tmpIdModifier)->get();  
        }  

        // Jika kosong, kembalikan koleksi kosong  
        return collect([]);  
    } 

    public function variant(){
        return $this->belongsTo(VariantProduct::class, 'variant_id', 'id');
    }
}
