<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Playtesting\Domain\Enums\PlaytestSessionStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('playtest_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * Cascading: a session is one sitting of a particular
             * investigation and has no meaning apart from it.
             */
            $table->foreignUuid('playtest_id')->constrained()->cascadeOnDelete();

            $table->string('status')->default(PlaytestSessionStatus::Planned->value);

            /**
             * Three timestamps, and they are three different facts.
             *
             * `planned_at` is when somebody intended to run this; the other
             * two are when it actually began and ended. Keeping all three is
             * what lets a designer notice that the session they scheduled for
             * an hour ran for two, which is exactly the sort of thing a
             * playtest is meant to surface.
             *
             * There is deliberately no duration column. It is derived from
             * the pair below wherever it is needed — a stored duration is a
             * fourth fact that can disagree with the two it came from, and
             * the disagreement is only ever noticed later.
             */
            $table->timestamp('planned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->string('location')->nullable();

            /**
             * The session as a whole, and what it settled.
             *
             * Distinct from the observations hanging off it: those are
             * individual things somebody noticed at a moment, these are the
             * write-up. Both are useful and neither substitutes for the other.
             */
            $table->text('notes')->nullable();
            $table->text('outcome')->nullable();

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            /**
             * Sessions are always listed within one playtest, ordered by when
             * they were scheduled.
             */
            $table->index(['playtest_id', 'planned_at']);
            $table->index(['playtest_id', 'status']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playtest_sessions');
    }
};
