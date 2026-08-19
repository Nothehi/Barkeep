<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameEconomy\Domain\Enums\BalanceScenarioStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A hypothetical: "what does this economy look like at four players?", "what
     * if everybody starts rich?". A scenario names a situation and then overrides
     * the variables that differ in it — see `scenario_variables`, which is where
     * the overrides live and where the rule that they never touch the base value
     * is made structural.
     */
    public function up(): void
    {
        Schema::create('balance_scenarios', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('balance_profile_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->string('status')->default(BalanceScenarioStatus::Draft->value);

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            /**
             * Unlike profiles, any number of scenarios may be active at once: a
             * studio compares two-player and four-player side by side, and that
             * is the whole point of having them.
             */
            $table->unique(['balance_profile_id', 'name']);
            $table->index(['balance_profile_id', 'status']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_scenarios');
    }
};
