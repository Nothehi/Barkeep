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
        /*
         * Feedback and observations look alike and are not the same thing.
         *
         * An observation is what a designer noticed. Feedback is what a
         * participant said. They are stored apart because merging them would
         * lose the distinction that makes either one worth reading: "the
         * scoring confused them" and "I didn't understand the scoring" carry
         * different weight, and only one of them is a player's own words.
         */
        Schema::create('playtest_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('session_id')->constrained('playtest_sessions')->cascadeOnDelete();

            /**
             * Who said it, when they were willing to be named.
             *
             * Nullable because anonymous feedback is often the honest kind —
             * somebody who did not enjoy a friend's game says so more readily
             * when their name is not on it.
             *
             * Null on delete for the same reason as observations: removing a
             * participant drops the attribution, not the feedback.
             */
            $table->foreignUuid('participant_id')->nullable()
                ->constrained('playtest_participants')
                ->nullOnDelete();

            $table->text('content');

            /**
             * An optional score on a fixed one-to-five scale.
             *
             * Fixed rather than configurable because ratings that mean
             * different things in different playtests cannot be averaged, and
             * averaging them is the only thing the number is for. The range is
             * enforced by the value object and by validation; the column stays
             * a plain small integer so the constraint has one home rather than
             * two that can drift.
             */
            $table->unsignedTinyInteger('rating')->nullable();

            /**
             * Who recorded it, which is usually not who said it.
             *
             * Feedback is typed in by whoever is running the session, on
             * behalf of a participant who may not have an account. Keeping
             * this separate from `participant_id` is what stops "the
             * facilitator wrote this down" from being read as "the facilitator
             * said this".
             */
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            $table->index(['session_id', 'created_at']);
            $table->index('participant_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playtest_feedback');
    }
};
