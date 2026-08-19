<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One override: "in Rich Economy, starting gold is 15".
     *
     * The separate table is what makes "a scenario never modifies the base
     * variable" a structural fact rather than a rule somebody has to remember.
     * There is no code path on which applying a scenario writes to
     * `balance_variables`, because a scenario's values are not stored there — the
     * base value and the override sit in different rows in different tables, and
     * the two are combined on read.
     */
    public function up(): void
    {
        Schema::create('scenario_variables', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('scenario_id')->constrained('balance_scenarios')->cascadeOnDelete();

            /**
             * Cascading, because an override of a variable that no longer exists
             * is not a value — it is a number about nothing, and keeping it would
             * make a scenario read as though it changed something it does not.
             */
            $table->foreignUuid('balance_variable_id')->constrained()->cascadeOnDelete();

            $table->decimal('value', 20, 6);

            $table->timestamps();

            /**
             * A scenario states one value for a variable. Two rows would be two
             * answers to the same question with no way to choose between them.
             */
            $table->unique(['scenario_id', 'balance_variable_id']);
            $table->index('balance_variable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scenario_variables');
    }
};
