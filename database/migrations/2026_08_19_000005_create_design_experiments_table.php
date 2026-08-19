<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\PrototypeIteration\Domain\Enums\ExperimentStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('design_experiments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('iteration_id')->constrained()->cascadeOnDelete();

            $table->string('title');

            /**
             * The four things written down *before* the experiment runs.
             *
             * The order matters and the split is not bureaucracy. A question
             * names what is unknown; a hypothesis commits to an answer; a
             * method says how the answer will be looked for; an expected result
             * is what would count as confirmation. An experiment missing the
             * last two is an intention, and one missing the first is a session
             * with no question — which is what the module is trying to help a
             * designer stop doing.
             *
             * Only the question is required. Plenty of useful experiments are
             * "let us just watch and see", and demanding a hypothesis for
             * those would produce invented ones.
             */
            $table->text('question');
            $table->text('hypothesis')->nullable();
            $table->text('method')->nullable();
            $table->text('expected_result')->nullable();

            /**
             * The two things written down *after* it runs.
             *
             * Separate columns rather than one write-up, because the
             * distinction between what happened and what it means is the
             * distinction the whole module is built on. "Sessions ran twenty
             * minutes longer" is a result; "unlimited actions improve strategy
             * but harm pacing" is a conclusion, and only the second is an
             * argument. Both are nullable until the experiment completes, and
             * `CompleteExperiment` requires the result — an experiment with no
             * result taught nobody anything.
             */
            $table->text('actual_result')->nullable();
            $table->text('conclusion')->nullable();

            $table->string('status')->default(ExperimentStatus::Planned->value);

            /**
             * When the experiment actually ran. Set by the lifecycle commands
             * rather than supplied, so they record what happened.
             */
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            $table->index(['iteration_id', 'created_at']);
            $table->index(['iteration_id', 'status']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_experiments');
    }
};
