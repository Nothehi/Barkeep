<?php

namespace Modules\PrototypeIteration\Application\DTOs;

use Modules\PrototypeIteration\Domain\Enums\PrototypeStatus;
use Modules\PrototypeIteration\Domain\Enums\PrototypeType;

/**
 * What a prototypes list has been narrowed to.
 *
 * Filters can only ever narrow what a caller could already see: the game is a
 * required argument of the query rather than a value in here, so there is no
 * combination of filters that widens the scope.
 *
 * A value that names nothing — a status or kind the enum does not have — is
 * treated as no filter rather than as an error. A stale bookmark should show the
 * list, not an error page.
 */
final readonly class PrototypeFilters
{
    public function __construct(
        public ?string $search = null,
        public ?PrototypeStatus $status = null,
        public ?PrototypeType $type = null,
    ) {}

    /**
     * The unfiltered list.
     */
    public static function none(): self
    {
        return new self;
    }

    /**
     * Build the filters from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $status = IterationInput::identifier($input, 'status');
        $type = IterationInput::identifier($input, 'type');

        return new self(
            search: IterationInput::text($input, 'search'),
            status: $status === null ? null : PrototypeStatus::tryFrom($status),
            type: $type === null ? null : PrototypeType::tryFrom($type),
        );
    }

    /**
     * Determine whether anything is being filtered at all.
     */
    public function areEmpty(): bool
    {
        return $this->search === null && $this->status === null && $this->type === null;
    }
}
