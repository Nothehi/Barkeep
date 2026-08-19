<?php

namespace Modules\GameEconomy\Domain\Enums;

use Modules\GameEconomy\Domain\Enums\Contracts\Described;

/**
 * What kind of thing an action does that is not a quantity of a resource.
 *
 * Five cases, and the list stays short on purpose. Every entry here is something
 * the analysis can say nothing quantitative about — that is why it is an effect
 * rather than a cost or a reward — so a longer taxonomy would buy precision that
 * nothing in the module reads.
 *
 * `Other` carries the rest, and it carrying a lot is fine. An effect is written
 * to be read by a person.
 */
enum ActionEffectType: string implements Described
{
    case ResourceModifier = 'resource_modifier';
    case CapacityModifier = 'capacity_modifier';
    case Unlock = 'unlock';
    case Block = 'block';
    case Other = 'other';

    /**
     * The kind of effect assumed when nobody chose one.
     */
    public static function default(): self
    {
        return self::Other;
    }

    /**
     * Determine whether this kind of effect is normally stated with a number.
     *
     * Read by the editor to decide whether to offer a value field, and by the
     * analysis to decide whether a missing value is worth mentioning. An unlock
     * has no magnitude; a capacity modifier without one says nothing at all.
     */
    public function expectsValue(): bool
    {
        return match ($this) {
            self::ResourceModifier, self::CapacityModifier => true,
            self::Unlock, self::Block, self::Other => false,
        };
    }

    /**
     * A human readable label for the effect type.
     */
    public function label(): string
    {
        return match ($this) {
            self::ResourceModifier => __('Resource modifier'),
            self::CapacityModifier => __('Capacity modifier'),
            self::Unlock => __('Unlock'),
            self::Block => __('Block'),
            self::Other => __('Other'),
        };
    }

    /**
     * What this kind of effect describes.
     */
    public function description(): string
    {
        return match ($this) {
            self::ResourceModifier => __('Changes how much of a resource something costs or pays.'),
            self::CapacityModifier => __('Changes a limit: hand size, storage, the number of workers.'),
            self::Unlock => __('Makes something available that was not before.'),
            self::Block => __('Closes something off to a player or to everybody.'),
            self::Other => __('Anything that does not fit the categories above.'),
        };
    }
}
