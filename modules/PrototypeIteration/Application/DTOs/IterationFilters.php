<?php

namespace Modules\PrototypeIteration\Application\DTOs;

use Modules\PrototypeIteration\Domain\Enums\IterationOutcome;
use Modules\PrototypeIteration\Domain\Enums\IterationStatus;

/**
 * What an iterations list has been narrowed to.
 *
 * Outcome is a filter as well as status, and it is the one designers actually
 * use: "show me everything that failed" is how somebody finds the thread of a
 * problem that has been resisting them for months. It only ever matches
 * completed iterations, since nothing else has an outcome — which is why the two
 * are separate filters rather than one combined state.
 *
 * A value that names nothing is treated as no filter rather than as an error.
 */
final readonly class IterationFilters
{
    public function __construct(
        public ?string $search = null,
        public ?IterationStatus $status = null,
        public ?IterationOutcome $outcome = null,
        public ?string $prototypeId = null,
    ) {}

    /**
     * The unfiltered list.
     */
    public static function none(): self
    {
        return new self;
    }

    /**
     * Build the filters from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $status = IterationInput::identifier($input, 'status');
        $outcome = IterationInput::identifier($input, 'outcome');

        return new self(
            search: IterationInput::text($input, 'search'),
            status: $status === null ? null : IterationStatus::tryFrom($status),
            outcome: $outcome === null ? null : IterationOutcome::tryFrom($outcome),
            prototypeId: IterationInput::identifier($input, 'prototype'),
        );
    }

    /**
     * Determine whether anything is being filtered at all.
     */
    public function areEmpty(): bool
    {
        return $this->search === null
            && $this->status === null
            && $this->outcome === null
            && $this->prototypeId === null;
    }
}
