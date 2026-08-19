<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The mirror of `action_costs`, and separate from it rather than one table
     * with a direction column. Costs and rewards are asked about separately
     * everywhere — profitability subtracts one from the other, the warnings read
     * only one at a time, and the editor puts them in two panels — so a shared
     * table would mean every query in the module carrying a filter it could
     * forget.
     */
    public function up(): void
    {
        Schema::create('action_rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('action_id')->constrained('economy_actions')->cascadeOnDelete();
            $table->foreignUuid('resource_type_id')->constrained()->restrictOnDelete();

            $table->decimal('amount', 20, 6)->default(0);

            $table->boolean('is_variable')->default(false);
            $table->decimal('min_amount', 20, 6)->nullable();
            $table->decimal('max_amount', 20, 6)->nullable();

            $table->timestamps();

            $table->unique(['action_id', 'resource_type_id']);
            $table->index('resource_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_rewards');
    }
};
