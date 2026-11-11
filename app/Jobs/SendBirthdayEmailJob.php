<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\BirthdayMail;
use Throwable;
use Illuminate\Support\Facades\Log;

class SendBirthdayEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;

    /**
     * Create a new job instance.
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->user['email'])
            ->send(new BirthdayMail($this->user));

        Log::channel('birthday')->info('Email ulang tahun terkirim', [
            'customer_id' => $this->user['id'] ?? null,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('birthday')->error('Email ulang tahun gagal', [
            'customer_id' => $this->user['id'] ?? null,
            'error' => $exception?->getMessage(),
        ]);
    }

}
