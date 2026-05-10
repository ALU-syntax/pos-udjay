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
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->dropForeign(['storage_type_id']);
            $table->foreign('storage_type_id')
                ->references('id')
                ->on('raw_storage_types')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->dropForeign(['storage_type_id']);
            $table->foreign('storage_type_id')
                ->references('id')
                ->on('satuans')
                ->restrictOnDelete();
        });
    }
};
