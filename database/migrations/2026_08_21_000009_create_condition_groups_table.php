<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameRules\Domain\Enums\LogicOperator;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('condition_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rule_set_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            /**
             * One operator for the whole group, and no nesting.
             *
             * Section 19 of the brief is firm about this and the restraint is
             * deliberate rather than provisional: an arbitrary expression tree
             * needs a parser, a renderer and a precedence rule, and by the time a
             * studio needs one they need something that can evaluate it too. A
             * flat group covers what a board game rule usually says and stays
             * readable in a form somebody fills in.
             */
            $table->string('logic_operator')->default(LogicOperator::And->value);

            $table->timestamps();

            $table->unique(['rule_set_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('condition_groups');
    }
};
