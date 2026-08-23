<?php

namespace Modules\GameRules\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameRules\Domain\Models\GameEndCondition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * The things that bring this game to a close, in the order they are checked.
 *
 * Not the same question as how it is won. "The deck runs out" stops the game and
 * says nothing about who came first.
 */
final class GetGameEndConditions
{
    public function __construct(private readonly RuleStructureRepository $structure) {}

    /**
     * @return Collection<int, GameEndCondition>
     */
    public function handle(RuleSet $ruleSet): Collection
    {
        return $this->structure->endConditionsOf($ruleSet);
    }
}
