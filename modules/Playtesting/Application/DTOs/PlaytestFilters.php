<?php

namespace Modules\Playtesting\Application\DTOs;

use Modules\Playtesting\Domain\Enums\PlaytestStatus;

/**
 * What a playtests list has been narrowed to.
 *
 * Filters can only ever narrow what a caller could already see: the game is a
 * required argument of the query rather than a value in here, so there is no
 * combination of filters that widens the scope.
 *
 * A value that names nothing — a status the enum does not have — is treated as
 * no filter rather than as an error. A stale bookmark should show the list,
 * not an error page.
 */
final readonly class PlaytestFilters
{
    public function __construct(
        public ?string $search = null,
        public ?PlaytestStatus $status = null,
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
        $search = PlaytestInput::text($input, 'search');
        $status = PlaytestInput::identifier($input, 'status');

        return new self(
            search: $search,
            status: $status === null ? null : PlaytestStatus::tryFrom($status),
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
