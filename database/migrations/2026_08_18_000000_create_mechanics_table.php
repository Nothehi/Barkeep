<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameDesign\Domain\Enums\MechanicStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mechanics', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');

            /**
             * Unique across the platform, unlike a game's address which is only
             * unique inside a workspace. A mechanic is not anybody's: the whole
             * value of the vocabulary is that `worker-placement` means one thing
             * to every game that claims it.
             */
            $table->string('slug')->unique();

            $table->text('description')->nullable();

            $table->string('category');

            /**
             * Retired rather than deleted. A mechanic that turns out to be a
             * duplicate or a bad idea stops being offered, and the games that
             * already claimed it keep saying what they said — deleting the row
             * would quietly rewrite their design record instead.
             */
            $table->string('status')->default(MechanicStatus::Published->value);

            $table->timestamps();

            /**
             * The vocabulary is read as a whole, grouped by category and
             * alphabetical within it, on every screen that offers it.
             */
            $table->index(['category', 'name']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mechanics');
    }
};
