<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Which terms from the shared vocabulary a game claims.
     *
     * Attached to the design record rather than to the game, because a game's
     * mechanics are part of what has been decided about its design — the same
     * kind of fact as its player count, recorded and changed in the same place.
     */
    public function up(): void
    {
        Schema::create('design_record_mechanic', function (Blueprint $table) {
            /*
             * No surrogate key, unlike every other table in the module. A row
             * here is the claim itself and has no identity worth addressing — and
             * a uuid column would have to be populated by hand on every attach,
             * which is exactly the kind of thing that works until somebody uses
             * `sync()`.
             */
            $table->foreignUuid('design_record_id')->constrained()->cascadeOnDelete();

            /**
             * Restricted rather than cascading, and this is the half of the
             * relationship worth arguing about.
             *
             * A mechanic is retired, never deleted — `MechanicPolicy::delete()`
             * refuses everybody — precisely so that removing a word cannot
             * rewrite the games that used it. This constraint is the database
             * agreeing: if a delete were ever added, it would fail here rather
             * than silently emptying other studios' records.
             */
            $table->foreignUuid('mechanic_id')->constrained()->restrictOnDelete();

            $table->timestamps();

            /**
             * The identity of a row, and the invariant: a game claims a term or
             * it does not. Claiming it twice is not a stronger claim.
             */
            $table->primary(['design_record_id', 'mechanic_id']);

            $table->index('mechanic_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_record_mechanic');
    }
};
