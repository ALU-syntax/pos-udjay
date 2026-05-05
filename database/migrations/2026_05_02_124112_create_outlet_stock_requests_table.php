<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         Schema::create('outlet_stock_requests', function (Blueprint $table) {
            $table->id();

            $table->string('request_number', 100)->unique();
            // contoh: OSR-202605-0001

            $table->foreignId('outlet_id')
                ->constrained('outlets')
                ->cascadeOnDelete();

            /*
             * User outlet manager yang membuat request.
             * Saya tidak pakai constrained agar tetap fleksibel
             * jika nama tabel user di project berbeda.
             */
            $table->unsignedBigInteger('requested_by')->nullable();

            $table->date('request_date');
            $table->date('needed_date')->nullable();

            /*
             * draft     = masih disimpan
             * submitted = dikirim ke gudang pusat
             * approved  = disetujui gudang
             * rejected  = ditolak
             * fulfilled = sudah dipenuhi
             * cancelled = dibatalkan
             */
            $table->string('status', 50)->default('draft');

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('outlet_id');
            $table->index('requested_by');
            $table->index('request_date');
            $table->index('needed_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlet_stock_requests');
    }
};
