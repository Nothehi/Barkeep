<?php

namespace Modules\DesignFramework\Application\Services;

use Modules\DesignFramework\Application\DTOs\ContentData;
use Modules\DesignFramework\Domain\Enums\FrameworkContentStatus;
use Modules\DesignFramework\Domain\Exceptions\ContentDoesNotBelongToFrameworkVersion;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\Models\PhaseContent;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;

/**
 * The mechanics every piece of framework content is written with.
 *
 * The five content types are five different things to a designer and one shape to
 * the database, and the parts of writing one that are easy to get subtly wrong are
 * exactly the parts they share:
 *
 * - **the version must still be a draft.** Checked through the guard on every write,
 *   never assumed from having got this far.
 * - **the phase must belong to this version.** A phase id arrives in a request body
 *   rather than through a route binding, so it is resolved *through* the version — a
 *   phase from another version is not found rather than being found and rejected.
 * - **the address is derived once and then left alone.** Renaming content does not
 *   re-derive its address, so a bookmarked phase URL survives a reword and a seeder
 *   rebuilding v1 twice produces the same identifiers.
 * - **the position is allocated, never supplied.** New content is appended; moving it
 *   is a separate, explicit operation.
 * - **moving content between phases resequences both.** The phase it left closes its
 *   gap, and the phase it joined appends it at the end.
 *
 * The per-type commands supply the body fields their own type has and nothing else,
 * which is what keeps `CreatePrincipleData` from needing to exist.
 */
final class ContentWriter
{
    public function __construct(
        private readonly FrameworkRepository $frameworks,
        private readonly FrameworkModificationGuard $guard,
        private readonly ContentSequencer $sequencer,
        private readonly ContentSlugAllocator $slugs,
    ) {}

    /**
     * Write a new piece of content into a version.
     *
     * @template TContent of PhaseContent
     *
     * @param  class-string<TContent>  $type
     * @param  array<string, mixed>  $body  the fields this content type has
     * @return TContent
     */
    public function create(FrameworkVersion $version, string $type, ContentData $data, array $body)
    {
        $this->guard->ensureVersionIsModifiable($version);

        $phase = $this->resolvePhase($version, $data->phaseId);
        $title = (string) $data->title;

        $content = new $type;

        $content->fill($body);

        $content->framework_version_id = $version->getKey();
        $content->phase_id = $phase?->getKey();
        $content->title = $title;
        $content->slug = $this->slugs->derive(
            $type::query()->where('framework_version_id', $version->getKey()),
            $title,
        )->value;
        $content->status = $data->status ?? FrameworkContentStatus::default();
        $content->position = $this->sequencer->append(
            $this->frameworks->contentSiblings($version, $type, $phase?->getKey()),
        );

        $content->save();

        $content->setRelation('version', $version);
        $content->setRelation('phase', $phase);

        return $content;
    }

    /**
     * Change a piece of content, and move it between phases if asked.
     *
     * The title is written without re-deriving the address, on purpose — see the class
     * docblock. Fields the caller did not send are left alone, which is what lets a
     * form save a description without blanking the instructions beside it.
     *
     * @template TContent of PhaseContent
     *
     * @param  TContent  $content
     * @param  array<string, mixed>  $body  the fields this content type has, already
     *                                      narrowed to the ones that were sent
     * @return TContent
     */
    public function update(PhaseContent $content, ContentData $data, array $body)
    {
        $this->guard->ensureContentIsModifiable($content);

        $version = $content->version;

        if ($body !== []) {
            $content->fill($body);
        }

        if ($data->sent('title') && $data->title !== null) {
            $content->title = $data->title;
        }

        if ($data->status !== null) {
            $content->status = $data->status;
        }

        /*
         * `false` means nothing moved, `null` means it moved out of the top level, and
         * a string is the phase it left. Three states rather than two, because "was
         * filed under no phase" and "was not moved" are different facts that a plain
         * nullable would flatten into one.
         */
        $movedFrom = false;

        if ($version !== null && $data->movesPhase()) {
            $movedFrom = $this->movePhase($content, $version, $data->phaseId);
        }

        $content->save();

        /*
         * Close the gap the content left behind, and only then. Resequencing before
         * the save would renumber a set the content is still nominally part of.
         */
        if ($version !== null && $movedFrom !== false) {
            $this->sequencer->resequence(
                $this->frameworks->contentSiblings($version, $content::class, $movedFrom),
            );
        }

        /** @var TContent $content */
        return $content;
    }

    /**
     * File content under a different phase, appending it at the end of the new one.
     *
     * Appended rather than keeping its old position, because a position means nothing
     * outside the set it was allocated in: content moved from position 3 of one phase
     * into a phase with one item would otherwise claim to be third of one.
     *
     * Returns the phase id the content came from so the caller can close its gap, or
     * `false` when nothing moved.
     */
    private function movePhase(PhaseContent $content, FrameworkVersion $version, ?string $phaseId): string|false|null
    {
        $target = $this->resolvePhase($version, $phaseId);
        $targetId = $target?->getKey();
        $currentId = $content->phase_id;

        if ((string) $targetId === (string) $currentId) {
            return false;
        }

        $content->phase_id = $targetId;
        $content->setRelation('phase', $target);
        $content->position = $this->sequencer->append(
            $this->frameworks->contentSiblings($version, $content::class, $targetId === null ? null : (string) $targetId),
        );

        return $currentId === null ? null : (string) $currentId;
    }

    /**
     * Resolve the phase content is being filed under, if any.
     *
     * A null id is not an error: content that applies across the whole methodology is
     * filed under no phase, and that is the difference between "every decision should
     * have meaningful consequences" and "write the core loop in one sentence".
     *
     * @throws ContentDoesNotBelongToFrameworkVersion when the phase is not this
     *                                                version's
     */
    private function resolvePhase(FrameworkVersion $version, ?string $phaseId): ?DesignPhaseDefinition
    {
        if ($phaseId === null) {
            return null;
        }

        return $this->frameworks->findPhaseById($version, $phaseId)
            ?? throw ContentDoesNotBelongToFrameworkVersion::forPair((string) $version->getKey(), $phaseId);
    }
}
