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
        Schema::create('design_prompts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('framework_version_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('phase_id')->nullable()->constrained('design_phases')->nullOnDelete();

            /**
             * The short label a prompt is listed under, distinct from the
             * question itself. "Core experience" is scannable in a phase page;
             * "What does the player learn in the first five minutes?" is what
             * sits above the textarea.
             */
            $table->string('title');
            $table->string('slug');

            /**
             * Required, because a prompt with no question is nothing. This is
             * the only content type whose body is mandatory.
             */
            $table->text('prompt');

            $table->integer('position');
            $table->string('status')->default(FrameworkContentStatus::Draft->value);

            $table->timestamps();

            /**
             * The answer is not here. A prompt is asked of every game that
             * adopts the version; each game's answer lives in
             * `prompt_responses`.
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
        Schema::dropIfExists('design_prompts');
    }
};
