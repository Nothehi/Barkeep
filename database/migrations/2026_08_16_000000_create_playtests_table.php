<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Playtesting\Domain\Enums\PlaytestStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('playtests', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * The two halves of "we tested this version of this game", and the
             * module's central invariant.
             *
             * Both are stored even though the version already knows its game,
             * because every read of a playtest is scoped by game and joining
             * through the version to get there would put the tenancy check one
             * table further away than it needs to be. The pair is proved to
             * agree in the application layer on the way in — see
             * `GameCatalogue` — so the redundancy is checked rather than
             * assumed.
             *
             * Cascading on the game: a playtest has no meaning without the
             * project it belongs to. Games are archived rather than deleted in
             * normal use, so a delete that reaches here is a deliberate
             * teardown.
             */
            $table->foreignUuid('game_id')->constrained()->cascadeOnDelete();

            /**
             * Restricted rather than cascading, and this is the historical
             * integrity rule in one line.
             *
             * A playtest is evidence about a specific iteration of a design.
             * Removing that iteration and taking the evidence with it would
             * destroy the only record of what was actually played — so the
             * database refuses to remove a version anything was tested
             * against. Versions are superseded, not deleted; this makes that
             * true rather than merely customary.
             */
            $table->foreignUuid('game_version_id')->constrained('game_versions')->restrictOnDelete();

            $table->string('title');

            /**
             * What the playtest set out to find out, and what it thought the
             * answer would be. The objective is required because a playtest
             * without one is just an evening of gaming; the hypothesis is not,
             * because plenty of useful playtests are exploratory.
             */
            $table->text('objective');
            $table->text('hypothesis')->nullable();

            /**
             * What was learned, written after the fact.
             *
             * The one field a completed playtest stays open to. Conclusions
             * are drawn days later, once somebody has read back through the
             * observations, so freezing it with the rest of the plan would
             * make the loop the module exists to support impossible to close.
             */
            $table->text('conclusion')->nullable();

            $table->string('status')->default(PlaytestStatus::Planned->value);

            /**
             * When the playtest was meant to happen. Sessions carry their own
             * real timestamps; this is the intent, and the difference between
             * the two is worth being able to see.
             */
            $table->timestamp('planned_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            /**
             * Restricted rather than cascading: a playtest belongs to the
             * game, not to whoever happened to plan it, so deleting that
             * account must not take the studio's evidence with it.
             */
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            /**
             * Serves the playtests screen: every list is scoped to a game and
             * ordered by when it was planned, and the one filter it offers is
             * status.
             */
            $table->index(['game_id', 'planned_at']);
            $table->index(['game_id', 'status']);
            $table->index('game_version_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playtests');
    }
};
