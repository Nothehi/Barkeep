<?php

namespace Modules\Playtesting\Application\DTOs;

use Carbon\CarbonImmutable;

/**
 * The validated input required to plan a playtest.
 *
 * The game is absent, and that absence is a rule: it comes from the resolved
 * route binding rather than from the request body, so a caller cannot plan a
 * playtest against a game they merely named. The version id *is* here — it has
 * to be, there is no route segment for it — which is why it is proved to
 * belong to that game before anything is written.
 *
 * There is no status either. Every playtest starts planned.
 */
final readonly class CreatePlaytestData
{
    public function __construct(
        public string $gameVersionId,
        public string $title,
        public string $objective,
        public ?string $hypothesis = null,
        public ?CarbonImmutable $plannedAt = null,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            gameVersionId: (string) $input['game_version_id'],
            title: trim((string) $input['title']),
            objective: trim((string) $input['objective']),
            hypothesis: PlaytestInput::text($input, 'hypothesis'),
            plannedAt: PlaytestInput::timestamp($input, 'planned_at'),
        );
    }
}
