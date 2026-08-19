<?php

namespace Modules\PrototypeIteration\Application\DTOs;

use Modules\PrototypeIteration\Domain\Enums\IterationOutcome;

/**
 * What has to be said to close a turn of the design loop.
 *
 * Both fields are required and neither is nullable, which is the only DTO in the
 * module shaped that way. That is the requirement from section 47 expressed in a
 * type: an iteration with no outcome is a period of time, and the outcome plus
 * the summary are what make it a turn of a loop the next turn can be built on.
 *
 * The two do different jobs and that is why both are needed. The outcome is the
 * index — one of four words, scannable down the side of a year of history. The
 * summary is the account: "combat became more interesting, but downtime remains
 * too high" is the sentence that tells the next designer where to start.
 */
final readonly class CompleteIterationData
{
    public function __construct(
        public IterationOutcome $outcome,
        public string $summary,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * The enum is resolved with `from` rather than `tryFrom`: an outcome that
     * names nothing must not silently become a default, because the default
     * would then be recorded as the studio's own judgement.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            outcome: IterationOutcome::from((string) $input['outcome']),
            summary: IterationInput::requiredText($input, 'summary'),
        );
    }
}
