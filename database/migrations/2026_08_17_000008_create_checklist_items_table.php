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
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * The one content row that hangs off a checklist rather than off a
             * version. Items have no meaning apart from the list they belong
             * to, so their address is unique within it and they cascade with
             * it — which in turn cascades from the version.
             */
            $table->foreignUuid('checklist_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();

            $table->integer('position');

            /**
             * Whether the item has to be ticked for the checklist to count as
             * satisfied.
             *
             * Required by default, because a checklist of optional items is a
             * list of suggestions. Only the required items count towards phase
             * progress — see `FrameworkProgressCalculator` — which is what
             * lets a framework author add a nice-to-have without moving
             * everybody's percentages.
             */
            $table->boolean('required')->default(true);

            $table->timestamps();

            /**
             * No `completed` column. An item is a requirement the framework
             * states; whether a particular game has met it lives in
             * `checklist_item_completions`.
             */
            $table->unique(['checklist_id', 'slug']);
            $table->index(['checklist_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
    }
};
