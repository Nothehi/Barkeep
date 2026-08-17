<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('framework_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * Cascading: a version is one edition of a particular methodology
             * and has no meaning apart from it.
             */
            $table->foreignUuid('framework_id')->constrained()->cascadeOnDelete();

            /**
             * Allocated by the module, never chosen by a caller — see
             * `CreateFrameworkVersion`. The unique index below is what makes
             * that allocation safe when two people press the button at once.
             */
            $table->integer('version_number');

            /**
             * Optional, and it falls back to "v3" wherever it is read. A
             * version number is what designers actually cite; a name is for
             * the editions that earned one.
             */
            $table->string('name')->nullable();
            $table->text('description')->nullable();

            $table->string('status')->default(FrameworkStatus::Draft->value);

            /**
             * When the version stopped being editable.
             *
             * Derivable from the status, and stored anyway, because it is the
             * fact a game's adoption is read against: "this game follows v1,
             * published in March" is the sentence historical integrity exists
             * to keep true.
             */
            $table->timestamp('published_at')->nullable();

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            /**
             * The invariant behind version numbering. Allocation reads the
             * highest number under a row lock and then inserts; where the lock
             * is weaker than it looks — SQLite ignores `FOR UPDATE` — this
             * index is what still refuses the duplicate.
             */
            $table->unique(['framework_id', 'version_number']);

            $table->index(['framework_id', 'status']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('framework_versions');
    }
};
