<?php

namespace Modules\GameEconomy\Domain\Enums;

use Modules\GameEconomy\Domain\Enums\Contracts\Described;

/**
 * What kind of thing a resource is.
 *
 * Classification, and nothing else. The category never decides behaviour — what
 * a resource can do is stated by its own flags (`is_spendable`, `is_tradeable`
 * and the rest), because those are the questions the analysis actually asks and
 * because a designer's gold may behave nothing like the next designer's.
 *
 * Keeping the two apart is what stops "action points are an ACTION so obviously
 * they don't accumulate" from becoming a rule the platform enforces on a game
 * that carries them between rounds on purpose.
 */
enum ResourceCategory: string implements Described
{
    case Currency = 'currency';
    case Material = 'material';
    case Action = 'action';
    case Victory = 'victory';
    case Information = 'information';
    case Capacity = 'capacity';
    case Health = 'health';
    case Progression = 'progression';
    case Other = 'other';

    /**
     * The category a resource falls into when nobody chose one.
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
            self::Currency => __('Currency'),
            self::Material => __('Material'),
            self::Action => __('Action'),
            self::Victory => __('Victory'),
            self::Information => __('Information'),
            self::Capacity => __('Capacity'),
            self::Health => __('Health'),
            self::Progression => __('Progression'),
            self::Other => __('Other'),
        };
    }

    /**
     * The kind of resource that belongs under this heading.
     */
    public function description(): string
    {
        return match ($this) {
            self::Currency => __('A general medium of exchange, spent on almost anything.'),
            self::Material => __('Something gathered and consumed to make other things.'),
            self::Action => __('The right to do something this turn, rather than a thing you own.'),
            self::Victory => __('Counts towards winning and is rarely spent.'),
            self::Information => __('What a player knows: cards in hand, revealed tiles, intelligence.'),
            self::Capacity => __('A limit on how much of something else can be held or done.'),
            self::Health => __('How much punishment a player or piece can still take.'),
            self::Progression => __('A track that moves one way: experience, research, reputation.'),
            self::Other => __('Anything that does not fit the categories above.'),
        };
    }
}
