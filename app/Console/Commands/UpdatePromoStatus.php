<?php

namespace App\Console\Commands;

use App\Models\Promo;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdatePromoStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promo:update-status';

    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update promo status based on date, time, and allowed days';

    /**
     * Execute the console command.
     */
    public function handle()  
    {  
        // Mendapatkan tanggal dan waktu saat ini  
        $currentDate = Carbon::now();  
        $currentTime = $currentDate->format('H:i:s');  
        $currentDay = $currentDate->translatedFormat('l'); // Mengambil nama hari dalam bahasa Indonesia  
  
        // Mengambil promo yang tidak dihapus  
        $promos = Promo::whereNull('deleted_at')->get();  
  
        foreach ($promos as $promo) {  
            // Memeriksa kondisi  
            $isDateInRange = $currentDate->between($promo->promo_date_periode_start, $promo->promo_date_periode_end);  
            $isTimeInRange = $currentTime >= $promo->promo_time_periode_start && $currentTime <= $promo->promo_time_periode_end;  
            $isDayAllowed = in_array($currentDay, json_decode($promo->day_allowed));  
  
            // Jika semua kondisi terpenuhi, ubah status  
            if ($isDateInRange && $isTimeInRange && $isDayAllowed) {  
                if($promo->status != 1){
                    $promo->status = 1; // Atur status menjadi aktif  
                    $promo->save();  
                }
            } else {  
                if($promo->status != 0){
                    $promo->status = 0; // Atur status menjadi tidak aktif  
                    $promo->save();  
                }
            }  
        }  
  
        $this->info('Promo status updated successfully.');  
    }  
}
