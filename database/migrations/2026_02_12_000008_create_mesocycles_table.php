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
        Schema::create('mesocycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('macrocycle_id')->constrained()->onDelete('cascade');
            $table->foreignId('mesocycle_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('split_type_id')->constrained()->onDelete('cascade');
            $table->smallInteger('order')->unsigned();
            $table->smallInteger('start_week')->unsigned();
            $table->smallInteger('duration_weeks')->unsigned();
            $table->json('deload_weeks')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['macrocycle_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mesocycles');
    }
};
