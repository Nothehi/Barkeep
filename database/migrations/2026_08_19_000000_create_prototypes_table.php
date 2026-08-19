<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\PrototypeIteration\Domain\Enums\PrototypeStatus;
use Modules\PrototypeIteration\Domain\Enums\PrototypeType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prototypes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * A prototype is an *implementation* of a design, and these two
             * columns are what tie it to the design it implements.
             *
             * Both are stored even though the version already knows its game,
             * for the same reason the playtests table stores both: every read
             * of a prototype is scoped by game, and joining through the
             * version to get there would put the tenancy check one table
             * further away than it needs to be. The pair is proved to agree in
             * the application layer on the way in, so the redundancy is
             * checked rather than assumed.
             */
            $table->foreignUuid('game_id')->constrained()->cascadeOnDelete();

            /**
             * The design state this prototype was built to implement.
             *
             * Restricted rather than cascading, which is the historical
             * integrity rule in one line: removing the game version a
             * prototype was built from would leave every iteration run against
             * it describing a design nobody can look up. Versions are
             * superseded, not deleted.
             *
             * Note that this is the version the prototype *started* from, not
             * the version it currently reflects. A prototype outlives the
             * design state it was cut against — that is the whole point of
             * iterating on it — and the iterations carry their own version
             * reference for the design as it stood when each cycle ran.
             */
            $table->foreignUuid('game_version_id')->constrained('game_versions')->restrictOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            /**
             * What the prototype physically is, and where it is in its life.
             *
             * The kind matters because it decides what an iteration costs:
             * reprinting a card sheet is an afternoon, rebuilding a digital
             * simulation is a week. Both default at the column as well as on
             * the model, so a row written by a seeder or a fixture is as valid
             * as one written by a command.
             */
            $table->string('type')->default(PrototypeType::Paper->value);
            $table->string('status')->default(PrototypeStatus::Draft->value);

            /**
             * Restricted rather than cascading: a prototype belongs to the
             * game, not to whoever happened to build it, so deleting that
             * account must not take the studio's work with it.
             */
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            /**
             * Serves the prototypes screen: every list is scoped to a game and
             * ordered newest first, and the filters it offers are status and
             * kind.
             */
            $table->index(['game_id', 'created_at']);
            $table->index(['game_id', 'status']);
            $table->index(['game_id', 'type']);
            $table->index('game_version_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prototypes');
    }
};
