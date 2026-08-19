<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Costs are normalised rows rather than a JSON blob on the action, which is
     * the difference between an economy that can be analysed and one that can
     * only be displayed. "Which actions spend wood?" and "what is wood's total
     * consumption?" are the two questions this module exists to answer, and both
     * are a `where` here and a `sum` in a document store.
     *
     * It also lets the database refuse to delete a resource anything is priced
     * in, which is where the referential integrity in a balance configuration
     * actually lives.
     */
    public function up(): void
    {
        Schema::create('action_costs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('action_id')->constrained('economy_actions')->cascadeOnDelete();

            /**
             * Restricted rather than cascading. Removing a resource that actions
             * are priced in would silently make every one of them free, which is
             * the single most damaging thing that could happen to a balance
             * configuration without anybody noticing.
             */
            $table->foreignUuid('resource_type_id')->constrained()->restrictOnDelete();

            $table->decimal('amount', 20, 6)->default(0);

            /**
             * A cost that is not one number: "3 to 5 wood, depending on the
             * terrain". The flag is what tells the analysis whether to read the
             * bounds beside it, so that a fixed cost of 5 and a variable cost
             * that happens to average 5 are never confused.
             */
            $table->boolean('is_variable')->default(false);
            $table->decimal('min_amount', 20, 6)->nullable();
            $table->decimal('max_amount', 20, 6)->nullable();

            $table->timestamps();

            /**
             * One line per resource per action. A build that costs "2 wood and
             * 3 more wood" is a data entry mistake, not a design — it should be
             * a single line reading 5.
             */
            $table->unique(['action_id', 'resource_type_id']);
            $table->index('resource_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_costs');
    }
};
