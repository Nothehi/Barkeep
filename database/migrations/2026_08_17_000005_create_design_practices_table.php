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
        Schema::create('design_practices', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('framework_version_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('phase_id')->nullable()->constrained('design_phases')->nullOnDelete();

            /**
             * A practice is phrased as an instruction to carry out — "write
             * the core loop in one sentence", "run a two-player test".
             */
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();

            /**
             * How to actually do it, at whatever length it takes.
             *
             * Separate from the description because they are read at different
             * moments: the description is scanned in a list, the instructions
             * are followed once somebody has decided to do the thing.
             */
            $table->text('instructions')->nullable();

            $table->integer('position');
            $table->string('status')->default(FrameworkContentStatus::Draft->value);

            $table->timestamps();

            /**
             * No `completed` column, and that is the point. A practice such as
             * "run a two-player playtest" is part of the methodology and is
             * never finished; a *game* finishes it, and that fact lives in
             * `practice_completions` against the game's own adoption.
             */
            $table->unique(['framework_version_id', 'slug']);
            $table->index(['framework_version_id', 'phase_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_practices');
    }
};
