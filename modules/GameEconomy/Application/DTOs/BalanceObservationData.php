<?php

namespace Modules\GameEconomy\Application\DTOs;

use Modules\GameEconomy\Domain\Enums\ObservationSeverity;
use Modules\GameEconomy\Domain\Enums\ObservationSourceType;

/**
 * The validated input required to record what the studio noticed.
 *
 * `sourceReference` is a plain string rather than an id this module resolves,
 * and that is the boundary being kept: pointing it at a playtest would mean
 * GameEconomy holding another context's identifier and, before long, a copy of
 * its records.
 */
final readonly class BalanceObservationData
{
    /**
     * @param  list<string>  $sentFields  the fields the request actually mentioned
     */
    public function __construct(
        public ?string $title = null,
        public ?string $observation = null,
        public ?ObservationSourceType $sourceType = null,
        public ?string $sourceReference = null,
        public ?ObservationSeverity $severity = null,
        public array $sentFields = [],
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $source = EconomyInput::identifier($input, 'source_type');
        $severity = EconomyInput::identifier($input, 'severity');

        return new self(
            title: EconomyInput::text($input, 'title'),
            observation: EconomyInput::text($input, 'observation'),
            sourceType: $source === null ? null : ObservationSourceType::tryFrom($source),
            sourceReference: EconomyInput::text($input, 'source_reference'),
            severity: $severity === null ? null : ObservationSeverity::tryFrom($severity),
            sentFields: array_keys($input),
        );
    }

    /**
     * Determine whether the request mentioned a field at all.
     */
    public function sent(string $field): bool
    {
        return in_array($field, $this->sentFields, strict: true);
    }
}
