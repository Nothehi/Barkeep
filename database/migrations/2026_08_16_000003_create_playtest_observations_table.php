<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Playtesting\Domain\Enums\ObservationCategory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('playtest_observations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('session_id')->constrained('playtest_sessions')->cascadeOnDelete();

            /**
             * Who the observation was about, when it was about one person.
             *
             * "All players ignored the market" is about nobody in particular
             * and leaves this null; "player two never read the reference card"
             * is about somebody, and being able to say so is what turns a pile
             * of notes into an account of what happened at the table.
             *
             * Null on delete rather than cascade: taking somebody out of the
             * participant list must not delete what was noticed about them.
             * The observation survives with its attribution dropped, which is
             * a smaller loss than the observation disappearing.
             */
            $table->foreignUuid('participant_id')->nullable()
                ->constrained('playtest_participants')
                ->nullOnDelete();

            $table->string('category')->default(ObservationCategory::Other->value);
            $table->text('content');

            /**
             * When it happened, as opposed to when it was written down.
             *
             * Nullable on purpose. Half of all observations are typed up after
             * the session from memory, and demanding a moment for those would
             * produce invented timestamps — which is worse than no timestamp,
             * because a session timeline would then order things by a fiction.
             */
            $table->timestamp('observed_at')->nullable();

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            /**
             * Serves the session timeline, which reads a single session's
             * observations in the order they were noticed, and the category
             * filter beside it.
             */
            $table->index(['session_id', 'observed_at']);
            $table->index(['session_id', 'category']);
            $table->index('participant_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playtest_observations');
    }
};
