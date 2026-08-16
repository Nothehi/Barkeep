<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameDesign\Domain\Enums\DesignPhase;
use Modules\GameDesign\Domain\Enums\GameStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * The tenancy boundary, and the reason every other index on this
             * table starts with it. A game never moves between workspaces, so
             * this column is effectively part of its identity.
             *
             * Cascading: a workspace is archived rather than deleted in
             * normal use, so a delete that does reach here is a deliberate
             * teardown, and leaving orphaned games behind would be worse than
             * removing them.
             */
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            $table->string('status')->default(GameStatus::Draft->value);
            $table->string('design_phase')->default(DesignPhase::Idea->value);

            /**
             * Restricted rather than cascading: a game belongs to the
             * workspace, not to whoever happened to start it, so deleting
             * that account must not take the studio's project with it.
             */
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            /**
             * Addresses are unique per workspace, not globally. Two studios
             * may each be working on a "bears-and-bridges" without either
             * having to rename, and this is the constraint that both permits
             * that and settles the race when two people in one workspace
             * claim the same address at once.
             */
            $table->unique(['workspace_id', 'slug']);

            /**
             * Serves the games screen: every list is scoped to a workspace
             * and ordered by last activity, and the two filters it offers are
             * status and design phase.
             */
            $table->index(['workspace_id', 'updated_at']);
            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'design_phase']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
