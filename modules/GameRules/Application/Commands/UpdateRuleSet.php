<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\RuleSetData;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Exceptions\RuleNameIsTaken;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleSetRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Correct a rule set's own title or summary.
 *
 * Permitted on an *active* set, unlike everything inside it. The distinction is
 * section 55 of the brief read carefully: a title is a label on the document,
 * and correcting it does not change what a session was played under. Rewriting
 * one of the rules does, which is why that path is refused and offers a clone
 * instead.
 *
 * The status is not settable from here. Activating and archiving are actions with
 * rules of their own — one of them irreversible — rather than a field value.
 */
final class UpdateRuleSet
{
    public function __construct(
        private readonly RuleSetRepository $ruleSets,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleSet $ruleSet, RuleSetData $data): RuleSet
    {
        $this->guard->ensureRuleSetIsRenamable($ruleSet);

        if ($data->name !== null && $data->name !== $ruleSet->name) {
            $version = $ruleSet->version;

            if ($version !== null && $this->ruleSets->versionHasRuleSetNamed($version, $data->name, $ruleSet->getKey())) {
                throw RuleNameIsTaken::forRuleSet($data->name);
            }

            $ruleSet->name = $data->name;
        }

        if ($data->sent('description')) {
            $ruleSet->description = $data->description;
        }

        $ruleSet->save();

        return $ruleSet;
    }
}
