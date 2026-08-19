<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameEconomy\Domain\Enums\ResourceFlowType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resource_flows', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * The profile is stored alongside the resource even though the
             * resource already knows it, for the reason every scoping column in
             * the platform is stored twice: every read of a flow is scoped by
             * profile, and joining through the resource to get there would put
             * the scope one table further away than it needs to be.
             *
             * The pair is proved to agree in the application layer before
             * anything is written — a flow may not name a resource from a
             * different profile — so the redundancy is checked rather than
             * assumed.
             */
            $table->foreignUuid('balance_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('resource_type_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->string('flow_type')->default(ResourceFlowType::Generation->value);

            /**
             * How much moves, always as a positive magnitude. The direction is
             * the flow type's job — storing "-2 consumption" would let a sign
             * and a type disagree, and then the net calculation has to guess
             * which one the designer meant.
             */
            $table->decimal('amount', 20, 6)->default(0);

            /**
             * When the flow happens, in the designer's own words: "per round",
             * "when a worker is placed", "if the market is empty".
             *
             * Deliberately prose rather than an expression. This module is a
             * model and an analysis layer, not a rules engine — a scripting
             * language here would be a simulator wearing a text column, and the
             * brief is explicit that simulation is a different bounded context.
             */
            $table->text('condition')->nullable();

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->index(['balance_profile_id', 'position']);
            $table->index(['resource_type_id', 'flow_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_flows');
    }
};
