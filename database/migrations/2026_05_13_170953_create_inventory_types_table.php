<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            $table->index('is_active');
        });

        DB::table('inventory_types')->insert([
            ['name' => 'Central Warehouse', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Outlet Warehouse', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kitchen', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bar', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pastry', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'other', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_types');
    }
};
