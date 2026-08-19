<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\PrototypeIteration\Domain\Enums\DecisionStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('design_decisions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('iteration_id')->constrained()->cascadeOnDelete();

            $table->string('title');

            /**
             * What was decided, and why.
             *
             * Both required, and the pair is the single most valuable thing
             * this module stores. A decision without a reason is an
             * instruction; a decision with one is an argument somebody can
             * re-examine when the situation changes — which is the only way a
             * design history is any use.
             *
             * `decision` is separate from `title` because the two get read at
             * different distances: the title is scanned in a list ("Reaction
             * phase"), the decision is the sentence itself ("Remove the
             * reaction phase permanently").
             */
            $table->text('decision');
            $table->text('reason');

            $table->string('status')->default(DecisionStatus::Proposed->value);

            /**
             * Who settled it, and when.
             *
             * Nullable because a proposed decision has not been settled by
             * anybody yet, and filling this in on creation would make every
             * proposal look agreed. Both are written by the lifecycle commands
             * at the moment of the accept, reject or defer.
             *
             * Restricted on delete rather than nulled: removing the account
             * that agreed something must not quietly turn an attributed
             * decision into an anonymous one.
             */
            $table->foreignUuid('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('decided_at')->nullable();

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            $table->index(['iteration_id', 'created_at']);
            $table->index(['iteration_id', 'status']);
            $table->index('decided_by');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_decisions');
    }
};
