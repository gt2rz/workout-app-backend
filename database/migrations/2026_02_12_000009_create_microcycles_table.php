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
        Schema::create('microcycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mesocycle_id')->constrained()->onDelete('cascade');
            $table->smallInteger('week_number')->unsigned();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_deload')->default(false);
            $table->tinyInteger('target_volume_percentage')->unsigned()->default(100);
            $table->double('actual_volume_completed')->nullable();
            $table->enum('status', ['planned', 'active', 'completed'])->default('planned');
            $table->timestamps();
            $table->unique(['mesocycle_id', 'week_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('microcycles');
    }
};
