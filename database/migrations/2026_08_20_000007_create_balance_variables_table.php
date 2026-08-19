<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameEconomy\Domain\Enums\BalanceVariableCategory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('balance_variables', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('balance_profile_id')->constrained()->cascadeOnDelete();

            /**
             * What the number is about, where it is about anything in the model.
             *
             * Two nullable foreign keys rather than a polymorphic pair, because
             * there are exactly two things a variable can point at and a real
             * foreign key on each is worth more than the flexibility a
             * `*_type`/`*_id` column would buy. The database refuses a variable
             * pointing at a resource that has been removed; a polymorphic
             * reference would leave it dangling and the analysis reading a
             * number about nothing.
             *
             * Both nullable because plenty of variables are about the game rather
             * than about anything in it — a victory threshold, a round limit.
             */
            $table->foreignUuid('resource_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('action_id')->nullable()->constrained('economy_actions')->nullOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            /**
             * The number itself, and the reason this whole module refuses
             * floating point. A victory threshold read back as 19.999999 is a
             * bug report; a probability that drifts is a balance decision made
             * by an accident of binary representation.
             */
            $table->decimal('value', 20, 6)->default(0);

            $table->string('unit')->nullable();

            /**
             * The range the designer considers sane, and the increment they tune
             * in. All three nullable: a variable with no declared range is the
             * ordinary case, and the analysis reports a value outside a range
             * only where one was actually stated.
             */
            $table->decimal('min_value', 20, 6)->nullable();
            $table->decimal('max_value', 20, 6)->nullable();
            $table->decimal('step', 20, 6)->nullable();

            $table->string('category')->default(BalanceVariableCategory::Other->value);

            $table->timestamps();

            $table->unique(['balance_profile_id', 'slug']);
            $table->index(['balance_profile_id', 'category']);
            $table->index('resource_type_id');
            $table->index('action_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_variables');
    }
};
