<?php

namespace Modules\PrototypeIteration\Domain\Exceptions;

use Modules\PrototypeIteration\Domain\Enums\EvidenceType;

/**
 * Raised when a decision cites something that cannot be resolved.
 *
 * Citations are checked when they are made, even though the stored reference has
 * no foreign key behind it. The two facts are not in tension: the reference is
 * loose so that this module holds no copy of the evidence and no key into
 * another context's tables, and it is validated on the way in so that a
 * citation is at least true at the moment somebody makes it.
 *
 * Resolution is always scoped to the iteration's own game, through whoever owns
 * the type. That is what stops a citation from being used as a reach across a
 * workspace boundary: an observation id from another studio's playtest does not
 * resolve, and — as everywhere else in the platform — is reported the same way
 * as an id that names nothing at all.
 */
final class UnknownEvidenceReference extends IterationRuleViolation
{
    private function __construct(
        public readonly EvidenceType $type,
        public readonly ?string $referenceId,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function forReference(EvidenceType $type, ?string $referenceId): self
    {
        return new self($type, $referenceId, __('That :type cannot be found in this game.', [
            'type' => mb_strtolower($type->label()),
        ]));
    }

    /**
     * Raised when a type that needs a reference was cited without one.
     */
    public static function missingFor(EvidenceType $type): self
    {
        return new self($type, null, __('Choose the :type this decision is based on.', [
            'type' => mb_strtolower($type->label()),
        ]));
    }

    /**
     * Reported against the submitted field so the form can show it in place.
     */
    public function field(): string
    {
        return 'reference_id';
    }
}
