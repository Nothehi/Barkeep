<?php

namespace Modules\GameRules\Domain\Enums;

use Modules\GameRules\Domain\Enums\Contracts\Described;

/**
 * What kind of thing has to be true before a rule or action applies.
 *
 * A requirement is prose with a category, not an expression. Section 17 of the
 * brief is explicit that this module does not implement a scripting language,
 * and the reason is worth keeping in front of whoever extends this: the moment a
 * requirement becomes evaluable, something has to evaluate it, and this module
 * would stop being a description of a board game and start being a half-finished
 * engine for one. Execution belongs to a future `GameRuntime`.
 *
 * {@see Resource} is the case that reaches across a boundary. A build action
 * requiring five wood is a fact this module records the *shape* of; the five and
 * the wood belong to GameEconomy, and are read through an integration reference
 * rather than copied.
 */
enum RequirementType: string implements Described
{
    case Resource = 'resource';
    case PlayerState = 'player_state';
    case GameState = 'game_state';
    case Phase = 'phase';
    case Turn = 'turn';
    case Position = 'position';
    case Ownership = 'ownership';
    case Card = 'card';
    case Custom = 'custom';

    /**
     * The type a requirement falls under when nobody chose one.
     */
    public static function default(): self
    {
        return self::Custom;
    }

    /**
     * Determine whether this requirement is about the economy.
     *
     * The one case that can carry an integration reference to a GameEconomy
     * record, which is why the interface offers a resource picker for it and
     * plain prose for the rest.
     */
    public function isEconomic(): bool
    {
        return $this === self::Resource;
    }

    /**
     * A human readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Resource => __('Resource'),
            self::PlayerState => __('Player state'),
            self::GameState => __('Game state'),
            self::Phase => __('Phase'),
            self::Turn => __('Turn'),
            self::Position => __('Position'),
            self::Ownership => __('Ownership'),
            self::Card => __('Card'),
            self::Custom => __('Custom'),
        };
    }

    /**
     * What has to be true for a requirement of this kind.
     */
    public function description(): string
    {
        return match ($this) {
            self::Resource => __('The player holds enough of something. The amount lives in the economy.'),
            self::PlayerState => __('Something about the player: alive, unblocked, not exhausted.'),
            self::GameState => __('Something about the board or the round as a whole.'),
            self::Phase => __('Play is in a particular phase.'),
            self::Turn => __('It is the player\'s turn, or a particular turn number.'),
            self::Position => __('A piece is somewhere in particular.'),
            self::Ownership => __('The player owns or controls something.'),
            self::Card => __('A card is in hand, in play, or has been revealed.'),
            self::Custom => __('Anything else, written out in words.'),
        };
    }
}
