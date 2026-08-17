<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\ContentData;
use Modules\DesignFramework\Application\Services\ContentWriter;
use Modules\DesignFramework\Domain\Models\DesignPrinciple;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\Identity\Domain\Models\User;

/**
 * Add a design rule to a draft edition.
 *
 * Principles are the one content type a designer does nothing *with* — there is no
 * evaluation, completion or answer to record against one. "Every decision should have
 * meaningful consequences" is read, held in mind, and argued with; it shapes how the
 * criteria beside it are answered.
 *
 * Which is why no event is dispatched here. Nothing outside this module can act on a
 * principle having been written, and an event that exists only for symmetry is an
 * event somebody will eventually subscribe to by mistake.
 *
 * A principle filed under no phase applies across the whole methodology, and that is
 * the common case rather than the exception.
 *
 * The mechanics — proving the version is still a draft, resolving the phase through it,
 * deriving an address, allocating a position — are `ContentWriter`'s, shared with the
 * other four content types. What is specific to a principle is the body field below.
 */
final class CreatePrinciple
{
    public function __construct(private readonly ContentWriter $writer) {}

    public function handle(User $creator, FrameworkVersion $version, ContentData $data): DesignPrinciple
    {
        /** @var DesignPrinciple $principle */
        $principle = $this->writer->create($version, DesignPrinciple::class, $data, [
            'description' => $data->description,
        ]);

        return $principle;
    }
}
