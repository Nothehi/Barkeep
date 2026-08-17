<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\ContentData;
use Modules\DesignFramework\Application\Services\ContentWriter;
use Modules\DesignFramework\Domain\Models\DesignPractice;
use Modules\Identity\Domain\Models\User;

/**
 * Change something designers do.
 *
 * Instructions and description are written independently, so a form that clarifies the
 * instructions does not blank the summary above them.
 *
 * Only the fields the caller actually sent are written. `sent()` is what makes that
 * possible: a request that omits a field left it alone, and one that sent it empty
 * cleared it, and the two would otherwise be indistinguishable.
 */
final class UpdatePractice
{
    public function __construct(private readonly ContentWriter $writer) {}

    public function handle(User $actor, DesignPractice $practice, ContentData $data): DesignPractice
    {
        /** @var array<string, mixed> $body */
        $body = [];

        if ($data->sent('description')) {
            $body['description'] = $data->description;
        }

        if ($data->sent('instructions')) {
            $body['instructions'] = $data->instructions;
        }

        /** @var DesignPractice $updated */
        $updated = $this->writer->update($practice, $data, $body);

        return $updated;
    }
}
