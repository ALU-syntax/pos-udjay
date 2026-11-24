<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master bank & e-wallet (umum, tidak khusus B2B). Diisi AccountProviderSeeder; belum ada halaman kelola.
        // Dipakai rekening customer B2B (b2b_customer_accounts.provider = code) dan form pendaftaran di aplikasi membership.
        // Tanpa foreign key dari tabel pemakai (pola sama dengan wilayah); konsistensi dijaga validasi.
        Schema::create('account_providers', function (Blueprint $table) {
            $table->string('code', 30)->primary()->comment('kode tetap, mis. bca, gopay; jangan diubah setelah dipakai');
            $table->enum('type', ['bank', 'ewallet']);
            $table->string('name', 100)->comment('nama tampil');
            $table->unsignedSmallInteger('sort_order')->default(0);
            // Nonaktif = tidak bisa dipilih untuk rekening baru; rekening lama tetap tampil dengan namanya.
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'type', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_providers');
    }
};
