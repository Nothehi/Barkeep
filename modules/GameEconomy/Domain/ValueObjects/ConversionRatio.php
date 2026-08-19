<?php

namespace Modules\GameEconomy\Domain\ValueObjects;

/**
 * What one resource buys of another, through a particular action.
 *
 * "Two wood makes one gold" is the sentence this exists to state precisely: a
 * ratio of 0.5 gold per wood, derived from an action that costs 2 wood and pays
 * 1 gold.
 *
 * The ratio is always tied to the action that produces it, and there is
 * deliberately no attempt to compose several of them into an exchange rate for
 * the whole economy. Section 26 of the brief is explicit about why: resources do
 * not share a scalar value unless a designer says they do, and a module that
 * quietly decided one gold was one wood would be inventing the most consequential
 * number in the game.
 */
final readonly class ConversionRatio
{
    public function __construct(
        public string $actionId,
        public string $actionName,
        public string $fromResourceId,
        public string $fromResourceName,
        public Quantity $fromAmount,
        public string $toResourceId,
        public string $toResourceName,
        public Quantity $toAmount,
        public ?Quantity $ratio,
    ) {}

    /**
     * Work out the ratio for one pair on one action.
     *
     * A null ratio is a real answer rather than a failure: an action that costs
     * nothing converts nothing at any rate, and the analysis reports that as a
     * free action rather than dividing by zero.
     */
    public static function between(
        string $actionId,
        string $actionName,
        string $fromResourceId,
        string $fromResourceName,
        Quantity $fromAmount,
        string $toResourceId,
        string $toResourceName,
        Quantity $toAmount,
    ): self {
        return new self(
            actionId: $actionId,
            actionName: $actionName,
            fromResourceId: $fromResourceId,
            fromResourceName: $fromResourceName,
            fromAmount: $fromAmount,
            toResourceId: $toResourceId,
            toResourceName: $toResourceName,
            toAmount: $toAmount,
            ratio: $toAmount->dividedBy($fromAmount),
        );
    }

    /**
     * Determine whether the rate could be worked out at all.
     */
    public function isDefined(): bool
    {
        return $this->ratio !== null;
    }

    /**
     * How the exchange reads: "2 wood → 1 gold".
     */
    public function label(): string
    {
        return __(':fromAmount :fromResource → :toAmount :toResource', [
            'fromAmount' => $this->fromAmount->label(),
            'fromResource' => $this->fromResourceName,
            'toAmount' => $this->toAmount->label(),
            'toResource' => $this->toResourceName,
        ]);
    }
}
