<?php

namespace Modules\Playtesting\Application\DTOs;

use Carbon\CarbonImmutable;
use Modules\Playtesting\Domain\Enums\ObservationCategory;

/**
 * The validated input required to record something noticed at a session.
 *
 * Only the text is required, which is the point. An observation is typed with
 * one hand while the game carries on with the other, and every field that has
 * to be filled in first is a reason the observation does not get recorded at
 * all.
 *
 * `observedAt` stays optional for the same reason it is nullable in the
 * database: half of all observations are written up afterwards from memory,
 * and demanding a moment for those would produce invented timestamps.
 */
final readonly class CreateObservationData
{
    public function __construct(
        public string $content,
        public ObservationCategory $category,
        public ?string $participantId = null,
        public ?CarbonImmutable $observedAt = null,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $category = isset($input['category'])
            ? ObservationCategory::from((string) $input['category'])
            : ObservationCategory::default();

        return new self(
            content: PlaytestInput::requiredText($input, 'content'),
            category: $category,
            participantId: PlaytestInput::identifier($input, 'participant_id'),
            observedAt: PlaytestInput::timestamp($input, 'observed_at'),
        );
    }
}
