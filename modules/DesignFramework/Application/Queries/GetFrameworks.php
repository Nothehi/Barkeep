<?php

namespace Modules\DesignFramework\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\DesignFramework\Application\DTOs\FrameworkFilters;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;

/**
 * The methodologies on the platform.
 *
 * The only unscoped list in the module, and the only one that could be: a framework has no
 * workspace, so there is no tenant to scope it to. What takes the place of scoping is
 * `$includeDrafts`, which is a *permission* rather than a filter — it is passed by the
 * caller after the policy has confirmed the caller administers frameworks, and it is
 * deliberately not readable from the query string.
 *
 * Filters can only narrow what a caller could already see.
 *
 * @see FrameworkRepository::listing()
 */
final class GetFrameworks
{
    public function __construct(private readonly FrameworkRepository $frameworks) {}

    /**
     * @return Collection<int, Framework>
     */
    public function handle(?FrameworkFilters $filters = null, bool $includeDrafts = false): Collection
    {
        return $this->frameworks->listing($filters, $includeDrafts);
    }
}
