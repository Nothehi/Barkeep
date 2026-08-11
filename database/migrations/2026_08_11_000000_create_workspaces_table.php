<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Workspace\Domain\Enums\WorkspaceStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            /**
             * Ownership is restricted rather than cascading. A workspace is
             * the tenancy root for games, playtests and content, so deleting
             * the owner's account must not take a team's work with it —
             * ownership has to be transferred first.
             */
            $table->foreignUuid('owner_id')->constrained('users')->restrictOnDelete();

            $table->string('status')->default(WorkspaceStatus::Active->value)->index();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
