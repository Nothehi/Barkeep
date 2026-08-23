<?php

namespace Modules\GameRules\Domain\Enums;

use Modules\GameRules\Domain\Enums\Contracts\Described;

/**
 * The family a gameplay mechanism belongs to.
 *
 * A mechanic says *what kind of system exists* — worker placement, deck
 * building, push your luck. This says what family that system is from, so a rule
 * set listing eight of them can be read at a glance.
 *
 * Deliberately not a public taxonomy. Section 11 of the brief is explicit that
 * this is not the beginning of a shared mechanic marketplace: a mechanic here
 * belongs to one rule set and is named in that studio's own words.
 */
enum MechanicCategory: string implements Described
{
    case Action = 'action';
    case Resource = 'resource';
    case Card = 'card';
    case Dice = 'dice';
    case PlayerInteraction = 'player_interaction';
    case Movement = 'movement';
    case Combat = 'combat';
    case Economy = 'economy';
    case Scoring = 'scoring';
    case Information = 'information';
    case Progression = 'progression';
    case Other = 'other';

    /**
     * The category a mechanic falls under when nobody chose one.
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
            self::Action => __('Action'),
            self::Resource => __('Resource'),
            self::Card => __('Card'),
            self::Dice => __('Dice'),
            self::PlayerInteraction => __('Player interaction'),
            self::Movement => __('Movement'),
            self::Combat => __('Combat'),
            self::Economy => __('Economy'),
            self::Scoring => __('Scoring'),
            self::Information => __('Information'),
            self::Progression => __('Progression'),
            self::Other => __('Other'),
        };
    }

    /**
     * The kind of mechanism that belongs under this heading.
     */
    public function description(): string
    {
        return match ($this) {
            self::Action => __('How players choose and take actions: worker placement, action selection.'),
            self::Resource => __('Gathering, converting and managing what players hold.'),
            self::Card => __('Deck building, hand management, drafting.'),
            self::Dice => __('Rolling, re-rolling, pushing your luck.'),
            self::PlayerInteraction => __('Trading, auctions, negotiation, take-that.'),
            self::Movement => __('Getting pieces around a board or a track.'),
            self::Combat => __('Attacking, defending, area control.'),
            self::Economy => __('Markets, prices, investment, income.'),
            self::Scoring => __('Set collection, majorities, end-game bonuses.'),
            self::Information => __('Hidden roles, bluffing, deduction, memory.'),
            self::Progression => __('Tech trees, campaigns, levelling, legacy.'),
            self::Other => __('Anything that does not fit the families above.'),
        };
    }
}
