<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A frozen copy of an entire balance configuration.
     *
     * This is the one table in the module that stores denormalised data on
     * purpose, and the reason is the thing snapshots exist for. "What did the
     * economy look like when we ran the convention playtest?" cannot be answered
     * by reading the live tables — they have moved on, which is why somebody is
     * asking. A snapshot is the answer, and it has to survive every subsequent
     * edit, including edits that delete the rows it describes.
     *
     * So the payload is a copy rather than a set of references, and there is no
     * `updated_at`: nothing writes to a snapshot after it is created. The
     * immutability is enforced by there being no command and no route that
     * changes one, and held by a test.
     */
    public function up(): void
    {
        Schema::create('balance_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('balance_profile_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            /**
             * The whole configuration as it stood: resources, flows, actions,
             * costs, rewards, effects and variables.
             *
             * JSON here and normalised rows everywhere else is not an
             * inconsistency. The live configuration is queried, filtered and
             * summed; a snapshot is only ever read whole and diffed against
             * another snapshot, and giving it thirteen shadow tables would mean
             * every future schema change had to be applied to history as well as
             * to the present — which is exactly what "history is immutable"
             * forbids.
             */
            $table->json('snapshot_data');

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            /**
             * Created, never updated. The absence of `updated_at` is the
             * immutability rule written into the schema.
             */
            $table->timestamp('created_at')->nullable();

            $table->index(['balance_profile_id', 'created_at']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_snapshots');
    }
};
