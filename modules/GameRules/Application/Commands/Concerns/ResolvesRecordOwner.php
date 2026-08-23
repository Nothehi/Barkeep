<?php

namespace Modules\GameRules\Application\Commands\Concerns;

use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Domain\Exceptions\AmbiguousOwner;
use Modules\GameRules\Domain\Models\RuleSet;

/**
 * The "exactly one owner" rule, in one place.
 *
 * Requirements and effects both hang off either a rule or an action, and the
 * schema cannot portably say "exactly one of these two columns". So the commands
 * do, and they do it here rather than four times.
 *
 * Both mistakes are refused and neither is harmless. A record owned by a rule
 * *and* an action happens twice and is edited once; one owned by neither never
 * happens at all, which is what the validator reports as an error for the rows
 * that predate this check.
 *
 * Both ids are resolved through the rule set on the way past, so an owner from
 * another rule system is never found rather than found and rejected.
 */
trait ResolvesRecordOwner
{
    /**
     * Resolve the single owner of a requirement or an effect.
     *
     * @return array{0: string|null, 1: string|null} the rule id and the action id
     *
     * @throws AmbiguousOwner
     */
    protected function resolveOwner(
        RuleCatalogue $catalogue,
        RuleSet $ruleSet,
        ?string $ruleId,
        ?string $actionId,
        bool $forEffect,
    ): array {
        if ($ruleId !== null && $actionId !== null) {
            throw $forEffect ? AmbiguousOwner::forEffect() : AmbiguousOwner::forRequirement();
        }

        if ($ruleId === null && $actionId === null) {
            throw $forEffect
                ? AmbiguousOwner::withoutOwnerForEffect()
                : AmbiguousOwner::withoutOwnerForRequirement();
        }

        if ($ruleId !== null) {
            return [$catalogue->ruleOf($ruleSet, $ruleId)->getKey(), null];
        }

        return [null, $catalogue->actionOf($ruleSet, (string) $actionId)->getKey()];
    }
}
