<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameRules\Domain\Enums\TriggerType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rule_triggers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rule_set_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->string('trigger_type')->default(TriggerType::Custom->value);

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            /**
             * Deliberately no `fires_effect_id`, and no join table to one.
             *
             * A trigger records *when* something happens automatically. Wiring it
             * to something that would then run is the first line of a game engine
             * living inside a design tool — see section 23 of the brief. What a
             * trigger guards is expressed the other way round: a phase transition
             * names the trigger that moves it.
             */
            $table->unique(['rule_set_id', 'name']);
            $table->index(['rule_set_id', 'position']);
            $table->index(['rule_set_id', 'trigger_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rule_triggers');
    }
};
