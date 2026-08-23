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
        Schema::create('phase_transitions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rule_set_id')->constrained()->cascadeOnDelete();

            /**
             * Both ends of the edge.
             *
             * Cascading on both, unlike the nullable phase references elsewhere:
             * a transition is *about* the pair, and one with a missing end is not
             * a transition with a gap in it, it is nothing.
             *
             * The foreign keys prove the phases exist. They cannot prove both
             * belong to the rule set named in the first column, which is the
             * invariant that matters — that is checked by resolving each phase
             * through the set, in `RuleCatalogue`.
             */
            $table->foreignUuid('from_phase_id')->constrained('game_phases')->cascadeOnDelete();
            $table->foreignUuid('to_phase_id')->constrained('game_phases')->cascadeOnDelete();

            /**
             * What has to be true for play to take this edge, and what makes it
             * happen. Both optional: the commonest transition in a board game is
             * unconditional and automatic — the action phase simply ends and
             * resolution begins.
             */
            $table->foreignUuid('condition_id')->nullable()
                ->constrained('rule_conditions')->nullOnDelete();
            $table->foreignUuid('trigger_id')->nullable()
                ->constrained('rule_triggers')->nullOnDelete();

            /**
             * Which edge is considered first when a phase has several. The order
             * is the designer's: "if somebody has won, go to game end; otherwise
             * back to round start" is two edges whose order is the rule.
             */
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            /**
             * One edge per pair per guard. A rule set may legitimately hold two
             * transitions between the same phases under different conditions, so
             * the condition is part of the key; two identical unguarded edges are
             * a duplicate and are refused.
             */
            $table->unique(['from_phase_id', 'to_phase_id', 'condition_id']);

            $table->index(['rule_set_id', 'position']);
            $table->index('to_phase_id');
            $table->index('condition_id');
            $table->index('trigger_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phase_transitions');
    }
};
