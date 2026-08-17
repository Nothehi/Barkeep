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
        Schema::create('checklists', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('framework_version_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('phase_id')->nullable()->constrained('design_phases')->nullOnDelete();

            /**
             * A checklist is a readiness gate — "prototype readiness",
             * "playtest readiness" — and its title is read as one.
             */
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
        Schema::dropIfExists('checklists');
    }
};
