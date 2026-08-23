<?php

namespace Modules\GameRules\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Put a rule set's mechanics into the order the designer dragged them into.
 *
 * Takes the whole ordered list rather than one mechanic and an index, for the
 * same reason every reorder in this module does: that is the shape a drag
 * produces, and it is the only shape that cannot go half-wrong.
 *
 * Ids the rule set does not own are ignored rather than raising. A mechanics list
 * is presentational — a bad id here reorders nothing and is not worth a page of
 * error — which is the one place this module is looser than elsewhere, and it is
 * deliberate.
 */
final class ReorderMechanics
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleWorkGuard $guard,
    ) {}

    /**
     * @param  list<string>  $orderedMechanicIds
     */
    public function handle(User $actor, RuleSet $ruleSet, array $orderedMechanicIds): void
    {
        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        $mechanics = $this->structure->mechanicsOf($ruleSet)->keyBy('id');

        DB::transaction(function () use ($mechanics, $orderedMechanicIds): void {
            $position = 0;

            foreach ($orderedMechanicIds as $mechanicId) {
                $mechanic = $mechanics->get($mechanicId);

                if ($mechanic === null) {
                    continue;
                }

                $mechanic->position = $position++;
                $mechanic->save();
            }
        });
    }
}
