<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\PrototypeIteration\Domain\Enums\EvidenceType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('decision_evidence', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('decision_id')
                ->constrained('design_decisions')
                ->cascadeOnDelete();

            $table->string('type')->default(EvidenceType::Note->value);

            /**
             * What the citation points at, and the deliberate limit of this
             * table.
             *
             * A type and a bare id, with no foreign key and no polymorphic
             * `*_type` class name. That is not an oversight — it is the shape
             * that lets a decision cite an observation without this module
             * holding a copy of it, or a foreign key into another context's
             * tables, or a class name that breaks when that context is
             * refactored.
             *
             * The consequence is that the reference is resolved rather than
             * joined: whoever owns the type resolves the id, through the
             * contract they publish, scoped to the same game. Playtesting
             * evidence goes through this module's Playtesting adapter;
             * experiments resolve against `design_experiments` here. A
             * reference that no longer resolves renders as a citation whose
             * target is gone, which is honest — and better than a cascade that
             * would silently delete the argument along with the exhibit.
             *
             * Null for a note, which *is* the evidence rather than a pointer to
             * it.
             */
            $table->uuid('reference_id')->nullable();

            /**
             * Why this was cited, or — for a note — the whole content.
             *
             * Never a copy of the referenced record. "Players spent less time
             * waiting" belongs to the observation in Playtesting; what belongs
             * here is the reason a designer thought that observation supported
             * this decision.
             */
            $table->text('description')->nullable();

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            /**
             * Nothing stops the same record being cited twice by the same
             * decision, and that is on purpose: two citations of one playtest
             * with different reasons are two arguments, not a duplicate.
             */
            $table->index(['decision_id', 'created_at']);
            $table->index(['type', 'reference_id']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decision_evidence');
    }
};
