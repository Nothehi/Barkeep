<?php

namespace Modules\GameRules\Application\Queries;

use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\ValueObjects\RuleGraph;
use Modules\GameRules\Infrastructure\Analysis\RuleGraphBuilder;

/**
 * The flow of a game, drawn from its phases and transitions.
 *
 * Read-only. The phase designer edits phases and transitions; this is what those
 * are when drawn, which is why it returns labels rather than models and why there
 * is no command beside it.
 */
final class GetRuleGraph
{
    public function __construct(private readonly RuleGraphBuilder $graphs) {}

    public function handle(RuleSet $ruleSet): RuleGraph
    {
        return $this->graphs->build($ruleSet);
    }
}
