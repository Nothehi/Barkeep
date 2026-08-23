<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameRules\Domain\Enums\GamePhaseType;
use Modules\GameRules\Domain\Enums\RuleStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_phases', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * Cascading is correct throughout this module's child tables: a phase
             * has no meaning outside the rule set that declares it.
             */
            $table->foreignUuid('rule_set_id')->constrained()->cascadeOnDelete();

            /**
             * Phases nest — an action phase inside a round — and the nesting is
             * one level of structure rather than an arbitrary tree. Nulled on
             * delete so removing a parent flattens its children rather than
             * silently deleting a chunk of the turn structure.
             *
             * The database cannot see a cycle here; `CycleDetector` refuses one
             * on the way in and the validator reports any that predate it.
             */
            $table->foreignUuid('parent_phase_id')->nullable()
                ->constrained('game_phases')->nullOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            $table->string('phase_type')->default(GamePhaseType::Round->value);
            $table->string('status')->default(RuleStatus::Active->value);

            /**
             * The order the phases happen in. This is the one `position` in the
             * module that means something beyond display: a turn structure read
             * out of order is a different turn structure.
             */
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->unique(['rule_set_id', 'slug']);
            $table->index(['rule_set_id', 'position']);
            $table->index(['rule_set_id', 'phase_type']);
            $table->index('parent_phase_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_phases');
    }
};
