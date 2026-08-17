<?php

namespace Modules\GameDesign\Domain\Enums;

/**
 * How much a game asks of the table.
 *
 * "Weight", in the language designers actually use. Five steps, because the
 * published scales are numeric averages of strangers' opinions and a designer
 * setting an intention needs a word rather than a decimal — "1.87" is a fact
 * about other people's games, not a decision about yours.
 *
 * Set as an intention rather than measured. The framework asks whether the
 * intended weight matches the intended audience, which is a question that only
 * means anything if both are things the designer chose. A complexity derived
 * from rules length or component count would be an observation, and observations
 * cannot be wrong in the way an intention usefully can.
 */
enum Complexity: string
{
    case Party = 'party';
    case Family = 'family';
    case Gateway = 'gateway';
    case Hobby = 'hobby';
    case Heavy = 'heavy';

    /**
     * The complexity's place on the scale, counting from one.
     */
    public function position(): int
    {
        return match ($this) {
            self::Party => 1,
            self::Family => 2,
            self::Gateway => 3,
            self::Hobby => 4,
            self::Heavy => 5,
        };
    }

    /**
     * How many steps the scale has.
     */
    public static function count(): int
    {
        return count(self::cases());
    }

    /**
     * A human readable label for the complexity.
     */
    public function label(): string
    {
        return match ($this) {
            self::Party => __('Party'),
            self::Family => __('Family'),
            self::Gateway => __('Gateway'),
            self::Hobby => __('Hobby'),
            self::Heavy => __('Heavy'),
        };
    }

    /**
     * What choosing this weight is claiming.
     *
     * Written in terms of what the table has to do, because that is what the
     * designer is deciding. A description like "medium complexity" would send
     * them back to guessing what the scale means.
     */
    public function description(): string
    {
        return match ($this) {
            self::Party => __('Explained in a minute, played while talking about something else.'),
            self::Family => __('Rules a mixed table picks up in one round, with real decisions in it.'),
            self::Gateway => __('A short teach and one clear system. The game somebody is shown first.'),
            self::Hobby => __('Several interacting systems. Assumes players who choose to play games.'),
            self::Heavy => __('A long teach and a long game. Played by people who came for exactly this.'),
        };
    }
}
