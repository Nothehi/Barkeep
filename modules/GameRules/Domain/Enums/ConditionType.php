<?php

namespace Modules\GameRules\Domain\Enums;

use Modules\GameRules\Domain\Enums\Contracts\Described;

/**
 * What a declarative condition is about.
 *
 * A condition is a named, reusable three-part statement — subject, operator,
 * value — that phase transitions, victory conditions and end conditions point
 * at. Naming it is the whole point: "all players have passed" is written once
 * and referenced from the four places that care, rather than retyped into each
 * of them with slightly different words.
 *
 * The operator lives on {@see ConditionOperator} and the value is a string, so
 * the three parts together are readable by a person and comparable by the
 * validator without anything having to interpret them.
 */
enum ConditionType: string implements Described
{
    case Resource = 'resource';
    case Counter = 'counter';
    case PlayerCount = 'player_count';
    case Phase = 'phase';
    case Turn = 'turn';
    case Score = 'score';
    case Ownership = 'ownership';
    case Card = 'card';
    case GameState = 'game_state';
    case Custom = 'custom';

    /**
     * The type a condition falls under when nobody chose one.
     */
    public static function default(): self
    {
        return self::Custom;
    }

    /**
     * A human readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Resource => __('Resource'),
            self::Counter => __('Counter'),
            self::PlayerCount => __('Player count'),
            self::Phase => __('Phase'),
            self::Turn => __('Turn'),
            self::Score => __('Score'),
            self::Ownership => __('Ownership'),
            self::Card => __('Card'),
            self::GameState => __('Game state'),
            self::Custom => __('Custom'),
        };
    }

    /**
     * What a condition of this kind measures.
     */
    public function description(): string
    {
        return match ($this) {
            self::Resource => __('How much of something a player holds.'),
            self::Counter => __('A tracked number: rounds elapsed, tokens left, damage taken.'),
            self::PlayerCount => __('How many players are in the game, or still in it.'),
            self::Phase => __('Which phase play is currently in.'),
            self::Turn => __('Whose turn it is, or how many turns have passed.'),
            self::Score => __('A player\'s score, or the leader\'s.'),
            self::Ownership => __('What a player owns or controls.'),
            self::Card => __('What is in a hand, a deck or a discard pile.'),
            self::GameState => __('A flag about the game as a whole.'),
            self::Custom => __('Anything else, written out in words.'),
        };
    }
}
