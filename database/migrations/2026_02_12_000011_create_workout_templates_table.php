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
        Schema::create('workout_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('split_type_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->tinyInteger('day_of_week')->unsigned();
            $table->text('description')->nullable();
            $table->boolean('is_base_template')->default(true);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->tinyInteger('estimated_duration_minutes')->unsigned()->nullable();
            $table->timestamps();
            $table->index(['split_type_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_templates');
    }
};
