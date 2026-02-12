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
        Schema::create('macrocycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('goal')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->smallInteger('duration_weeks')->unsigned();
            $table->boolean('is_active')->default(false);
            $table->enum('status', ['planned', 'active', 'completed', 'paused'])->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'is_active']);
            $table->index('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('macrocycles');
    }
};
