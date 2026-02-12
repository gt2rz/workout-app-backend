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
        Schema::create('workout_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_exercise_id')->constrained()->onDelete('cascade');
            $table->smallInteger('set_number')->unsigned();
            $table->decimal('weight', 8, 2)->nullable();
            $table->smallInteger('reps')->unsigned()->nullable();
            $table->tinyInteger('rpe')->unsigned()->nullable();
            $table->tinyInteger('rir')->unsigned()->nullable();
            $table->boolean('is_warmup')->default(false);
            $table->boolean('is_dropset')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['workout_exercise_id', 'set_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_sets');
    }
};
