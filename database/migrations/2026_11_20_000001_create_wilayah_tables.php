<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Referensi wilayah administrasi Indonesia (Kepmendagri), diisi lewat WilayahSeeder.
     * Primary key = kode Kemendagri bertitik, mis. 32 → 32.01 → 32.01.01 → 32.01.01.1001.
     */
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->string('code', 2)->primary();
            $table->string('name', 100);
        });

        Schema::create('regencies', function (Blueprint $table) {
            $table->string('code', 5)->primary()->comment('kota/kabupaten');
            $table->string('province_code', 2);
            $table->string('name', 100);

            $table->foreign('province_code')->references('code')->on('provinces');
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->string('code', 8)->primary()->comment('kecamatan');
            $table->string('regency_code', 5);
            $table->string('name', 100);

            $table->foreign('regency_code')->references('code')->on('regencies');
        });

        Schema::create('villages', function (Blueprint $table) {
            $table->string('code', 13)->primary()->comment('kelurahan/desa');
            $table->string('district_code', 8);
            $table->string('name', 100);
            $table->string('postal_code', 5)->nullable()->comment('kode pos, dari github.com/cahyadsn/wilayah_kodepos');

            $table->foreign('district_code')->references('code')->on('districts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('villages');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('regencies');
        Schema::dropIfExists('provinces');
    }
};
