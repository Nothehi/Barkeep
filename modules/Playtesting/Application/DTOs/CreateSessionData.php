<?php

namespace Modules\Playtesting\Application\DTOs;

use Carbon\CarbonImmutable;

/**
 * The validated input required to schedule a sitting of a playtest.
 *
 * Everything is optional. A designer who is about to start a session right now
 * — which is the common case — should be able to create one and press start
 * without filling in a form about where they are sitting.
 *
 * There are no real timestamps here either. `started_at` and `ended_at` are
 * written by the commands that start and end a session, from the clock, so
 * they record what happened rather than what somebody typed.
 */
final readonly class CreateSessionData
{
    public function __construct(
        public ?CarbonImmutable $plannedAt = null,
        public ?string $location = null,
        public ?string $notes = null,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            plannedAt: PlaytestInput::timestamp($input, 'planned_at'),
            location: PlaytestInput::text($input, 'location'),
            notes: PlaytestInput::text($input, 'notes'),
        );
    }
}
