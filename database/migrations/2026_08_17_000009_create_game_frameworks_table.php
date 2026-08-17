<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\DesignFramework\Domain\Enums\GameFrameworkStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_frameworks', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * The join between a design project and a methodology, and the
             * reason DesignFramework does not own the game.
             *
             * Unique, so a game follows exactly one framework version at a
             * time. Supporting several at once is a real product question —
             * whose progress is *the* progress? — and answering it later is
             * cheaper than guessing now.
             *
             * Cascading on the game: the adoption, the evaluations and the
             * completions are all facts about a project, and none of them
             * survives it.
             */
            $table->foreignUuid('game_id')->unique()->constrained()->cascadeOnDelete();

            /**
             * Restricted rather than cascading, and this is historical
             * integrity enforced by the database.
             *
             * A game that adopted v1 stays on v1 forever unless its designer
             * explicitly migrates. Deleting a version that games are following
             * would silently detach their evaluations from the questions they
             * answered, so the database refuses. Versions are superseded, not
             * removed.
             */
            $table->foreignUuid('framework_version_id')->constrained()->restrictOnDelete();

            $table->string('status')->default(GameFrameworkStatus::Active->value);

            /**
             * When the game took the methodology up, and when it declared
             * itself done with it. Both are facts about this project rather
             * than about the framework.
             */
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();

            /**
             * Who adopted it. Not part of any rule — every member of the
             * workspace may work the framework — but "who signed us up to
             * this?" is the first question asked about a process nobody
             * remembers choosing.
             */
            $table->foreignUuid('adopted_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            /**
             * Answers "which games are on v1?", which is what a framework
             * author needs before publishing v2.
             */
            $table->index(['framework_version_id', 'status']);
            $table->index('adopted_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_frameworks');
    }
};
