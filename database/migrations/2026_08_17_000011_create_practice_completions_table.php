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
        Schema::create('practice_completions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * A practice belongs to the framework; its completion belongs to
             * the game's adoption of that framework. Keeping the two apart is
             * what lets "run a two-player playtest" be advice to everybody and
             * a finished task for one project at the same time.
             */
            $table->foreignUuid('game_framework_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('practice_id')->constrained('design_practices')->cascadeOnDelete();

            $table->foreignUuid('completed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('completed_at');

            /**
             * What they did and what came of it. This is where "we ran it with
             * four people and the market never emptied" gets written down.
             */
            $table->text('notes')->nullable();

            $table->timestamps();

            /**
             * The row's existence *is* the completion, so one per practice per
             * game. Un-ticking a practice deletes the row rather than setting a
             * flag — there is no third state between done and not done, and a
             * `completed` boolean on a completion record would invite one.
             */
            $table->unique(['game_framework_id', 'practice_id']);

            $table->index('completed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_completions');
    }
};
