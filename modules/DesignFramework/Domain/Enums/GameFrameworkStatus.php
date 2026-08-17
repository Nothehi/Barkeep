<?php

namespace Modules\DesignFramework\Domain\Enums;

/**
 * Where a game's relationship with a methodology stands.
 *
 * Distinct from the framework's own lifecycle and from the game's. A published
 * framework, an active game and a paused adoption are all perfectly consistent:
 * the studio has decided to stop working the process for a while, which is a
 * thing designers actually do.
 *
 * Paused exists so that stopping is honest. Without it the only ways to step
 * away from a framework are to leave it looking active — which makes every
 * progress bar a lie — or to complete it, which claims something that did not
 * happen.
 */
enum GameFrameworkStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';

    /**
     * The status an adoption starts life in.
     */
    public static function default(): self
    {
        return self::Active;
    }

    /**
     * The statuses this one may legally move to.
     *
     * Completed is terminal, and unlike the framework's own lifecycle that is
     * not about read-only-ness — the game keeps its evaluations and can still
     * read every phase. It is about the claim: a studio that has finished
     * working a methodology and then wants back in is starting again, and
     * "start again" is `AssignFrameworkToGame` on the version they mean, not a
     * quiet reversal of a declaration they made.
     *
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Active => [self::Paused, self::Completed],
            self::Paused => [self::Active, self::Completed],
            self::Completed => [],
        };
    }

    /**
     * Determine whether this status may move to the given one.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->transitions(), strict: true);
    }

    /**
     * Determine whether the game may still record work against the framework.
     *
     * A paused adoption refuses new evaluations, completions and responses.
     * That is the difference between pausing and simply not visiting: the
     * record says the studio stepped away, and nothing lands in the gap.
     */
    public function allowsProgress(): bool
    {
        return $this === self::Active;
    }

    /**
     * Determine whether the adoption is over.
     */
    public function isTerminal(): bool
    {
        return $this->transitions() === [];
    }

    /**
     * A human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Paused => __('Paused'),
            self::Completed => __('Completed'),
        };
    }

    /**
     * The verb offered for moving from this status to the given one.
     */
    public function transitionLabelTo(self $target): string
    {
        return match ([$this, $target]) {
            [self::Paused, self::Active] => __('Resume framework'),
            default => match ($target) {
                self::Active => __('Make active'),
                self::Paused => __('Pause framework'),
                self::Completed => __('Complete framework'),
            },
        };
    }

    /**
     * The message shown when a change is refused because of this status.
     */
    public function deniedReason(): string
    {
        return match ($this) {
            self::Active => __('This framework is active.'),
            self::Paused => __('This framework is paused. Resume it to record more work.'),
            self::Completed => __('This game has finished working through its framework.'),
        };
    }
}
