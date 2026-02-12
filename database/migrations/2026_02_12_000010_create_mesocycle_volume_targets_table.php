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
        Schema::create('mesocycle_volume_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mesocycle_id')->constrained()->onDelete('cascade');
            $table->foreignId('muscle_group_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('mev')->unsigned();
            $table->tinyInteger('mav_min')->unsigned();
            $table->tinyInteger('mav_max')->unsigned();
            $table->tinyInteger('mrv')->unsigned();
            $table->tinyInteger('mv')->unsigned();
            $table->tinyInteger('current_weekly_volume')->nullable();
            $table->timestamps();
            $table->unique(['mesocycle_id', 'muscle_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mesocycle_volume_targets');
    }
};
