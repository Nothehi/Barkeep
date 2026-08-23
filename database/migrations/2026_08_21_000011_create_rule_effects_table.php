<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameRules\Domain\Enums\EffectType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rule_effects', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rule_set_id')->constrained()->cascadeOnDelete();

            /**
             * What the effect is an effect *of* — exactly one of the two, for the
             * same reason a requirement has exactly one owner.
             */
            $table->foreignUuid('action_id')->nullable()
                ->constrained('rule_actions')->cascadeOnDelete();
            $table->foreignUuid('rule_id')->nullable()
                ->constrained('game_rules')->cascadeOnDelete();

            $table->string('effect_type')->default(EffectType::Custom->value);

            /**
             * What the effect acts on: "Wood", "the active player", "building
             * level 2".
             *
             * Free text and required. The things an effect targets are not all
             * rows — a board position is not a record anywhere — so there is
             * nothing to validate it against beyond it being said at all.
             */
            $table->string('target');

            /**
             * How much, when the effect type implies an amount. A string, so that
             * "+3", "-1" and "all of them" are all sayable — nothing computes with
             * it, because nothing in this module executes an effect. See section
             * 33 of the brief.
             */
            $table->string('value')->nullable();

            $table->text('description')->nullable();

            /**
             * The handle of the GameEconomy record this effect moves, when the
             * studio has modelled its economy. A handle rather than a foreign key,
             * and never a copy of the amount.
             */
            $table->string('economy_resource_slug')->nullable();

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->index(['rule_set_id', 'position']);
            $table->index(['action_id', 'position']);
            $table->index(['rule_id', 'position']);
            $table->index(['rule_set_id', 'effect_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rule_effects');
    }
};
