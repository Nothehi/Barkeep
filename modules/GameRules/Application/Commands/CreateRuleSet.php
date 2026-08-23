<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\DTOs\RuleSetData;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\RuleSetCreated;
use Modules\GameRules\Domain\Exceptions\RuleNameIsTaken;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleSetRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Start writing a game's rules down.
 *
 * The set is created empty — no rules, no phases, no actions — and that is how
 * designers work. "We need to write the rules for v4" comes before anybody has
 * decided what the first phase is, and a create form that asked for one would be
 * asking somebody to have finished before they had started.
 *
 * The validator will report the empty set as having no rules, no phases and no
 * way to win, which is true and is exactly the checklist somebody needs.
 */
final class CreateRuleSet
{
    public function __construct(
        private readonly RuleSetRepository $ruleSets,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, GameVersion $version, RuleSetData $data): RuleSet
    {
        $game = $version->game;

        if ($game !== null) {
            $this->guard->ensureGameAcceptsRuleWork($game);
        }

        $name = $data->name ?? '';

        if ($this->ruleSets->versionHasRuleSetNamed($version, $name)) {
            throw RuleNameIsTaken::forRuleSet($name);
        }

        $ruleSet = new RuleSet;

        $ruleSet->fill([
            'name' => $name,
            'description' => $data->description,
        ]);

        $ruleSet->game_version_id = $version->getKey();
        $ruleSet->created_by = $actor->getKey();

        $ruleSet->save();

        $ruleSet->setRelation('version', $version);
        $ruleSet->setRelation('creator', $actor);

        event(new RuleSetCreated(
            ruleSetId: $ruleSet->getKey(),
            gameVersionId: $version->getKey(),
            createdBy: $actor->getKey(),
        ));

        return $ruleSet;
    }
}
