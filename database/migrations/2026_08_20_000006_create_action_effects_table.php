<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameEconomy\Domain\Enums\ActionEffectType;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Everything an action does that is not a quantity of a resource: unlocking
     * a technology, raising a hand limit, blocking a route.
     *
     * There is no foreign key to a resource here, and that is the point of the
     * table existing at all. An effect names its target in the designer's own
     * words because the things it targets are not all rows — "building level 2"
     * is not a resource, and modelling it as one to get a foreign key would be
     * the schema telling the designer what their game is allowed to contain.
     */
    public function up(): void
    {
        Schema::create('action_effects', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('action_id')->constrained('economy_actions')->cascadeOnDelete();

            $table->string('effect_type')->default(ActionEffectType::Other->value);

            /**
             * What the effect acts on — "maximum hand size", "building cost",
             * "Building II". Free text, for the reason above.
             */
            $table->string('target');

            /**
             * How much it acts by, where that is a number at all. Nullable
             * because an unlock has no magnitude, and defaulting it to zero
             * would make "unlocks nothing" and "unlocks a thing" look alike.
             */
            $table->decimal('value', 20, 6)->nullable();

            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['action_id', 'effect_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_effects');
    }
};
