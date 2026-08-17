<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Which fact of a game's design record answers this piece of content.
     *
     * Null for almost everything, and that is the normal case: "is the core
     * decision meaningful?" is a judgement nothing can answer for a designer,
     * and it keeps the four-point scale. But "are the player count and playing
     * time decided?" is a question about whether a fact has been written down,
     * and asking somebody to grade themselves on it was always the wrong shape —
     * they ticked "player count decided" on their own word while the platform
     * had no idea whether it was.
     *
     * A string key rather than a foreign key, because the thing being named is a
     * field on a record rather than a row: `player_count`, `core_action`,
     * `mechanics`. The keys are defined in `DesignFacts`, which is the only
     * place that knows how to read one.
     */
    public function up(): void
    {
        Schema::table('design_criteria', function (Blueprint $table) {
            $table->string('satisfied_by')->nullable()->after('description');
        });

        Schema::table('checklist_items', function (Blueprint $table) {
            $table->string('satisfied_by')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('design_criteria', function (Blueprint $table) {
            $table->dropColumn('satisfied_by');
        });

        Schema::table('checklist_items', function (Blueprint $table) {
            $table->dropColumn('satisfied_by');
        });
    }
};
