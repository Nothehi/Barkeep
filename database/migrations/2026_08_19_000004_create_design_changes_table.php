<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\PrototypeIteration\Domain\Enums\DesignChangeCategory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('design_changes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('iteration_id')->constrained()->cascadeOnDelete();

            $table->string('category')->default(DesignChangeCategory::Other->value);
            $table->string('title');
            $table->text('description')->nullable();

            /**
             * Why the change was made, and the column this table exists for.
             *
             * A list of edits is a changelog; a list of edits with reasons is a
             * design rationale, and the second is the thing a designer needs
             * eighteen months later when somebody asks why the trading phase
             * is gone. It is required for that reason — an unexplained change
             * is the one entry in a history that cannot be learned from.
             */
            $table->text('reason');

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            /**
             * Serves the iteration timeline, which reads a single cycle's
             * changes in the order they were recorded, and the category
             * grouping beside it.
             */
            $table->index(['iteration_id', 'created_at']);
            $table->index(['iteration_id', 'category']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_changes');
    }
};
