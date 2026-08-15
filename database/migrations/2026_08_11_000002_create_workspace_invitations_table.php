<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Workspace\Domain\Enums\InvitationStatus;
use Modules\Workspace\Domain\Enums\WorkspaceRole;

return new class extends Migration
{
    /**
     * The index that stops one address being invited twice at once.
     *
     * Expressed as raw SQL because a partial unique index has no portable
     * builder API. The predicate is what makes it usable: a revoked or
     * accepted invitation must not block a fresh one, so only pending rows
     * take part in the constraint.
     */
    private const PENDING_UNIQUE_INDEX = 'workspace_invitations_pending_unique';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workspace_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role')->default(WorkspaceRole::Member->value);

            /**
             * Only the digest of the token is stored, so a database dump does
             * not hand out workspace access.
             */
            $table->string('token_hash', 64)->unique();

            $table->string('status')->default(InvitationStatus::Pending->value);
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            /** Lists a workspace's outstanding invitations. */
            $table->index(['workspace_id', 'status']);

            /** Finds every workspace an address has been invited to, for the post-registration hand-off. */
            $table->index(['email', 'status']);

            /** Supports sweeping expired invitations out of the pending list. */
            $table->index('expires_at');
        });

        DB::statement(sprintf(
            'CREATE UNIQUE INDEX %s ON workspace_invitations (workspace_id, email) WHERE status = %s',
            self::PENDING_UNIQUE_INDEX,
            DB::getPdo()->quote(InvitationStatus::Pending->value),
        ));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_invitations');
    }
};
