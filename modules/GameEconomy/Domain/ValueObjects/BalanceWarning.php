<?php

namespace Modules\GameEconomy\Domain\ValueObjects;

use Modules\GameEconomy\Domain\Enums\BalanceEntityType;
use Modules\GameEconomy\Domain\Enums\BalanceWarningCode;
use Modules\GameEconomy\Domain\Enums\BalanceWarningSeverity;

/**
 * One thing the analysis noticed.
 *
 * A finding is a value rather than a row, and that is section 28 of the brief
 * made structural: nothing persists these. An analysis is a reading of the
 * configuration as it stands right now, and storing it would immediately create
 * a second question — "is this warning still true?" — that the module would then
 * have to keep answering. Recomputing is cheap and always right.
 *
 * The code carries its own severity and its own general explanation; what is
 * built per finding is the sentence naming the thing it is about. That split is
 * why "an uncapped resource is a warning" has one definition rather than one per
 * call site.
 */
final readonly class BalanceWarning
{
    public function __construct(
        public BalanceWarningCode $code,
        public BalanceEntityType $entityType,
        public ?string $entityId,
        public string $subject,
        public string $description,
    ) {}

    /**
     * Report a finding about a named record.
     *
     * The subject is the thing's own name, kept separate from the description so
     * that a list of findings can be grouped and sorted by what they are about
     * without parsing prose.
     */
    public static function about(
        BalanceWarningCode $code,
        BalanceEntityType $entityType,
        ?string $entityId,
        string $subject,
        string $description,
    ): self {
        return new self($code, $entityType, $entityId, $subject, $description);
    }

    /**
     * How seriously to take this.
     */
    public function severity(): BalanceWarningSeverity
    {
        return $this->code->severity();
    }

    /**
     * Determine whether this describes something that cannot work.
     */
    public function isError(): bool
    {
        return $this->severity()->isError();
    }

    /**
     * The heading this finding is listed under.
     */
    public function title(): string
    {
        return $this->code->title();
    }

    /**
     * Why the check exists at all, as opposed to what it found here.
     */
    public function explanation(): string
    {
        return $this->code->explanation();
    }
}
