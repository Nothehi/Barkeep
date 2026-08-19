<?php

namespace Modules\GameEconomy\Domain\Enums;

use Modules\GameEconomy\Domain\Enums\Contracts\Described;

/**
 * What sort of number a balance variable is.
 *
 * Grouping for the variable table, which is the screen a designer spends the
 * most time on and the one that gets unusable fastest: twenty-seven numbers in
 * one flat list is a spreadsheet, and the whole reason for having this module
 * rather than a spreadsheet is that the numbers are grouped by what they do.
 *
 * Like {@see ResourceCategory}, this classifies and never decides anything.
 */
enum BalanceVariableCategory: string implements Described
{
    case StartingValue = 'starting_value';
    case Cost = 'cost';
    case Reward = 'reward';
    case Production = 'production';
    case Capacity = 'capacity';
    case Threshold = 'threshold';
    case Timing = 'timing';
    case Probability = 'probability';
    case Other = 'other';

    /**
     * The category a variable falls into when nobody chose one.
     */
    public static function default(): self
    {
        return self::Other;
    }

    /**
     * Determine whether values in this category are proportions rather than
     * counts.
     *
     * Read by the analysis, which reports a probability outside 0–1 as an error
     * rather than as a value somebody meant. The module stores probabilities on
     * one scale throughout — 0.0 to 1.0 — and never mixes it with percentages,
     * because a game where one variable is 0.25 and another is 25 is a game
     * where somebody eventually multiplies the wrong pair.
     */
    public function isProportion(): bool
    {
        return $this === self::Probability;
    }

    /**
     * A human readable label for the category.
     */
    public function label(): string
    {
        return match ($this) {
            self::StartingValue => __('Starting value'),
            self::Cost => __('Cost'),
            self::Reward => __('Reward'),
            self::Production => __('Production'),
            self::Capacity => __('Capacity'),
            self::Threshold => __('Threshold'),
            self::Timing => __('Timing'),
            self::Probability => __('Probability'),
            self::Other => __('Other'),
        };
    }

    /**
     * The kind of number that belongs under this heading.
     */
    public function description(): string
    {
        return match ($this) {
            self::StartingValue => __('What a player begins the game holding.'),
            self::Cost => __('What something takes to do or to buy.'),
            self::Reward => __('What something pays out.'),
            self::Production => __('How much arrives per round or per action.'),
            self::Capacity => __('A ceiling: hand size, storage, workers available.'),
            self::Threshold => __('The number that triggers something, such as a win.'),
            self::Timing => __('Rounds, turns, delays and durations.'),
            self::Probability => __('A chance, always written between 0 and 1.'),
            self::Other => __('Anything that does not fit the categories above.'),
        };
    }
}
