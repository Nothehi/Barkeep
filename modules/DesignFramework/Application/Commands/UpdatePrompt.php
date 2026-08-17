<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\ContentData;
use Modules\DesignFramework\Application\Services\ContentWriter;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
use Modules\Identity\Domain\Models\User;

/**
 * Change a thinking question.
 *
 * The question itself is required and cannot be blanked — a prompt with no question is
 * nothing — while the title, which is only a label for lists, is free to change.
 *
 * Only the fields the caller actually sent are written. `sent()` is what makes that
 * possible: a request that omits a field left it alone, and one that sent it empty
 * cleared it, and the two would otherwise be indistinguishable.
 */
final class UpdatePrompt
{
    public function __construct(private readonly ContentWriter $writer) {}

    public function handle(User $actor, DesignPrompt $prompt, ContentData $data): DesignPrompt
    {
        /** @var array<string, mixed> $body */
        $body = [];

        if ($data->sent('prompt')) {
            $body['prompt'] = $data->prompt;
        }

        /** @var DesignPrompt $updated */
        $updated = $this->writer->update($prompt, $data, $body);

        return $updated;
    }
}
