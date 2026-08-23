<?php

namespace Modules\GameRules\Application\DTOs;

use Modules\GameRules\Domain\Enums\RuleSetStatus;

/**
 * How a rule sets list was narrowed.
 *
 * A value that names nothing is treated as no filter rather than as an error: a
 * stale bookmark carrying `status=retired` should show the list, not a validation
 * page.
 */
final readonly class RuleSetFilters
{
    public function __construct(
        public ?string $search = null,
        public ?RuleSetStatus $status = null,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            search: RuleInput::text($input, 'search'),
            status: RuleInput::optionalEnum($input, 'status', RuleSetStatus::class),
        );
    }

    /**
     * Determine whether anything was actually asked for.
     */
    public function isEmpty(): bool
    {
        return $this->search === null && $this->status === null;
    }

    /**
     * The filters as the client sent them, for round-tripping into the view.
     *
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->status?->value,
        ];
    }
}
