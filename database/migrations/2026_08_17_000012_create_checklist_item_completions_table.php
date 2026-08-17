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
        Schema::create('checklist_item_completions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * The framework states the requirement; the game records having met
             * it. Same separation as practices and criteria, and for the same
             * reason: one published checklist is read by every game following
             * the version it belongs to.
             */
            $table->foreignUuid('game_framework_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('checklist_item_id')->constrained()->cascadeOnDelete();

            $table->foreignUuid('completed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('completed_at');

            $table->text('notes')->nullable();

            $table->timestamps();

            /**
             * The row's existence is the tick. Unticking deletes it, which is
             * why a checklist item is genuinely binary rather than a workflow.
             */
            $table->unique(['game_framework_id', 'checklist_item_id']);

            $table->index('completed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_item_completions');
    }
};
