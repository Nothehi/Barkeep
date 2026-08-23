<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameRules\Domain\Enums\ReferenceType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * The edges of the rule graph: which rule depends on, modifies,
         * overrides or is an exception to which other.
         *
         * These are the facts a designer loses first and needs most. "Siege is an
         * exception to Combat" lives in somebody's head until the day they change
         * Combat, and writing it down is what lets the validator notice a cycle
         * before a playtester does.
         */
        Schema::create('rule_references', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * Both ends cascade. A reference is *about* the pair; one with a
             * missing end is not a reference with a gap in it.
             *
             * Note what is absent: there is no `rule_set_id` here. The set is
             * reachable through either rule, and storing it would create a third
             * place the answer lives and a way for the three to disagree. That
             * both rules belong to the *same* set is the invariant that matters,
             * and it is checked by resolving the referenced rule through the
             * referring rule's own set — see `RuleCatalogue`.
             */
            $table->foreignUuid('rule_id')->constrained('game_rules')->cascadeOnDelete();
            $table->foreignUuid('referenced_rule_id')->constrained('game_rules')->cascadeOnDelete();

            $table->string('reference_type')->default(ReferenceType::RelatedTo->value);
            $table->text('description')->nullable();

            $table->timestamps();

            /**
             * One edge per pair per kind. Two rules may legitimately both depend
             * on and be exceptions to each other's readings, so the type is part
             * of the key; the same statement twice is a duplicate.
             */
            $table->unique(['rule_id', 'referenced_rule_id', 'reference_type']);

            $table->index('referenced_rule_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rule_references');
    }
};
