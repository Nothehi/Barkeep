<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\ContentData;
use Modules\DesignFramework\Application\Services\ContentWriter;
use Modules\DesignFramework\Domain\Models\DesignPrinciple;
use Modules\Identity\Domain\Models\User;

/**
 * Change a design rule.
 *
 * The phase may be changed here, including to none at all, which is how a principle
 * that turned out to be general gets promoted out of the stage it was first written
 * under. Moving it appends it at the end of the new set and closes the gap in the old
 * one — see `ContentWriter`.
 *
 * Only the fields the caller actually sent are written. `sent()` is what makes that
 * possible: a request that omits a field left it alone, and one that sent it empty
 * cleared it, and the two would otherwise be indistinguishable.
 */
final class UpdatePrinciple
{
    public function __construct(private readonly ContentWriter $writer) {}

    public function handle(User $actor, DesignPrinciple $principle, ContentData $data): DesignPrinciple
    {
        /** @var array<string, mixed> $body */
        $body = [];

        if ($data->sent('description')) {
            $body['description'] = $data->description;
        }

        /** @var DesignPrinciple $updated */
        $updated = $this->writer->update($principle, $data, $body);

        return $updated;
    }
}
