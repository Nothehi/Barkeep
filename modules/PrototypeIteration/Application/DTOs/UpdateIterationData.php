<?php

namespace Modules\PrototypeIteration\Application\DTOs;

/**
 * The validated input for changing an iteration's plan.
 *
 * The plan is what the cycle set out to do, and it stays editable only while the
 * cycle is open — the guard enforces that, not this DTO. What this shape decides
 * is which parts of the plan are editable at all.
 *
 * The two version ids are here and the outcome is not, which is the split worth
 * explaining. A designer who picked the wrong prototype version when planning
 * should be able to fix it while the cycle is still open; a designer who has
 * completed the cycle cannot, because by then the version is what the changes and
 * decisions were made against. The outcome, by contrast, is never a field —
 * completing an iteration is an action with its own endpoint, its own required
 * arguments and its own event.
 */
final readonly class UpdateIterationData
{
    public function __construct(
        public ?string $gameVersionId = null,
        public ?string $prototypeVersionId = null,
        public ?string $title = null,
        public ?string $objective = null,
        public ?string $hypothesis = null,
        public bool $changesHypothesis = false,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            gameVersionId: IterationInput::identifier($input, 'game_version_id'),
            prototypeVersionId: IterationInput::identifier($input, 'prototype_version_id'),
            title: array_key_exists('title', $input) ? IterationInput::requiredText($input, 'title') : null,
            objective: array_key_exists('objective', $input) ? IterationInput::requiredText($input, 'objective') : null,
            hypothesis: IterationInput::text($input, 'hypothesis'),
            changesHypothesis: array_key_exists('hypothesis', $input),
        );
    }

    /**
     * Determine whether the update would change anything at all.
     */
    public function isEmpty(): bool
    {
        return $this->gameVersionId === null
            && $this->prototypeVersionId === null
            && $this->title === null
            && $this->objective === null
            && ! $this->changesHypothesis;
    }
}
