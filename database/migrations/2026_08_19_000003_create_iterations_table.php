<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\PrototypeIteration\Domain\Enums\IterationStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('iterations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * Three references, and getting all three to agree is this
             * module's central invariant.
             *
             * An iteration is one turn of the design loop, and it only means
             * something if the design state, the built thing and the project
             * are the same project. The game is stored directly — as on
             * `prototypes` and `playtests` — so every read is scoped by it
             * without a join.
             */
            $table->foreignUuid('game_id')->constrained()->cascadeOnDelete();

            /**
             * The design as it stood when this cycle ran.
             *
             * Restricted, for the reason every version reference in the
             * platform is restricted: an iteration that cannot say what the
             * design looked like when it happened is not a historical record.
             */
            $table->foreignUuid('game_version_id')->constrained('game_versions')->restrictOnDelete();

            /**
             * The prototype state that was on the table.
             *
             * Restricted rather than cascading, and it is worth being explicit
             * about why this differs from `prototype_versions` cascading from
             * its prototype. A prototype version's *existence* depends on its
             * prototype; an iteration's existence does not depend on anything
             * — it is the record of work somebody did, and it outlives every
             * artifact of that work. So the database refuses to remove a
             * prototype version that a cycle was run against.
             *
             * The version's own prototype is proved to belong to `game_id` in
             * the application layer before anything is written. That check
             * cannot be expressed as a foreign key, which is exactly why it
             * has a named rule, a command that enforces it and a test that
             * proves it.
             */
            $table->foreignUuid('prototype_version_id')
                ->constrained('prototype_versions')
                ->restrictOnDelete();

            $table->string('title');

            /**
             * What the cycle set out to change, and what the designer expected
             * would happen.
             *
             * The objective is required because an iteration without one is
             * just a period of time; the hypothesis is not, because plenty of
             * real cycles are exploratory. Writing the hypothesis down *first*
             * is what makes the outcome mean anything afterwards, which is why
             * it is offered on the create form rather than only on completion.
             */
            $table->text('objective');
            $table->text('hypothesis')->nullable();

            $table->string('status')->default(IterationStatus::Planned->value);

            /**
             * How the cycle turned out, in one word and then in prose.
             *
             * Both nullable because an iteration that has not completed has no
             * outcome — which is different from an outcome of "inconclusive",
             * and the difference is the whole reason these are not defaulted.
             * `CompleteIteration` requires both, so a completed iteration
             * never has either as null.
             */
            $table->string('outcome')->nullable();
            $table->text('summary')->nullable();

            /**
             * When the work actually began and ended, as opposed to when the
             * row was written. Both are set by the lifecycle commands rather
             * than being supplied, so they record what happened.
             */
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            /**
             * Serves the iterations screen, which reads a game's cycles newest
             * first, and the two things that screen filters by.
             */
            $table->index(['game_id', 'created_at']);
            $table->index(['game_id', 'status']);
            $table->index(['game_id', 'outcome']);
            $table->index('game_version_id');
            $table->index('prototype_version_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iterations');
    }
};
