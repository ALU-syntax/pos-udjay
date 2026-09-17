<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel penyimpanan value config untuk kebutuhan mobile
     * (validasi, batas nilai, flag fitur, dan lain sebagainya).
     *
     * - name  : key unik yang dipakai mobile untuk lookup config
     * - value : nilai config, disimpan sebagai string/JSON agar fleksibel
     */
    public function up(): void
    {
        Schema::create('configs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configs');
    }
};
