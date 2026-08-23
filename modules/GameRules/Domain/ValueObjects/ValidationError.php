<?php

namespace Modules\GameRules\Domain\ValueObjects;

use Modules\GameRules\Domain\Enums\RuleEntityType;
use Modules\GameRules\Domain\Enums\ValidationCode;
use Modules\GameRules\Domain\Enums\ValidationSeverity;

/**
 * One thing the validator found.
 *
 * A finding is a value rather than a row, and nothing persists these. A
 * validation run is a reading of the rule set as it stands right now, and
 * storing it would immediately create a second question — "is this still true?" —
 * that the module would then have to keep answering. Recomputing is cheap and
 * always right.
 *
 * The code carries its own severity and its own general explanation; what is
 * built per finding is the sentence naming the thing it is about. That split is
 * why "an action with no phase is an error" has one definition rather than one
 * per call site.
 *
 * The shape is the one section 30 of the brief specifies — code, severity,
 * entity type, entity id, message — with the subject kept separate from the
 * message so a list of findings can be grouped by what they are about without
 * parsing prose.
 */
final readonly class ValidationError
{
    public function __construct(
        public ValidationCode $code,
        public RuleEntityType $entityType,
        public ?string $entityId,
        public string $subject,
        public string $message,
    ) {}

    /**
     * Report a finding about a named record.
     */
    public static function about(
        ValidationCode $code,
        RuleEntityType $entityType,
        ?string $entityId,
        string $subject,
        string $message,
    ): self {
        return new self($code, $entityType, $entityId, $subject, $message);
    }

    /**
     * Report a finding about the rule set as a whole.
     */
    public static function aboutRuleSet(ValidationCode $code, string $subject, string $message): self
    {
        return new self($code, RuleEntityType::RuleSet, null, $subject, $message);
    }

    /**
     * How seriously to take this.
     */
    public function severity(): ValidationSeverity
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
