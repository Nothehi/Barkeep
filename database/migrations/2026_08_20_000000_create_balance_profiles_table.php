<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\GameEconomy\Domain\Enums\BalanceProfileStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('balance_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * A balance configuration belongs to a design state, never to the
             * game as a whole, and that is the module's foundational decision.
             *
             * Wood income was 2 in v1 and 3 in v2. If the numbers hung off the
             * game, the second answer would silently overwrite the first and
             * every playtest run against v1 would become uninterpretable —
             * "players ran out of wood" means nothing without knowing what wood
             * cost at the time.
             *
             * Restricted rather than cascading for the same reason every version
             * reference in the platform is restricted: removing the design state
             * a profile configures would leave the profile describing a game
             * nobody can look up.
             */
            $table->foreignUuid('game_version_id')->constrained('game_versions')->restrictOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->string('status')->default(BalanceProfileStatus::Draft->value);

            /**
             * Restricted rather than cascading: a profile belongs to the game,
             * not to whoever happened to tune it, so deleting that account must
             * not take the studio's economy with it.
             */
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            /**
             * A design state may carry several profiles — a draft being tuned
             * beside the one in play, and the archived ones that came before —
             * so the name is what tells them apart.
             */
            $table->unique(['game_version_id', 'name']);

            $table->index(['game_version_id', 'status']);
            $table->index('created_by');
        });

        /**
         * "Only one active profile per game version", enforced by the database
         * rather than by a command holding a lock.
         *
         * A partial unique index is the right tool: it constrains exactly the
         * rows that matter and leaves any number of drafts and archives alone.
         * Both PostgreSQL and SQLite accept this form.
         */
        DB::statement(sprintf(
            "CREATE UNIQUE INDEX balance_profiles_one_active_per_version ON balance_profiles (game_version_id) WHERE status = '%s'",
            BalanceProfileStatus::Active->value,
        ));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_profiles');
    }
};
