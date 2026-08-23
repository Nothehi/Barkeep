<?php

namespace Modules\GameRules\Domain\Enums;

use Modules\GameRules\Domain\Enums\Contracts\Described;

/**
 * What sort of thing a player action is.
 *
 * A vocabulary for the actions a rule system offers, kept short for the same
 * reason {@see RuleType} is. Nothing behaves differently because of it.
 *
 * Worth restating what one of these is *not*. A `RuleAction` answers "what can
 * the player do?"; GameEconomy's `EconomyAction` answers "what does doing it
 * cost and pay?". They are separate records in separate bounded contexts, joined
 * — when a studio wants them joined — by a reference this module stores as a
 * plain handle rather than as a foreign key.
 */
enum RuleActionType: string implements Described
{
    case Basic = 'basic';
    case Movement = 'movement';
    case Combat = 'combat';
    case Resource = 'resource';
    case Card = 'card';
    case Build = 'build';
    case Trade = 'trade';
    case Special = 'special';
    case Free = 'free';
    case Reaction = 'reaction';
    case Pass = 'pass';

    /**
     * The type an action falls under when nobody chose one.
     */
    public static function default(): self
    {
        return self::Basic;
    }

    /**
     * A human readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Basic => __('Basic'),
            self::Movement => __('Movement'),
            self::Combat => __('Combat'),
            self::Resource => __('Resource'),
            self::Card => __('Card'),
            self::Build => __('Build'),
            self::Trade => __('Trade'),
            self::Special => __('Special'),
            self::Free => __('Free'),
            self::Reaction => __('Reaction'),
            self::Pass => __('Pass'),
        };
    }

    /**
     * The kind of action that belongs under this heading.
     */
    public function description(): string
    {
        return match ($this) {
            self::Basic => __('An ordinary action, taken on your turn like any other.'),
            self::Movement => __('Moving a piece, a worker or yourself.'),
            self::Combat => __('Attacking, defending or resolving a fight.'),
            self::Resource => __('Gathering, converting or spending what you hold.'),
            self::Card => __('Drawing, playing, discarding or buying a card.'),
            self::Build => __('Placing something permanent on the board.'),
            self::Trade => __('Exchanging with another player or with the bank.'),
            self::Special => __('Unlocked by a role, a card or a board state.'),
            self::Free => __('Taken without spending the turn on it.'),
            self::Reaction => __('Taken out of turn, in response to something else.'),
            self::Pass => __('Declining to act, which is itself a decision.'),
        };
    }
}
