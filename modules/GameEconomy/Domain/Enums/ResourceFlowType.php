<?php

namespace Modules\GameEconomy\Domain\Enums;

use Modules\GameEconomy\Domain\Enums\Contracts\Described;

/**
 * How a resource moves.
 *
 * The direction lives here rather than in the sign of the amount, which is the
 * decision the whole net-flow calculation rests on. A flow stores a positive
 * magnitude and says what kind of movement it is; if the sign were the source of
 * truth, a "-2 generation" row would be a valid contradiction and the net figure
 * would have to guess which half the designer meant.
 *
 * Transfer and conversion are both zero-sum on purpose. A trade moves a resource
 * between players and creates none of it, and a conversion is modelled as the
 * two flows a designer would describe — wood consumed, gold generated — rather
 * than as one row that quietly mints value.
 */
enum ResourceFlowType: string implements Described
{
    case Generation = 'generation';
    case Consumption = 'consumption';
    case Conversion = 'conversion';
    case Transfer = 'transfer';
    case Loss = 'loss';
    case Reward = 'reward';
    case Penalty = 'penalty';

    /**
     * The kind of flow assumed when nobody chose one.
     */
    public static function default(): self
    {
        return self::Generation;
    }

    /**
     * Which way this flow moves the total quantity in play.
     *
     * `+1` adds to it, `-1` removes from it, `0` moves it around without
     * changing it. This single method is what the net-flow calculation reads, so
     * a flow type added later becomes part of every figure in the module by
     * answering here rather than by being special-cased at each call site.
     */
    public function direction(): int
    {
        return match ($this) {
            self::Generation, self::Reward => 1,
            self::Consumption, self::Loss, self::Penalty => -1,
            self::Conversion, self::Transfer => 0,
        };
    }

    /**
     * Determine whether this flow puts more of the resource into the game.
     */
    public function increases(): bool
    {
        return $this->direction() > 0;
    }

    /**
     * Determine whether this flow takes the resource out of the game.
     */
    public function decreases(): bool
    {
        return $this->direction() < 0;
    }

    /**
     * Determine whether this flow only moves the resource around.
     */
    public function isNeutral(): bool
    {
        return $this->direction() === 0;
    }

    /**
     * The flow types that count as a source of a resource.
     *
     * @return list<self>
     */
    public static function generating(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type): bool => $type->increases()));
    }

    /**
     * The flow types that count as a sink for a resource.
     *
     * @return list<self>
     */
    public static function consuming(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type): bool => $type->decreases()));
    }

    /**
     * A human readable label for the flow type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Generation => __('Generation'),
            self::Consumption => __('Consumption'),
            self::Conversion => __('Conversion'),
            self::Transfer => __('Transfer'),
            self::Loss => __('Loss'),
            self::Reward => __('Reward'),
            self::Penalty => __('Penalty'),
        };
    }

    /**
     * What this kind of flow describes.
     */
    public function description(): string
    {
        return match ($this) {
            self::Generation => __('The resource enters the game: income, harvest, production.'),
            self::Consumption => __('The resource is spent to do something.'),
            self::Conversion => __('The resource is exchanged for another one.'),
            self::Transfer => __('The resource moves between players without changing the total.'),
            self::Loss => __('The resource disappears: spoilage, decay, an end-of-round discard.'),
            self::Reward => __('The resource arrives as a payout for achieving something.'),
            self::Penalty => __('The resource is taken away as a punishment.'),
        };
    }
}
