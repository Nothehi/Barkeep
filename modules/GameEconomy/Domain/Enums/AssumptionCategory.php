<?php

namespace Modules\GameEconomy\Domain\Enums;

use Modules\GameEconomy\Domain\Enums\Contracts\Described;

/**
 * What part of the design an assumption is about.
 *
 * Assumptions accumulate faster than anything else in a balance profile — every
 * number somebody argues about produces one — so they need a heading before the
 * list is long enough to need scrolling.
 */
enum AssumptionCategory: string implements Described
{
    case Economy = 'economy';
    case Progression = 'progression';
    case Pacing = 'pacing';
    case PlayerBehaviour = 'player_behaviour';
    case Complexity = 'complexity';
    case Interaction = 'interaction';
    case Other = 'other';

    /**
     * The category an assumption falls into when nobody chose one.
     */
    public static function default(): self
    {
        return self::Other;
    }

    /**
     * A human readable label for the category.
     */
    public function label(): string
    {
        return match ($this) {
            self::Economy => __('Economy'),
            self::Progression => __('Progression'),
            self::Pacing => __('Pacing'),
            self::PlayerBehaviour => __('Player behaviour'),
            self::Complexity => __('Complexity'),
            self::Interaction => __('Interaction'),
            self::Other => __('Other'),
        };
    }

    /**
     * The kind of belief that belongs under this heading.
     */
    public function description(): string
    {
        return match ($this) {
            self::Economy => __('How resources should flow through the game.'),
            self::Progression => __('How fast a player should get more powerful.'),
            self::Pacing => __('How long a turn, a round or the whole game should take.'),
            self::PlayerBehaviour => __('What players will actually try to do.'),
            self::Complexity => __('How much a player should have to hold in their head.'),
            self::Interaction => __('How much players should be able to affect each other.'),
            self::Other => __('Anything that does not fit the categories above.'),
        };
    }
}
