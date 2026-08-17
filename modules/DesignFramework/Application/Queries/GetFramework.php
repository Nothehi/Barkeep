<?php

namespace Modules\DesignFramework\Application\Queries;

use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\ValueObjects\FrameworkSlug;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;

/**
 * One methodology, by address.
 *
 * The lookup behind the `{framework}` route binding. Unauthorized on purpose: a draft
 * framework resolves here and is then hidden by the policy, which is what lets the refusal
 * be a 404 that does not distinguish "no such framework" from "a draft you may not see".
 */
final class GetFramework
{
    public function __construct(private readonly FrameworkRepository $frameworks) {}

    public function handle(FrameworkSlug $slug): ?Framework
    {
        return $this->frameworks->findBySlug($slug);
    }
}
