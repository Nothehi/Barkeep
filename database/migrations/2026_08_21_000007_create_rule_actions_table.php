<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameRules\Domain\Enums\RuleActionType;
use Modules\GameRules\Domain\Enums\RuleStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rule_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rule_set_id')->constrained()->cascadeOnDelete();

            /**
             * When the action may be taken. Nullable in the schema because an
             * action is created before the turn structure is settled, and
             * reported by the validator as an error once it stays that way — an
             * action nobody can place in the turn is an action nobody can take.
             */
            $table->foreignUuid('phase_id')->nullable()
                ->constrained('game_phases')->nullOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            $table->string('action_type')->default(RuleActionType::Basic->value);
            $table->string('status')->default(RuleStatus::Active->value);

            /**
             * The handle of the GameEconomy action this one costs and pays
             * through, when the studio has modelled its economy.
             *
             * A slug rather than a foreign key, and that is section 34 of the
             * brief made structural. A foreign key would mean this module holding
             * a GameEconomy identifier and, sooner or later, joining to its
             * tables; a handle is resolved at render time through the one adapter
             * allowed to talk to that module, and resolves to nothing when there
             * is no active balance profile. Nothing here stores a cost.
             */
            $table->string('economy_action_slug')->nullable();

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->unique(['rule_set_id', 'slug']);
            $table->index(['rule_set_id', 'position']);
            $table->index(['rule_set_id', 'action_type']);
            $table->index('phase_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rule_actions');
    }
};
