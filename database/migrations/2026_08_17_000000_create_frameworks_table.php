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
        Schema::create('frameworks', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');

            /**
             * Globally unique, unlike a game address.
             *
             * A framework is a methodology the whole platform shares rather
             * than a document inside a workspace, so there is no tenant to
             * scope its address to. That is the same reason there is no
             * `workspace_id` here: a framework belongs to Barkeep, and a
             * workspace *adopts* one — see `game_frameworks`.
             */
            $table->string('slug')->unique();

            $table->text('description')->nullable();

            /**
             * Draft, published or archived. The lifecycle matrix lives on the
             * enum; this column only records where the framework got to.
             */
            $table->string('status')->default(FrameworkStatus::Draft->value);

            /**
             * Restricted rather than cascading: a methodology outlives the
             * account that wrote it down, and games follow its versions.
             */
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            /**
             * The frameworks screen lists everything and filters by status;
             * the games that adopt one only ever reach it through a version.
             */
            $table->index('status');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frameworks');
    }
};
