<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameRules\Domain\Enums\RequirementType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rule_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rule_set_id')->constrained()->cascadeOnDelete();

            /**
             * What the requirement is a requirement *of*. Exactly one of these is
             * set, which the schema cannot express and the commands enforce: a
             * requirement belonging to both a rule and an action would be checked
             * twice and edited in two places, and one belonging to neither is
             * never checked at all — the validator reports that as an error.
             *
             * Both cascade, because a requirement describes its owner and nothing
             * else.
             */
            $table->foreignUuid('action_id')->nullable()
                ->constrained('rule_actions')->cascadeOnDelete();
            $table->foreignUuid('rule_id')->nullable()
                ->constrained('game_rules')->cascadeOnDelete();

            $table->string('requirement_type')->default(RequirementType::Custom->value);

            /**
             * What has to be true, in words. Required, because a requirement
             * nobody can read is not a requirement — see section 17 of the brief
             * on why this is prose rather than an expression.
             */
            $table->text('description');

            /**
             * The threshold, when there is one: "5", "at least 2", "the workshop".
             * A string for the same reason a condition's value is one.
             */
            $table->string('value')->nullable();

            /**
             * The handle of the GameEconomy resource this requirement is priced
             * in, for the `resource` type. A handle rather than a foreign key —
             * this module never holds a cost. See section 34 of the brief.
             */
            $table->string('economy_resource_slug')->nullable();

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->index(['rule_set_id', 'position']);
            $table->index(['action_id', 'position']);
            $table->index(['rule_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rule_requirements');
    }
};
