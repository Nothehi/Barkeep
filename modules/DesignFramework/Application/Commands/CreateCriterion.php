<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\ContentData;
use Modules\DesignFramework\Application\Services\ContentWriter;
use Modules\DesignFramework\Domain\Events\CriterionCreated;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\Identity\Domain\Models\User;

/**
 * Add something for designers to assess to a draft edition.
 *
 * A criterion is written as a question — "does the game provide meaningful decisions?"
 * — because it is answered rather than read. What a particular game answered is a
 * `CriterionEvaluation` against that game's own adoption; nothing about a grade is
 * stored here, and the table has no column to store one in.
 *
 * Criteria are the content type that most directly drives a game's progress, so adding
 * one to a draft version changes what the next studio to adopt it will be asked. It
 * changes nothing for studios already on the published version — they are reading
 * different rows.
 *
 * The mechanics — proving the version is still a draft, resolving the phase through it,
 * deriving an address, allocating a position — are `ContentWriter`'s, shared with the
 * other four content types. What is specific to a criterion is the body field below.
 */
final class CreateCriterion
{
    public function __construct(private readonly ContentWriter $writer) {}

    public function handle(User $creator, FrameworkVersion $version, ContentData $data): DesignCriterion
    {
        /** @var DesignCriterion $criterion */
        $criterion = $this->writer->create($version, DesignCriterion::class, $data, [
            'description' => $data->description,
        ]);

        event(new CriterionCreated(
            criterionId: $criterion->id,
            frameworkVersionId: $version->id,
            phaseId: $criterion->phase_id,
            slug: $criterion->slug,
            createdBy: $creator->id,
        ));

        return $criterion;
    }
}
