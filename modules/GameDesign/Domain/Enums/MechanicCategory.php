<?php

namespace Modules\GameDesign\Domain\Enums;

/**
 * The part of a design a mechanic is about.
 *
 * Grouping exists so that a list of a hundred terms can be read at all. A
 * designer tagging their game scans for "how do turns work" and then for "what
 * do players do to each other"; a flat alphabetical list makes them read the
 * whole vocabulary to find out it contains nothing they need.
 *
 * Deliberately coarse, and deliberately not a hierarchy. Real mechanics belong
 * to several of these at once — an auction is economy and interaction both —
 * and a taxonomy that tried to be true would need multiple parents, which is a
 * lot of machinery for a heading on a picker. Each mechanic is filed under the
 * one that best describes what it changes about play, and the description is
 * where the nuance goes.
 */
enum MechanicCategory: string
{
    case TurnStructure = 'turn_structure';
    case Economy = 'economy';
    case Space = 'space';
    case Cards = 'cards';
    case Interaction = 'interaction';
    case Uncertainty = 'uncertainty';
    case Scoring = 'scoring';

    /**
     * The category a mechanic is filed under when nobody has said.
     */
    public static function default(): self
    {
        return self::TurnStructure;
    }

    /**
     * The category's place in the reading order, counting from one.
     *
     * Ordered the way a design is built rather than alphabetically: how a turn
     * works, what it costs, where it happens, and only then how the game is
     * won. A picker that opened on "Scoring" would be asking the last question
     * first.
     */
    public function position(): int
    {
        return match ($this) {
            self::TurnStructure => 1,
            self::Economy => 2,
            self::Space => 3,
            self::Cards => 4,
            self::Interaction => 5,
            self::Uncertainty => 6,
            self::Scoring => 7,
        };
    }

    /**
     * A human readable label for the category.
     */
    public function label(): string
    {
        return match ($this) {
            self::TurnStructure => __('Turn structure'),
            self::Economy => __('Economy and resources'),
            self::Space => __('Space and movement'),
            self::Cards => __('Cards and hands'),
            self::Interaction => __('Player interaction'),
            self::Uncertainty => __('Uncertainty'),
            self::Scoring => __('Scoring and endgame'),
        };
    }

    /**
     * What the category covers.
     */
    public function description(): string
    {
        return match ($this) {
            self::TurnStructure => __('How play passes between people, and what a turn is made of.'),
            self::Economy => __('What players gather, spend and convert.'),
            self::Space => __('Boards, regions, routes and where pieces sit.'),
            self::Cards => __('Hands, decks, drafting and what a card does.'),
            self::Interaction => __('What players can do to and with each other.'),
            self::Uncertainty => __('Where randomness and hidden information enter.'),
            self::Scoring => __('How the game is won, and when it stops.'),
        };
    }
}
