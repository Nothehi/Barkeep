<?php

namespace Modules\GameRules\Domain\ValueObjects;

/**
 * One arrow in the flow of a game.
 *
 * Carries what guards it as already-worded prose rather than as a condition id,
 * because the graph is read-only and the label is the whole point: an arrow
 * reading "if all players have passed" is the sentence the diagram exists to
 * show. Editing happens in the phase designer, which works with the records.
 */
final readonly class RuleGraphEdge
{
    public function __construct(
        public string $from,
        public string $to,
        public ?string $label = null,
        public ?string $entityId = null,
        public bool $isImplicit = false,
    ) {}

    /**
     * An edge the rule set did not state but the shape of it implies.
     *
     * The arrow from `START` into setup is the main one. Marked so the interface
     * can draw it faintly — a designer should be able to tell what they wrote
     * from what was inferred on their behalf.
     */
    public static function implicit(string $from, string $to, ?string $label = null): self
    {
        return new self(from: $from, to: $to, label: $label, isImplicit: true);
    }
}
