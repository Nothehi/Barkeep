<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\ContentData;
use Modules\DesignFramework\Application\Services\ContentWriter;
use Modules\DesignFramework\Domain\Events\PracticeCreated;
use Modules\DesignFramework\Domain\Models\DesignPractice;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\Identity\Domain\Models\User;

/**
 * Add something for designers to do to a draft edition.
 *
 * "Write the core loop in one sentence." "Create a paper prototype." "Run a two-player
 * test."
 *
 * Practices carry instructions as well as a description, because the two are read at
 * different moments: the description is scanned in a list, the instructions are
 * followed once somebody has decided to do the thing.
 *
 * This is the content type that will eventually meet Playtesting. "Run a two-player
 * playtest" is an instruction here and a real thing there, and the two are deliberately
 * not wired together — a designer marks it complete themselves. When an integration
 * does arrive it will live in whichever module observes both, not in a dependency from
 * one to the other.
 *
 * The mechanics — proving the version is still a draft, resolving the phase through it,
 * deriving an address, allocating a position — are `ContentWriter`'s, shared with the
 * other four content types. What is specific to a practice is the body fields below.
 */
final class CreatePractice
{
    public function __construct(private readonly ContentWriter $writer) {}

    public function handle(User $creator, FrameworkVersion $version, ContentData $data): DesignPractice
    {
        /** @var DesignPractice $practice */
        $practice = $this->writer->create($version, DesignPractice::class, $data, [
            'description' => $data->description,
            'instructions' => $data->instructions,
        ]);

        event(new PracticeCreated(
            practiceId: $practice->id,
            frameworkVersionId: $version->id,
            phaseId: $practice->phase_id,
            slug: $practice->slug,
            createdBy: $creator->id,
        ));

        return $practice;
    }
}
