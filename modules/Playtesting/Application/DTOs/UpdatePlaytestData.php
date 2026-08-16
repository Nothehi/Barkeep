<?php

namespace Modules\Playtesting\Application\DTOs;

use Carbon\CarbonImmutable;

/**
 * The validated input for changing a playtest, and a record of what was sent.
 *
 * The list of provided keys is the unusual part, and it exists because a
 * completed playtest is not uniformly frozen. Its plan — the title, the
 * objective, the hypothesis, the version, the date — is fixed for good, but
 * its conclusion is written afterwards and has to stay editable.
 *
 * Distinguishing "left this field alone" from "cleared this field" is
 * therefore load-bearing rather than a nicety: without it, a request that only
 * meant to write a conclusion would look identical to one that also blanked
 * the hypothesis, and the command would have to refuse both.
 *
 * The form requests validate with `sometimes`, so a key reaching here is a key
 * the caller actually sent.
 */
final readonly class UpdatePlaytestData
{
    /**
     * The fields that describe what a playtest set out to do.
     *
     * Changing any of these rewrites the question the evidence was gathered
     * against, which is why they are frozen together once it is over.
     *
     * @var list<string>
     */
    public const PLAN_FIELDS = ['game_version_id', 'title', 'objective', 'hypothesis', 'planned_at'];

    /**
     * @param  list<string>  $provided  the input keys the caller actually sent
     */
    public function __construct(
        public ?string $gameVersionId = null,
        public ?string $title = null,
        public ?string $objective = null,
        public ?string $hypothesis = null,
        public ?string $conclusion = null,
        public ?CarbonImmutable $plannedAt = null,
        public array $provided = [],
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            gameVersionId: PlaytestInput::identifier($input, 'game_version_id'),
            title: array_key_exists('title', $input) ? PlaytestInput::requiredText($input, 'title') : null,
            objective: array_key_exists('objective', $input) ? PlaytestInput::requiredText($input, 'objective') : null,
            hypothesis: PlaytestInput::text($input, 'hypothesis'),
            conclusion: PlaytestInput::text($input, 'conclusion'),
            plannedAt: PlaytestInput::timestamp($input, 'planned_at'),
            provided: array_keys($input),
        );
    }

    /**
     * Determine whether the caller sent the given field at all.
     */
    public function sent(string $field): bool
    {
        return in_array($field, $this->provided, strict: true);
    }

    /**
     * Determine whether this request tries to rewrite the plan.
     */
    public function touchesPlan(): bool
    {
        return array_intersect(self::PLAN_FIELDS, $this->provided) !== [];
    }

    /**
     * Determine whether this request writes a conclusion.
     */
    public function touchesConclusion(): bool
    {
        return $this->sent('conclusion');
    }
}
