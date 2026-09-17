<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom type pada tabel configs.
     *
     * type dipakai sebagai metadata/petunjuk casting untuk mobile, sehingga
     * API bisa mengembalikan value dengan tipe yang benar (integer, boolean,
     * json, dan sebagainya) dan client tidak perlu menebak sendiri.
     *
     * Nilai yang didukung: string, integer, float, boolean, json.
     * Default 'string' agar data lama tetap valid.
     */
    public function up(): void
    {
        Schema::table('configs', function (Blueprint $table) {
            $table->string('type')->default('string')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('configs', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
