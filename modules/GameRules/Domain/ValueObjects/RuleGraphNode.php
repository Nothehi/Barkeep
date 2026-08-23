<?php

namespace Modules\GameRules\Domain\ValueObjects;

use Modules\GameRules\Domain\Enums\RuleEntityType;

/**
 * One box in the flow of a game.
 *
 * Almost every node is a phase. Two are not, and both are synthetic: `START`,
 * which nothing in the database corresponds to, and the outcome checks drawn
 * where a rule set says how it finishes. Giving those an id of null rather than
 * inventing one keeps "a node with an id names a record you can open" true.
 */
final readonly class RuleGraphNode
{
    public function __construct(
        public string $key,
        public RuleEntityType $entityType,
        public ?string $entityId,
        public string $label,
        public ?string $detail = null,
        public bool $isEntry = false,
        public bool $isTerminal = false,
        /** @var list<string> */
        public array $actions = [],
    ) {}

    /**
     * The synthetic node play begins at.
     *
     * Drawn even when no phase is marked as setup, because a graph whose first
     * box is "Action phase" quietly implies the game starts there.
     */
    public static function start(): self
    {
        return new self(
            key: 'start',
            entityType: RuleEntityType::RuleSet,
            entityId: null,
            label: __('Start'),
            isEntry: true,
        );
    }
}
