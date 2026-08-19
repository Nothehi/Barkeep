<?php

namespace Modules\PrototypeIteration\Application\DTOs;

/**
 * The validated input for proposing or rewording a design decision.
 *
 * All three fields are required, which makes this the strictest create shape in
 * the module. That is not tidiness: a decision is the sentence somebody will read
 * in a year to find out why the game is the way it is, and each of the three does
 * a distinct job at a distinct reading distance.
 *
 * The title is scanned in a list — "Reaction phase". The decision is the sentence
 * itself — "Remove the reaction phase permanently". The reason is the argument —
 * "reaction windows created excessive downtime and players stopped using them
 * after the second round". Drop the third and the record becomes an instruction
 * nobody can re-examine when the situation changes, which is the only thing a
 * design history is actually for.
 *
 * There is no status here. A decision starts proposed, and settling one is an
 * action with its own endpoint, its own attribution and its own event — not a
 * field a create request gets to set.
 */
final readonly class DesignDecisionData
{
    public function __construct(
        public string $title,
        public string $decision,
        public string $reason,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            title: IterationInput::requiredText($input, 'title'),
            decision: IterationInput::requiredText($input, 'decision'),
            reason: IterationInput::requiredText($input, 'reason'),
        );
    }
}
