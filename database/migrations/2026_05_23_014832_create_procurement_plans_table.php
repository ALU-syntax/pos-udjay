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
        Schema::create('procurement_plans', function (Blueprint $table) {
            $table->id();

            $table->string('plan_number', 100);
            $table->unsignedBigInteger('planning_location_id');

            $table->unsignedBigInteger('status_id')->default(1);

            $table->unsignedBigInteger('planned_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();

            $table->dateTime('planned_at')->nullable();
            $table->dateTime('approved_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('plan_number', 'unique_plan_number');

            $table->index('planning_location_id', 'idx_planning_location_id');
            $table->index('status_id', 'idx_procurement_plan_status_id');

            $table->foreign('planning_location_id')
                ->references('id')
                ->on('inventory')
                ->restrictOnDelete();

            $table->foreign('status_id')
                ->references('id')
                ->on('procurement_plan_statuses')
                ->restrictOnDelete();

            $table->foreign('planned_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_plans');
    }
};
