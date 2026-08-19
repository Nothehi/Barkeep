<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\DTOs\DesignExperimentData;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;

/**
 * Refine an experiment's design before it is answered.
 *
 * The guard here is the one that protects the module's most subtle invariant. A completed
 * experiment refuses every field on this command — question, hypothesis, method,
 * expected result — because editing any of them after the result is known is how a
 * prediction becomes retroactively correct.
 *
 * That failure is almost always honest. Nobody sets out to falsify a hypothesis; somebody
 * reads back "we expected downtime to fall" next to "downtime rose slightly", decides the
 * prediction was sloppily worded, and tidies it. The tidied version then reads as a
 * successful prediction forever. Refusing the edit is what keeps an experiment's record
 * worth something, and it is why the after half is written by its own command with its own
 * name.
 *
 * A running experiment stays editable, which is deliberate: a method being corrected
 * mid-session — "we ended up doing four players, not three" — is a description getting
 * more accurate, not a prediction being adjusted.
 */
final class UpdateDesignExperiment
{
    public function __construct(private readonly DesignWorkGuard $guard) {}

    public function handle(User $actor, DesignExperiment $experiment, DesignExperimentData $data): DesignExperiment
    {
        $this->guard->ensureExperimentIsModifiable($experiment);

        $experiment->fill([
            'title' => $data->title,
            'question' => $data->question,
            'hypothesis' => $data->hypothesis,
            'method' => $data->method,
            'expected_result' => $data->expectedResult,
        ]);

        $experiment->save();

        return $experiment;
    }
}
