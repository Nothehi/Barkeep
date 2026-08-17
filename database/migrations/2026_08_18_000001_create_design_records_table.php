<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One record per game, holding the design decisions a methodology asks
     * about. Every column here exists because something in the seeded framework
     * already asks for it — "Are the player count and playing time decided?",
     * "Core action identified", "One-sentence pitch written" — and asked for it
     * as prose the platform could not read.
     *
     * A separate table rather than columns on `games`, which is what the Game
     * model's own docblock asks for: identity and lifecycle belong there, and
     * everything a designer records about the design belongs to the capability
     * that owns it. It also means a game that has decided nothing carries no
     * row at all, rather than a dozen nulls.
     */
    public function up(): void
    {
        Schema::create('design_records', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * One record per game, enforced rather than assumed. Two records
             * would mean two answers to "how many players is this for?", and
             * nothing downstream could choose between them.
             *
             * Cascading: the record describes the game and has no meaning
             * without it.
             */
            $table->foreignUuid('game_id')->unique()->constrained()->cascadeOnDelete();

            /**
             * The one-sentence pitch. Short on purpose — the framework's own
             * criterion is "if it takes a paragraph, the idea is still several
             * ideas", and a `text` column would quietly invite the paragraph.
             */
            $table->string('pitch', 300)->nullable();

            /**
             * The constraints. Nullable because a game in ideation has decided
             * none of them yet, and a default would be the platform deciding on
             * the designer's behalf.
             *
             * Ranges are stored as two columns rather than one, so that either
             * end can be queried — "which of our games are for two players?" is
             * a question somebody will ask.
             */
            $table->unsignedSmallInteger('player_count_min')->nullable();
            $table->unsignedSmallInteger('player_count_max')->nullable();

            /**
             * Minutes, always. Hours need fractions at the short end, and a
             * mixed unit is a conversion somebody eventually gets wrong.
             */
            $table->unsignedSmallInteger('play_time_min')->nullable();
            $table->unsignedSmallInteger('play_time_max')->nullable();

            $table->unsignedSmallInteger('target_age_min')->nullable();

            $table->string('complexity')->nullable();

            /**
             * Who this is for, in the designer's own words. Deliberately prose
             * rather than a taxonomy: "families with children who can already
             * read" is a useful answer that no enum would have offered.
             */
            $table->string('audience', 300)->nullable();

            /**
             * The core loop, in the five parts the framework asks for. Text
             * rather than string, because these are the answers a designer works
             * on hardest and a length limit on them would be the tool arguing
             * with the process.
             */
            $table->text('core_action')->nullable();
            $table->text('core_cost')->nullable();
            $table->text('core_reward')->nullable();
            $table->text('win_condition')->nullable();
            $table->text('failure_condition')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_records');
    }
};
