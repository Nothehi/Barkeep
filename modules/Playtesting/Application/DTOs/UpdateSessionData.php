<?php

namespace Modules\Playtesting\Application\DTOs;

use Carbon\CarbonImmutable;

/**
 * The validated input for changing a session that has not ended.
 *
 * Notes are the field this exists for. They accumulate during a session as
 * somebody types up what is happening around the observations, so they are
 * saved repeatedly rather than once at the end.
 */
final readonly class UpdateSessionData
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
