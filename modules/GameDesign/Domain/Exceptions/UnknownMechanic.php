<?php

namespace Modules\GameDesign\Domain\Exceptions;

/**
 * Raised when a game claims a term the vocabulary cannot offer it.
 *
 * Two different situations, deliberately reported the same way: the id names
 * nothing at all, or it names a term a curator has retired. Both mean "you
 * cannot claim this", and telling a designer which of the two it is would be
 * telling them about the platform's history rather than about their game.
 *
 * Retirement is the interesting case. A game that already claimed a term keeps
 * it — the pivot row is untouched by archiving — but it cannot newly claim one,
 * which is what stops the vocabulary growing back through the picker.
 */
final class UnknownMechanic extends GameRuleViolation
{
    /**
     * @param  list<string>  $ids
     */
    public static function forIds(array $ids): self
    {
        return new self(count($ids) === 1
            ? __('That mechanic is not one this game can claim.')
            : __(':count of those mechanics are not ones this game can claim.', ['count' => count($ids)]));
    }

    /**
     * Reported against the picker so the form shows it in place.
     */
    public function field(): string
    {
        return 'mechanics';
    }
}
