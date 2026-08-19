<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\PrototypeIteration\Domain\Enums\PrototypeArtifactType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prototype_artifacts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('prototype_version_id')
                ->constrained('prototype_versions')
                ->cascadeOnDelete();

            $table->string('name');

            /**
             * Coarse on purpose. This is not the mime type — that lives in the
             * metadata below, where a machine can read it. This is the answer
             * to "what am I looking at in this list?", and a list that
             * distinguishes `image/png` from `image/jpeg` answers that worse
             * than one that says "image".
             */
            $table->string('type')->default(PrototypeArtifactType::Other->value);

            /**
             * Where the file actually is, expressed as a path on the
             * application's configured disk rather than as a URL.
             *
             * A path rather than a URL because the disk is a deployment
             * choice: the same row has to keep working when local storage
             * becomes object storage, and a stored URL would bake today's
             * host into every historical artifact. Nothing serves this
             * directly to a browser — the download route asks the storage
             * adapter for a response, which is what keeps the file behind the
             * same authorization the artifact is.
             *
             * The file contents are emphatically not here. A print-ready card
             * sheet is tens of megabytes and PostgreSQL is the wrong place for
             * it; the row is the record, the disk holds the bytes.
             */
            $table->string('storage_reference', 2048);

            /**
             * What was known about the file when it arrived: size, mime type,
             * original filename.
             *
             * JSON rather than columns because none of it is queried and all of
             * it is advisory. Every value here came from the client, so it
             * describes the upload rather than proving anything about it — the
             * size is shown in a list, the original name is offered on
             * download, and neither is trusted for a decision.
             */
            $table->json('metadata')->nullable();

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            /**
             * Serves the artifact list on a prototype version, which is read
             * in upload order.
             */
            $table->index(['prototype_version_id', 'created_at']);
            $table->index(['prototype_version_id', 'type']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prototype_artifacts');
    }
};
