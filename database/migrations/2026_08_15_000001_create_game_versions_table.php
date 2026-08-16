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
        Schema::create('game_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * Cascading: a version has no meaning without the game it is an
             * iteration of, so it cannot outlive one.
             */
            $table->foreignUuid('game_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('version_number');

            $table->string('name')->nullable();
            $table->text('description')->nullable();

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            /**
             * The invariant that makes version numbers trustworthy, and the
             * one that decides a race.
             *
             * `CreateGameVersion` reads the game's highest number and adds
             * one, under a row lock on the game. Where that lock is real the
             * race never starts; where it is not — SQLite in the test suite,
             * a future read replica — two callers can compute the same next
             * number, and exactly one of them gets past this constraint. The
             * loser retries and takes the number after.
             *
             * Without this, concurrent version creation silently produces two
             * v4s, which is the kind of bug that is only noticed months later
             * when somebody asks which v4 they played.
             */
            $table->unique(['game_id', 'version_number']);

            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_versions');
    }
};
