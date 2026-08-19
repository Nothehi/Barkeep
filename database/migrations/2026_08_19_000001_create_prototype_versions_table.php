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
        Schema::create('prototype_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * Cascading, and this is the one place in the module where that is
             * the right answer. A prototype version has no meaning apart from
             * the prototype it is a state of — "v3" of nothing is not a
             * record. Prototypes are archived rather than deleted in normal
             * use, so a delete that reaches here is a deliberate teardown.
             */
            $table->foreignUuid('prototype_id')->constrained()->cascadeOnDelete();

            /**
             * The ordinal a designer actually says out loud: v1, v2, v3.
             *
             * Allocated by the module in sequence and never supplied by a
             * caller, so there is no request that can claim v999, reuse v3 or
             * renumber history. The unique index below is what makes that
             * true under concurrency rather than merely intended — see
             * `CreatePrototypeVersion` for the lock-and-retry that leans on
             * it.
             */
            $table->unsignedInteger('version_number');

            $table->string('name')->nullable();
            $table->text('description')->nullable();

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            /**
             * The module's numbering guarantee, enforced by the database
             * rather than by the application remembering to check.
             */
            $table->unique(['prototype_id', 'version_number']);

            $table->index(['prototype_id', 'created_at']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prototype_versions');
    }
};
