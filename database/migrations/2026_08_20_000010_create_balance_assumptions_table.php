<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameEconomy\Domain\Enums\AssumptionCategory;
use Modules\GameEconomy\Domain\Enums\AssumptionConfidence;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Why a number is what it is.
     *
     * A balance profile without this table is a spreadsheet: it says wood
     * production is 3 and gives the next designer no way to find out whether
     * that was measured, argued for or typed. An assumption is the belief the
     * numbers were chosen to satisfy, written down before the evidence arrives
     * so that the evidence can contradict it.
     */
    public function up(): void
    {
        Schema::create('balance_assumptions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('balance_profile_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('category')->default(AssumptionCategory::Other->value);

            /**
             * How much the studio actually believes it.
             *
             * The field that makes the record honest. "Players should be able to
             * afford one major action per round" held with low confidence is a
             * thing to test; held with high confidence it is a thing to design
             * around, and a table that could not tell them apart would flatten
             * every belief into an assertion.
             */
            $table->string('confidence')->default(AssumptionConfidence::Medium->value);

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            $table->index(['balance_profile_id', 'created_at']);
            $table->index(['balance_profile_id', 'confidence']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_assumptions');
    }
};
