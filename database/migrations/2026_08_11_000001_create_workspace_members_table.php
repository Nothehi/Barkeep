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
        Schema::create('workspace_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role');
            $table->timestamp('joined_at');
            $table->timestamps();

            /**
             * The invariant that makes membership safe to reason about: one
             * account has at most one role in a workspace. Two administrators
             * inviting the same person concurrently collide here rather than
             * producing a member with two roles.
             */
            $table->unique(['workspace_id', 'user_id']);

            /** Serves "which workspaces do I belong to?", the hottest query in the module. */
            $table->index(['user_id', 'workspace_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_members');
    }
};
