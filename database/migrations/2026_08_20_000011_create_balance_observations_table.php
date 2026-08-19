<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\GameEconomy\Domain\Enums\ObservationSeverity;
use Modules\GameEconomy\Domain\Enums\ObservationSourceType;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * What the studio noticed about the economy, and where they noticed it.
     *
     * This is deliberately not a copy of a playtest observation. Playtesting
     * records what happened at the table — "the green player never bought a
     * building" — and this records what that means for the numbers: "wood
     * becomes effectively unlimited after round six". One is evidence, the other
     * is the balance interpretation of it, and collapsing them would make it
     * impossible to disagree with an interpretation without editing the
     * evidence.
     *
     * Which is why `source_reference` is a plain string rather than a foreign
     * key. Pointing it at a playtest would put a Playtesting identifier in this
     * module's schema and give it a second copy of another context's records to
     * keep in step.
     */
    public function up(): void
    {
        Schema::create('balance_observations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('balance_profile_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->text('observation');

            $table->string('source_type')->default(ObservationSourceType::Other->value);

            /**
             * Where the evidence came from, in whatever form the source has one:
             * a playtest id, a session date, "the spreadsheet". Unconstrained on
             * purpose — see the note above.
             */
            $table->string('source_reference')->nullable();

            $table->string('severity')->default(ObservationSeverity::Info->value);

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            $table->index(['balance_profile_id', 'created_at']);
            $table->index(['balance_profile_id', 'severity']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_observations');
    }
};
