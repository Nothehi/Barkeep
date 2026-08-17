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
        Schema::create('design_principles', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * The version owns the principle; the phase only files it.
             *
             * Both columns are kept even though the phase knows its version,
             * because a principle may have no phase at all — "every decision
             * should have meaningful consequences" is true at every stage —
             * and every read is version-scoped either way. Storing the version
             * means those reads never have to join through a nullable parent.
             */
            $table->foreignUuid('framework_version_id')->constrained()->cascadeOnDelete();

            /**
             * Null when the principle applies to the whole methodology rather
             * than to one stage of it. Nulled rather than cascaded on delete,
             * so removing a phase from a draft version files its principles
             * back at the top level instead of destroying them.
             */
            $table->foreignUuid('phase_id')->nullable()->constrained('design_phases')->nullOnDelete();

            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();

            $table->integer('position');
            $table->string('status')->default(FrameworkContentStatus::Draft->value);

            $table->timestamps();

            $table->unique(['framework_version_id', 'slug']);
            $table->index(['framework_version_id', 'phase_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_principles');
    }
};
