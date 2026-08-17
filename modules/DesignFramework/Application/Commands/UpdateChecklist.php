<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\ContentData;
use Modules\DesignFramework\Application\Services\ContentWriter;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\Identity\Domain\Models\User;

/**
 * Change a readiness gate's title or description.
 *
 * Its items are edited separately. A checklist and its requirements change at different
 * rates — the title of "prototype readiness" is settled long before the list of what
 * that means is.
 *
 * Only the fields the caller actually sent are written. `sent()` is what makes that
 * possible: a request that omits a field left it alone, and one that sent it empty
 * cleared it, and the two would otherwise be indistinguishable.
 */
final class UpdateChecklist
{
    public function __construct(private readonly ContentWriter $writer) {}

    public function handle(User $actor, Checklist $checklist, ContentData $data): Checklist
    {
        /** @var array<string, mixed> $body */
        $body = [];

        if ($data->sent('description')) {
            $body['description'] = $data->description;
        }

        /** @var Checklist $updated */
        $updated = $this->writer->update($checklist, $data, $body);

        return $updated;
    }
}
