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
        Schema::create('workout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('microcycle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('workout_template_id')->nullable()->constrained()->nullOnDelete();
            $table->date('scheduled_date');
            $table->timestamp('completed_at')->nullable();
            $table->smallInteger('duration_minutes')->unsigned()->nullable();
            $table->tinyInteger('overall_rpe')->unsigned()->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'skipped'])->default('scheduled');
            $table->timestamps();
            $table->index(['user_id', 'scheduled_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_sessions');
    }
};
