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
        Schema::create('iteration_playtests', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('iteration_id')->constrained()->cascadeOnDelete();

            /**
             * The link to Playtesting, and everything this table is careful not
             * to be.
             *
             * A join row and nothing else. There is no copy of the playtest's
             * title, no cached session count, no denormalised observation
             * total — because Playtesting owns the evidence and a second copy
             * of it here would be a second answer waiting to disagree with the
             * first. Everything shown about an attached playtest is read back
             * through Playtesting's own contract at render time.
             *
             * A real foreign key rather than a loose id, unlike
             * `decision_evidence.reference_id`. The difference is worth
             * stating: this row's *only* content is the association, so an
             * association pointing at a playtest that no longer exists is not
             * a degraded record, it is a lie. Restricted on delete for the
             * same reason — a playtest an iteration was judged on cannot be
             * removed out from under it.
             */
            $table->foreignUuid('playtest_id')->constrained('playtests')->restrictOnDelete();

            /**
             * Who made the connection. An iteration's playtests are often
             * attached by somebody other than whoever planned either, and
             * knowing who linked the evidence to the cycle is part of reading
             * the history back.
             */
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            /**
             * A playtest is attached to an iteration or it is not; attaching it
             * twice says nothing the first attachment did not. Enforced here
             * rather than by the command checking first, so a double-submitted
             * form cannot produce two rows.
             */
            $table->unique(['iteration_id', 'playtest_id']);

            $table->index('playtest_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iteration_playtests');
    }
};
