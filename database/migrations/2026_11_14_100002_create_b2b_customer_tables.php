<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customer B2B sengaja terpisah dari tabel `customers` (B2C, ikut sync kasir Android & email ulang tahun).
        Schema::create('b2b_customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->enum('type', ['individual', 'pt', 'cv'])->comment('individual = perorangan');
            $table->string('name');
            $table->string('npwp', 30)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            // Satu kontak (PIC) dan satu alamat per customer; telepon & email di atas juga jadi tujuan reminder.
            $table->string('contact_name')->nullable()->comment('nama PIC');
            $table->text('address')->nullable()->comment('alamat penagihan sekaligus pengiriman');
            // Wilayah memakai kode Kemendagri (tabel provinces/regencies/districts/villages), dipilih lewat dropdown berjenjang.
            // Tanpa foreign key karena tabel wilayah dibuat oleh migration yang lebih baru; konsistensi dijaga CustomerRequest.
            $table->string('province_code', 2)->nullable();
            $table->string('regency_code', 5)->nullable()->comment('kota/kabupaten');
            $table->string('district_code', 8)->nullable()->comment('kecamatan');
            $table->string('village_code', 13)->nullable()->comment('kelurahan/desa');
            $table->string('postal_code', 10)->nullable();
            // Reminder diatur per customer. Tidak diatur = tidak ada yang dikirim (tidak ada pengaturan global).
            $table->boolean('reminder_enabled')->default(false);
            $table->json('reminder_channels')->nullable()->comment('["email"], ["whatsapp"], atau keduanya');
            $table->json('reminder_days')->nullable()->comment('hari relatif ke jatuh tempo, mis. [-3, -1, 0]');
            $table->boolean('reminder_overdue_daily')->default(false)->comment('kirim tiap hari setelah lewat jatuh tempo');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            // Audit trail: id user disimpan apa adanya, tanpa foreign key/index (user tidak dihapus permanen).
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('type');
        });

        // Rekening bank / e-wallet yang biasa dipakai customer untuk transfer; dasar pencocokan mutasi (fase 3).
        Schema::create('b2b_customer_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2b_customer_id')->constrained('b2b_customers')->cascadeOnDelete();
            $table->enum('type', ['bank', 'ewallet'])->comment('mengikuti provider (CustomerAccount::PROVIDERS)');
            $table->string('provider', 30)->comment('kode bank/e-wallet dari daftar baku, mis. bca, gopay');
            $table->string('account_number', 30)->comment('angka saja; e-wallet = nomor HP 08…');
            $table->string('account_name')->comment('nama pemilik rekening, biasanya yang muncul di mutasi');
            $table->string('notes')->nullable();
            $table->timestamps();

            // Satu rekening hanya milik satu customer, supaya pencocokan mutasi tidak ambigu.
            // Rekening customer yang dihapus ikut dihapus (CustomerService::delete) agar bisa didaftarkan ulang.
            $table->unique(['provider', 'account_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_customer_accounts');
        Schema::dropIfExists('b2b_customers');
    }
};
