<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\DesignFramework\Domain\Enums\FrameworkContentStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('design_phases', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * A phase belongs to a *version*, not to a framework.
             *
             * That is the whole of historical integrity in one column. v2 may
             * split "Prototyping" into two phases, rename a third and drop a
             * fourth; a game that adopted v1 keeps reading v1's phases,
             * because they are different rows.
             */
            $table->foreignUuid('framework_version_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            /**
             * The phase's place in the arc, and the only thing that orders it.
             *
             * Never `id` and never `created_at`: a designer inserting
             * "Concept" between "Ideation" and "Core loop" a week later would
             * otherwise get it at the end. Positions are 1-based and kept
             * contiguous by `ContentSequencer`.
             *
             * Deliberately not unique with the version. Reordering rewrites a
             * run of rows inside one transaction, and a unique index would
             * refuse the intermediate states unless it were deferrable —
             * which SQLite, the test database, does not support. The sequencer
             * is what keeps positions sane; the index below is only for
             * reading them back in order.
             */
            $table->integer('position');

            $table->string('status')->default(FrameworkContentStatus::Draft->value);

            $table->timestamps();

            /**
             * Phase addresses are unique inside a version, which is what lets
             * `/frameworks/bgdf/versions/1/phases/core-loop` be a URL.
             */
            $table->unique(['framework_version_id', 'slug']);

            $table->index(['framework_version_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_phases');
    }
};
