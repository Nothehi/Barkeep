<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\GameRules\Domain\Enums\RuleSetStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rule_sets', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * A rule system belongs to a design state, never to the game as a
             * whole, and that is the module's foundational decision.
             *
             * Combat was resolved with one die in v1 and two in v2. If the rules
             * hung off the game, the second answer would silently overwrite the
             * first and every playtest run against v1 would become
             * uninterpretable — "combat dragged" means nothing without knowing
             * how combat worked at the time.
             *
             * Restricted rather than cascading, like every version reference in
             * the platform: removing the design state a rule set describes would
             * leave the rules describing a game nobody can look up.
             */
            $table->foreignUuid('game_version_id')->constrained('game_versions')->restrictOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->string('status')->default(RuleSetStatus::Draft->value);

            /**
             * Where this set came from, when it was cloned rather than written.
             *
             * The lineage is what makes "clone, change, activate" legible a year
             * later: v2 of the rules is visibly a descendant of v1 rather than an
             * unrelated document that happens to look similar. Nulled rather than
             * cascaded on delete, because an ancestor going away does not make
             * the descendant untrue.
             */
            $table->foreignUuid('cloned_from_rule_set_id')->nullable()
                ->constrained('rule_sets')->nullOnDelete();

            /**
             * Restricted rather than cascading: a rule set belongs to the game,
             * not to whoever happened to write it down.
             */
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            /**
             * A design state may carry several rule sets — a draft being written
             * beside the one in play, and the archived ones that came before — so
             * the name is what tells them apart.
             */
            $table->unique(['game_version_id', 'name']);

            $table->index(['game_version_id', 'status']);
            $table->index('created_by');
        });

        /**
         * "Only one active rule set per game version", enforced by the database
         * rather than by a command holding a lock.
         *
         * A partial unique index constrains exactly the rows that matter and
         * leaves any number of drafts and archives alone. Both PostgreSQL and
         * SQLite accept this form. `ActivateRuleSet` still takes a row lock, so
         * two simultaneous activations queue rather than one of them surfacing
         * as a constraint violation.
         */
        DB::statement(sprintf(
            "CREATE UNIQUE INDEX rule_sets_one_active_per_version ON rule_sets (game_version_id) WHERE status = '%s'",
            RuleSetStatus::Active->value,
        ));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rule_sets');
    }
};
