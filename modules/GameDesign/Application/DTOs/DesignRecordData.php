<?php

namespace Modules\GameDesign\Application\DTOs;

use Modules\GameDesign\Domain\Enums\Complexity;
use Modules\GameDesign\Domain\Exceptions\InvalidPlayerCount;
use Modules\GameDesign\Domain\Exceptions\InvalidPlayTime;
use Modules\GameDesign\Domain\ValueObjects\PlayerCountRange;
use Modules\GameDesign\Domain\ValueObjects\PlayTimeRange;

/**
 * What has been decided about a game's design, as submitted.
 *
 * The ranges arrive as value objects rather than as four loose integers, so the
 * "max cannot be below min" rule is settled before the command runs and cannot
 * be forgotten by a second caller later.
 *
 * Every field is nullable, and clearing one is a real intention rather than an
 * accident: an update is a replacement, so a field left out of the request is a
 * field the designer has decided they no longer know. That is the same rule the
 * game's own update follows, and it is why the form always sends every field.
 */
final readonly class DesignRecordData
{
    /**
     * @param  list<string>  $mechanicIds  the vocabulary terms this game claims
     */
    public function __construct(
        public ?string $pitch,
        public ?PlayerCountRange $playerCount,
        public ?PlayTimeRange $playTime,
        public ?int $targetAgeMin,
        public ?Complexity $complexity,
        public ?string $audience,
        public ?string $coreAction,
        public ?string $coreCost,
        public ?string $coreReward,
        public ?string $winCondition,
        public ?string $failureCondition,
        public array $mechanicIds,
    ) {}

    /**
     * Build the data from validated request input.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws InvalidPlayerCount
     * @throws InvalidPlayTime
     */
    public static function fromArray(array $input): self
    {
        /** @var list<string> $mechanics */
        $mechanics = array_values(array_unique(array_filter(
            (array) ($input['mechanics'] ?? []),
            fn (mixed $id): bool => is_string($id) && $id !== '',
        )));

        return new self(
            pitch: self::text($input, 'pitch'),
            playerCount: PlayerCountRange::fromNullableInts(
                self::number($input, 'player_count_min'),
                self::number($input, 'player_count_max'),
            ),
            playTime: PlayTimeRange::fromNullableMinutes(
                self::number($input, 'play_time_min'),
                self::number($input, 'play_time_max'),
            ),
            targetAgeMin: self::number($input, 'target_age_min'),
            complexity: self::complexity($input),
            audience: self::text($input, 'audience'),
            coreAction: self::text($input, 'core_action'),
            coreCost: self::text($input, 'core_cost'),
            coreReward: self::text($input, 'core_reward'),
            winCondition: self::text($input, 'win_condition'),
            failureCondition: self::text($input, 'failure_condition'),
            mechanicIds: $mechanics,
        );
    }

    /**
     * Read a prose field, normalising whitespace-only input to nothing.
     *
     * A field containing a space is not an answer, and letting one through would
     * satisfy a framework criterion that asked whether the question had been
     * answered — which is the one thing that must not be possible.
     *
     * @param  array<string, mixed>  $input
     */
    private static function text(array $input, string $key): ?string
    {
        $value = trim((string) ($input[$key] ?? ''));

        return $value === '' ? null : $value;
    }

    /**
     * Read a numeric field, treating an empty box as undecided.
     *
     * @param  array<string, mixed>  $input
     */
    private static function number(array $input, string $key): ?int
    {
        $value = $input[$key] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private static function complexity(array $input): ?Complexity
    {
        $value = $input['complexity'] ?? null;

        if ($value instanceof Complexity) {
            return $value;
        }

        return $value === null || $value === ''
            ? null
            : Complexity::from((string) $value);
    }
}
