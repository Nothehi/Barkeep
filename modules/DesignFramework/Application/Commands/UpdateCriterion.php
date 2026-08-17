<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\ContentData;
use Modules\DesignFramework\Application\Services\ContentWriter;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\Identity\Domain\Models\User;

/**
 * Change something designers assess.
 *
 * Only while the version is a draft. Rewording a published criterion is the exact
 * failure the whole module is arranged to prevent: a studio graded their game against
 * the question as it was written, and changing it afterwards leaves an answer attached
 * to a question nobody was asked.
 *
 * Only the fields the caller actually sent are written. `sent()` is what makes that
 * possible: a request that omits a field left it alone, and one that sent it empty
 * cleared it, and the two would otherwise be indistinguishable.
 */
final class UpdateCriterion
{
    public function __construct(private readonly ContentWriter $writer) {}

    public function handle(User $actor, DesignCriterion $criterion, ContentData $data): DesignCriterion
    {
        /** @var array<string, mixed> $body */
        $body = [];

        if ($data->sent('description')) {
            $body['description'] = $data->description;
        }

        /** @var DesignCriterion $updated */
        $updated = $this->writer->update($criterion, $data, $body);

        return $updated;
    }
}
