<?php

namespace Modules\GameDesign\Domain\Exceptions;

/**
 * Raised when a game claims a player count it cannot mean.
 *
 * Reported against `player_count_min` rather than against the range, because a
 * form has two inputs and a message with nowhere to sit is a message nobody
 * reads. The lower bound is the one to draw attention to: it is where the
 * mistake usually is.
 */
final class InvalidPlayerCount extends GameRuleViolation
{
    public static function belowMinimum(int $minimum): self
    {
        return new self(__('A game has to be playable by at least :count player.', ['count' => $minimum]));
    }

    public static function aboveMaximum(int $maximum): self
    {
        return new self(__('A player count above :count is more of a party than a game.', ['count' => $maximum]));
    }

    /**
     * Raised when the range runs backwards.
     *
     * Worth its own message rather than folding into the others: "4 to 2
     * players" is almost always two fields filled in the wrong order, and
     * saying so is more useful than restating the bounds.
     */
    public static function outOfOrder(int $min, int $max): self
    {
        return new self(__(
            'A game for :min to :max players is the wrong way round.',
            ['min' => $min, 'max' => $max],
        ));
    }

    public function field(): string
    {
        return 'player_count_min';
    }
}
