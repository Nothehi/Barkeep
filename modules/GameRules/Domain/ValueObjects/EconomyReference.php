<?php

namespace Modules\GameRules\Domain\ValueObjects;

/**
 * A pointer from a rule to something GameEconomy owns.
 *
 * The whole of section 34 of the brief, in one small class. A build action costs
 * five wood; the five and the wood are the economy's, and this module must not
 * hold a second copy of either. So what is stored on a rule action, requirement
 * or effect is a *handle* — `build`, `wood` — and what is shown beside it is
 * whatever the economy says today, read live at render time.
 *
 * Three consequences follow from that, and all three are wanted:
 *
 * - the numbers on the rules screen always agree with the balance screen,
 *   because there is only one set of them;
 * - a handle that names nothing resolves to {@see unresolved()} rather than
 *   failing, which is the ordinary state of a rule set written before the
 *   economy was modelled;
 * - nothing in this module can be joined to a GameEconomy table, because a
 *   string is not a foreign key.
 *
 * The `summary` is already-worded text produced by the adapter — "5 Wood, 2
 * Stone" — because formatting an amount is the economy's business and this
 * module does not know that amounts are exact decimals.
 */
final readonly class EconomyReference
{
    public function __construct(
        public string $handle,
        public bool $isResolved,
        public ?string $label = null,
        public ?string $summary = null,
    ) {}

    /**
     * A handle the active balance profile knows about.
     */
    public static function resolved(string $handle, string $label, ?string $summary = null): self
    {
        return new self(handle: $handle, isResolved: true, label: $label, summary: $summary);
    }

    /**
     * A handle that names nothing in the active balance profile.
     *
     * Not an error. A rule set is usually written before the economy is
     * modelled, and a studio may never model one at all — the validator mentions
     * it and nothing refuses it.
     */
    public static function unresolved(string $handle): self
    {
        return new self(handle: $handle, isResolved: false);
    }
}
