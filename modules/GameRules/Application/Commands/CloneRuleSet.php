<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\CloneRuleSetData;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\RuleSetCloned;
use Modules\GameRules\Domain\Exceptions\RuleNameIsTaken;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleSetRepository;
use Modules\GameRules\Infrastructure\Persistence\RuleSetCloner;
use Modules\Identity\Domain\Models\User;

/**
 * Copy a rule system into a fresh draft.
 *
 * The module's answer to "I want to change the rules that are in play", and the
 * operation the whole of section 55 rests on. An active rule set refuses every
 * edit, because the rules are what a session was played under; the way forward is
 * to clone, change the copy, and activate it.
 *
 * That only works if it is one press. A form demanding a name would be a small
 * tax on the operation the module most wants people to reach for, so the name is
 * optional and the repository picks one the version does not already use —
 * "Combat rules (copy)", then "(copy 2)".
 *
 * Cloning an *archived* set is allowed, and deliberately so: "start again from
 * what we shipped" is a reason to copy something, not a reason to refuse. Nothing
 * about the source changes, so its status has nothing to protect.
 *
 * The copy is complete and independent — every rule, phase, transition, action,
 * requirement, condition, group, effect, trigger, outcome and reference, with new
 * ids and the relationships between them preserved. See {@see RuleSetCloner} for
 * how the id rewriting works, and the isolation tests for the guarantee that
 * changing the clone cannot touch the original.
 */
final class CloneRuleSet
{
    public function __construct(
        private readonly RuleSetRepository $ruleSets,
        private readonly RuleSetCloner $cloner,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleSet $source, CloneRuleSetData $data): RuleSet
    {
        $this->guard->ensureRuleSetCanBeCloned($source);

        $version = $source->version;
        $name = $data->name;

        if ($version !== null) {
            if ($name === null) {
                $name = $this->ruleSets->availableCloneName($version, $source->name);
            } elseif ($this->ruleSets->versionHasRuleSetNamed($version, $name)) {
                throw RuleNameIsTaken::forRuleSet($name);
            }
        }

        $clone = $this->cloner->clone($source, $actor, $name ?? $source->name, $data->description);

        event(new RuleSetCloned(
            ruleSetId: $clone->getKey(),
            sourceRuleSetId: $source->getKey(),
            gameVersionId: $clone->game_version_id,
            recordsCopied: $this->cloner->recordsCopied(),
            clonedBy: $actor->getKey(),
        ));

        return $clone;
    }
}
