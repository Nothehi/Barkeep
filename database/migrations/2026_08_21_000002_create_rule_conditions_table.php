<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameRules\Domain\Enums\ConditionOperator;
use Modules\GameRules\Domain\Enums\ConditionType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rule_conditions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rule_set_id')->constrained()->cascadeOnDelete();

            /**
             * Conditions are named and reused, which is the whole reason they are
             * rows rather than columns on the things that need them. "All players
             * have passed" is written once and pointed at from the phase
             * transition, the end condition and the trigger that all care about
             * it — so the name is the identity and is unique inside the set.
             */
            $table->string('name');
            $table->text('description')->nullable();

            $table->string('condition_type')->default(ConditionType::Custom->value);
            $table->string('operator')->default(ConditionOperator::Equals->value);

            /**
             * What the subject is compared against, as text.
             *
             * A string rather than a typed column because the ten operators
             * compare against different things: a number for `greater_than`, a
             * name for `equals`, a comma-separated list for `in`, and nothing at
             * all for `is_true`. The validator checks the pairing rather than the
             * schema, which is what keeps a condition readable by a person — see
             * section 18 of the module brief on why nothing evaluates these.
             */
            $table->string('value')->nullable();

            $table->timestamps();

            $table->unique(['rule_set_id', 'name']);
            $table->index(['rule_set_id', 'condition_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rule_conditions');
    }
};
