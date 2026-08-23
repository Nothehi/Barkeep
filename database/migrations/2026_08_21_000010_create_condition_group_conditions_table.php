<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * Which conditions are in a group, and in what order.
         *
         * A table of its own rather than a pivot with no key, because the order
         * is the designer's and a pivot Eloquent orders by insertion is one that
         * reshuffles the day somebody re-saves it.
         */
        Schema::create('condition_group_conditions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('condition_group_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('condition_id')->constrained('rule_conditions')->cascadeOnDelete();

            $table->unsignedInteger('position')->default(0);

            /**
             * A condition appears in a group once. Listing it twice under `and`
             * says nothing new, and under `or` says nothing at all.
             */
            $table->unique(['condition_group_id', 'condition_id']);

            $table->index(['condition_group_id', 'position']);
            $table->index('condition_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('condition_group_conditions');
    }
};
