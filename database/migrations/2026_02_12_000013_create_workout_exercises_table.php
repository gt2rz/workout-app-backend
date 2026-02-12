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
        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained()->onDelete('cascade');
            $table->smallInteger('order')->unsigned();
            $table->smallInteger('target_sets')->unsigned();
            $table->smallInteger('target_reps_min')->unsigned();
            $table->smallInteger('target_reps_max')->unsigned();
            $table->tinyInteger('target_rpe')->unsigned()->nullable();
            $table->smallInteger('rest_seconds')->unsigned()->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['workout_session_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_exercises');
    }
};
