<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameEconomy\Domain\Enums\ResourceCategory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resource_types', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * Resources belong to a profile, not to a game.
             *
             * That is what makes a profile a complete configuration: everything
             * a snapshot has to freeze, and everything a comparison has to diff,
             * hangs off this one column. Cascading is correct here — a resource
             * has no meaning outside the configuration that declares it.
             */
            $table->foreignUuid('balance_profile_id')->constrained()->cascadeOnDelete();

            $table->string('name');

            /**
             * Derived from the name, and the stable handle a designer uses when
             * talking about the resource. Unique inside the profile, which is
             * the scope the name is meaningful in.
             */
            $table->string('slug');

            $table->string('category')->default(ResourceCategory::Other->value);
            $table->text('description')->nullable();

            /**
             * What one of these is called: "cubes", "coins", "cards". Display
             * only — nothing computes with it — but a flow reading "+3" is
             * ambiguous in a way "+3 cubes" is not.
             */
            $table->string('unit')->nullable();

            /**
             * What the resource can do, which is what separates gold from action
             * points. Defaults describe the ordinary case — a material you gather,
             * hold and spend — so a designer only edits the ones that differ.
             */
            $table->boolean('is_tradeable')->default(true);
            $table->boolean('is_accumulative')->default(true);
            $table->boolean('is_spendable')->default(true);
            $table->boolean('is_convertible')->default(false);

            /**
             * The bounds a value lives inside, and where a player starts.
             *
             * Decimal rather than float throughout the module. A game that pays
             * out half a coin is rare but real, and floating point makes 0.1 + 0.2
             * a support ticket — see the note in section 47 of the module brief.
             *
             * All three are nullable because "unbounded" and "zero" are different
             * statements: a resource with no cap is one the analysis warns about,
             * and a resource capped at zero is nonsense.
             */
            $table->decimal('min_value', 20, 6)->nullable();
            $table->decimal('max_value', 20, 6)->nullable();
            $table->decimal('starting_value', 20, 6)->nullable();

            /**
             * The order the designer arranged them in. Economies have a natural
             * reading order — raw materials, then currency, then victory points —
             * and alphabetising it away makes the list harder to scan.
             */
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->unique(['balance_profile_id', 'slug']);
            $table->index(['balance_profile_id', 'position']);
            $table->index(['balance_profile_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_types');
    }
};
