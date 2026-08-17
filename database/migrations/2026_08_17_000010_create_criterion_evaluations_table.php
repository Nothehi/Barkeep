<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\DesignFramework\Domain\Enums\CriterionRating;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('criterion_evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * The evaluation belongs to the game's *adoption*, not to the
             * criterion and not to the game directly.
             *
             * That is the module's central separation. The criterion is a
             * question every game following v1 is asked; this row is one
             * game's answer to it. Hanging it off the adoption rather than the
             * game means the answer is automatically scoped to the version
             * that asked the question.
             */
            $table->foreignUuid('game_framework_id')->constrained()->cascadeOnDelete();

            $table->foreignUuid('criterion_id')->constrained('design_criteria')->cascadeOnDelete();

            /**
             * Not evaluated, weak, needs work, good or strong.
             *
             * A deliberately coarse scale. The purpose is structured
             * self-assessment, and a designer who has to choose between seven
             * grades spends their attention on the grade instead of on the
             * design.
             */
            $table->string('status')->default(CriterionRating::NotEvaluated->value);

            /**
             * Why they gave it that grade. Optional, and the most valuable
             * column in the table when it is filled in — a bare "needs work"
             * six months later tells nobody what needed work.
             */
            $table->text('notes')->nullable();

            $table->foreignUuid('evaluated_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('evaluated_at');

            $table->timestamps();

            /**
             * One standing answer per question per game. Re-evaluating
             * overwrites, because the criterion asks how the design is *now*;
             * a history of grades over time is a different feature with its
             * own table.
             */
            $table->unique(['game_framework_id', 'criterion_id']);

            $table->index(['game_framework_id', 'status']);
            $table->index('evaluated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('criterion_evaluations');
    }
};
