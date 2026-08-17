<?php

namespace Modules\DesignFramework\Application\DTOs;

use Modules\DesignFramework\Domain\Enums\FrameworkStatus;

/**
 * What a frameworks list has been narrowed to.
 *
 * Filters can only ever narrow. Whether drafts appear at all is *not* a filter —
 * it is a permission, decided by the policy and passed to the repository
 * separately, so there is no combination of query-string values that widens what
 * a caller may see.
 *
 * A value that names nothing is treated as no filter rather than as an error. A
 * stale bookmark should show the list, not an error page.
 */
final readonly class FrameworkFilters
{
    public function __construct(
        public ?string $search = null,
        public ?FrameworkStatus $status = null,
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
        $search = FrameworkInput::text($input, 'search');
        $status = FrameworkInput::identifier($input, 'status');

        return new self(
            search: $search,
            status: $status === null ? null : FrameworkStatus::tryFrom($status),
        );
    }

    /**
     * Determine whether anything is being filtered at all.
     */
    public function areEmpty(): bool
    {
        return $this->search === null && $this->status === null;
    }
}
