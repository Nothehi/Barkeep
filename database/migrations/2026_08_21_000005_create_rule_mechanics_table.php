<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameRules\Domain\Enums\MechanicCategory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * `rule_mechanics`, not `mechanics`.
         *
         * The platform already has a `mechanics` table, and it is a different
         * thing: GameDesign's is the shared *vocabulary* of design terms — the
         * eighty-odd canonical names for worker placement, deck building and the
         * rest, seeded once and translated on the way out, that a design record
         * tags itself with.
         *
         * A row here is a mechanism present in one game's rule system, named in
         * that studio's own words and sitting beside the rules that operate it.
         * The two would fight over the same table name and mean different things
         * in it, so this one is prefixed. Section 10 of the module brief calls
         * this entity `Mechanic`; the rename is the one place this module departs
         * from its own naming, and it is because the shorter name was taken.
         */
        Schema::create('rule_mechanics', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rule_set_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            $table->string('category')->default(MechanicCategory::Other->value);

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->unique(['rule_set_id', 'slug']);
            $table->index(['rule_set_id', 'position']);
            $table->index(['rule_set_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rule_mechanics');
    }
};
