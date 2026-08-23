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
        Schema::create('victory_conditions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rule_set_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            /**
             * What makes it true, when the studio has stated it precisely.
             *
             * Nullable, because "whoever has the most points at the end" gets
             * written down long before anybody defines a condition for it. The
             * validator reports the gap rather than the schema refusing it —
             * nobody can tell when an outcome without a condition has been met,
             * which is worth saying and not worth blocking on.
             */
            $table->foreignUuid('condition_id')->nullable()
                ->constrained('rule_conditions')->nullOnDelete();

            /**
             * Which outcome is checked first. Games routinely have several and
             * the order settles ties: "control three territories" beating "most
             * points" is the rule, not a display preference.
             */
            $table->unsignedInteger('priority')->default(0);

            $table->timestamps();

            $table->unique(['rule_set_id', 'name']);
            $table->index(['rule_set_id', 'priority']);
            $table->index('condition_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('victory_conditions');
    }
};
