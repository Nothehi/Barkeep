<?php

namespace Modules\GameDesign\Domain\Exceptions;

/**
 * Raised when a game claims a playing time it cannot mean.
 */
final class InvalidPlayTime extends GameRuleViolation
{
    public static function belowMinimum(int $minutes): self
    {
        return new self(__('A playing time of at least :count minute is needed.', ['count' => $minutes]));
    }

    public static function aboveMaximum(int $minutes): self
    {
        return new self(__('A playing time longer than :count minutes is a campaign rather than a game.', ['count' => $minutes]));
    }

    public static function outOfOrder(int $min, int $max): self
    {
        return new self(__(
            'A playing time of :min to :max minutes is the wrong way round.',
            ['min' => $min, 'max' => $max],
        ));
    }

    public function field(): string
    {
        return 'play_time_min';
    }
}
