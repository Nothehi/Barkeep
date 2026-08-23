<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameRules\Domain\Enums\RuleStatus;
use Modules\GameRules\Domain\Enums\RuleType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rule_set_id')->constrained()->cascadeOnDelete();

            /**
             * Rules nest, because rulebooks do: "Combat" contains "declare
             * attacks", "choose defence", "resolve", "apply damage".
             *
             * Nulled on delete rather than cascaded, so removing a heading
             * promotes its children to the top level instead of quietly deleting
             * four rules somebody wrote. That is the safe reading of an ambiguous
             * gesture — a designer deleting "Combat" may well mean "these are not
             * a group any more".
             *
             * Nothing in the schema can see a cycle. `CycleDetector` refuses one
             * on the way in and the validator reports any that predate the check.
             */
            $table->foreignUuid('parent_rule_id')->nullable()
                ->constrained('game_rules')->nullOnDelete();

            /**
             * The phase this rule applies during, when it applies to one.
             *
             * Nullable because most rules are general: "you may not look at
             * another player's hand" is true throughout. Nulled on delete so
             * removing a phase does not take its rules with it.
             */
            $table->foreignUuid('phase_id')->nullable()
                ->constrained('game_phases')->nullOnDelete();

            $table->string('name');

            /**
             * Derived from the name, and the stable handle a rule is known by
             * inside its set. Unique per set, which is the scope a rule name is
             * meaningful in.
             */
            $table->string('slug');

            $table->text('description')->nullable();

            $table->string('rule_type')->default(RuleType::General->value);
            $table->string('status')->default(RuleStatus::Active->value);

            /**
             * The designer's own order, within the parent. A rulebook has a
             * reading order and alphabetising it away makes it unusable.
             */
            $table->unsignedInteger('position')->default(0);

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            $table->unique(['rule_set_id', 'slug']);
            $table->index(['rule_set_id', 'position']);
            $table->index(['rule_set_id', 'rule_type']);
            $table->index(['parent_rule_id', 'position']);
            $table->index('phase_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_rules');
    }
};
