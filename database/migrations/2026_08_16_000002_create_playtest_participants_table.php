<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Playtesting\Domain\Enums\PlaytestParticipantRole;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('playtest_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('session_id')->constrained('playtest_sessions')->cascadeOnDelete();

            /**
             * Nullable, and that is the point of the table.
             *
             * Most people at a playtest do not have a Barkeep account: they
             * are a friend, somebody from the local game group, a stranger at
             * a convention. Creating shadow Identity accounts for them would
             * put real people into the platform's user table without their
             * knowledge, to solve a problem a nullable column solves.
             *
             * Restricted rather than cascading: deleting an account must not
             * silently remove them from the sessions they played in, because
             * the participant count is part of what a session means.
             */
            $table->foreignUuid('user_id')->nullable()->constrained('users')->restrictOnDelete();

            /**
             * How this person is referred to in this session, always present.
             *
             * Recorded even for participants who have an account, so a session
             * reads back the way the room worked — somebody who introduced
             * themselves as "Sam" stays Sam here regardless of what their
             * profile says now.
             *
             * Nothing else about a person is stored. No email, no phone, no
             * address: Playtesting has no use for them, and holding personal
             * data about people who never signed up to anything would be a
             * liability taken on for no benefit.
             */
            $table->string('display_name');

            $table->string('role')->default(PlaytestParticipantRole::Player->value);

            /**
             * When they arrived and when they left. Both optional, because a
             * participant added while planning the session has not arrived
             * yet, and most people who stay to the end are never marked as
             * leaving.
             */
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();

            $table->timestamps();

            /**
             * One seat per account per session.
             *
             * This is also the tie-breaker when two facilitators add the same
             * person at the same moment: one insert wins and the other is
             * refused, rather than both succeeding and the session reporting
             * five players where four sat down.
             *
             * The constraint deliberately does not reach guests. In both
             * PostgreSQL and SQLite a NULL `user_id` never collides, so any
             * number of anonymous participants coexist — which is correct.
             * Two guests introduced as "Sam" may genuinely be two people, and
             * the platform has nothing to tell them apart with; refusing the
             * second would lose a real participant to protect against a
             * duplicate that may not exist.
             */
            $table->unique(['session_id', 'user_id']);

            $table->index(['session_id', 'role']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playtest_participants');
    }
};
