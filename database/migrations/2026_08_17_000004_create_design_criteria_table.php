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
        Schema::create('design_criteria', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('framework_version_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('phase_id')->nullable()->constrained('design_phases')->nullOnDelete();

            /**
             * A criterion is written as a question — "does the game provide
             * meaningful decisions?" — because it is answered rather than read.
             */
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();

            $table->integer('position');
            $table->string('status')->default(FrameworkContentStatus::Draft->value);

            $table->timestamps();

            /**
             * There is deliberately no score, rating or weight here.
             *
             * A criterion is the *question*; what a particular game scored
             * against it lives in `criterion_evaluations`, keyed by the game's
             * framework adoption. Putting an answer on the question would mean
             * every game that adopted this version shared one, which is the
             * single most tempting mistake this module could make.
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
        Schema::dropIfExists('design_criteria');
    }
};
