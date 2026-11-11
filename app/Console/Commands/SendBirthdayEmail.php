<?php

namespace App\Console\Commands;

use App\Jobs\SendBirthdayEmailJob;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendBirthdayEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:birthday';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send birthday emails';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        $users = Customer::whereMonth('tanggal_lahir', $today->month)
            ->whereDay('tanggal_lahir', $today->day)
            // hanya member yang sudah terdaftar minimal 30 hari
            ->whereDate('created_at', '<=', $today->copy()->subDays(30))
            ->where(function ($q) {
                $q->whereNull('birthday_email_sent_at')
                ->orWhereYear('birthday_email_sent_at', '!=', now()->year);
            }) // biar ga double
            ->limit(40)
            ->get();


        foreach ($users as $user) {
            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ];
            SendBirthdayEmailJob::dispatch($data);

            $user->update([
                'birthday_email_sent_at' => now()
            ]);
        }

        Log::channel('birthday')->info('email:birthday dijalankan', [
            'jumlah_member' => $users->count(),
        ]);

        $this->info("Processed " . $users->count() . " users");
    }
}
