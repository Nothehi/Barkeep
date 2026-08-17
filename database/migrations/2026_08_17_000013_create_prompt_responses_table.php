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
        Schema::create('prompt_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('game_framework_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('prompt_id')->constrained('design_prompts')->cascadeOnDelete();

            /**
             * What the designer wrote.
             *
             * The most valuable column in the module. "What is the most
             * interesting decision in your game?" is a question a designer can
             * only answer by thinking, and the answer is the thing they will
             * want to reread when the design has drifted.
             */
            $table->text('response');

            $table->foreignUuid('answered_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('answered_at');

            $table->timestamps();

            /**
             * One standing answer per prompt per game. Answering again
             * overwrites, and `updated_at` records that it moved — a prompt is
             * asking what the design is now, not keeping a diary of what it
             * used to be.
             */
            $table->unique(['game_framework_id', 'prompt_id']);

            $table->index('answered_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prompt_responses');
    }
};
