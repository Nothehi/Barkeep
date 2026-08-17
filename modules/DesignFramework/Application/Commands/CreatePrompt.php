<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\ContentData;
use Modules\DesignFramework\Application\Services\ContentWriter;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\Identity\Domain\Models\User;

/**
 * Add a thinking question to a draft edition.
 *
 * "What is the most interesting decision in your game?" "Where does tension come
 * from?"
 *
 * The difference from a criterion is the shape of the answer. A criterion is graded and
 * the grade is the point; a prompt is answered in prose and there is no right answer.
 * That is also why answering prompts does not move a progress bar — see
 * `FrameworkProgressCalculator`.
 *
 * No event, for the same reason as a principle: nothing outside this module can act on
 * a question having been written.
 *
 * The mechanics — proving the version is still a draft, resolving the phase through it,
 * deriving an address, allocating a position — are `ContentWriter`'s, shared with the
 * other four content types. What is specific to a prompt is the body field below.
 */
final class CreatePrompt
{
    public function __construct(private readonly ContentWriter $writer) {}

    public function handle(User $creator, FrameworkVersion $version, ContentData $data): DesignPrompt
    {
        /** @var DesignPrompt $prompt */
        $prompt = $this->writer->create($version, DesignPrompt::class, $data, [
            'prompt' => $data->prompt,
        ]);

        return $prompt;
    }
}
