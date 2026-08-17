<?php

namespace Modules\DesignFramework\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\DesignFramework\Application\DTOs\FrameworkFilters;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\Models\PhaseContent;
use Modules\DesignFramework\Domain\ValueObjects\ContentSlug;
use Modules\DesignFramework\Domain\ValueObjects\FrameworkSlug;
use Modules\DesignFramework\Domain\ValueObjects\FrameworkVersionNumber;
use Modules\DesignFramework\Infrastructure\Search\FrameworkSearchTerm;

/**
 * Every read the module performs against the framework side of its schema.
 *
 * Collecting them here is what makes two properties checkable rather than hopeful:
 *
 * - **drafts are never listed by accident.** Every method that could return a draft
 *   takes an explicit `$includeDrafts` flag. There is no default that quietly
 *   discloses work in progress, and the callers that pass `true` are the ones that
 *   asked the policy first.
 * - **content is only ever read through its version.** No method takes a bare
 *   content id, so there is no lookup that could return a criterion from a version
 *   the caller was not asking about.
 *
 * Nothing here authorizes. Resolving a record and deciding who may see it are
 * separate steps, and every caller runs a policy on the result; merging the two
 * would make it easy to forget the second half.
 */
final class FrameworkRepository
{
    /**
     * The frameworks somebody may see, newest first.
     *
     * Ordered by last change rather than alphabetically: the frameworks screen is a
     * working list for whoever is authoring them, and there will never be enough of
     * them for alphabetical order to help anybody find one.
     *
     * @return Collection<int, Framework>
     */
    public function listing(?FrameworkFilters $filters = null, bool $includeDrafts = false): Collection
    {
        $filters ??= FrameworkFilters::none();

        return Framework::query()
            ->when(
                ! $includeDrafts,
                fn (Builder $query) => $query->whereNot('status', FrameworkStatus::Draft),
            )
            ->when(
                $filters->status !== null,
                fn (Builder $query) => $query->where('status', $filters->status),
            )
            ->when(
                $filters->search !== null,
                fn (Builder $query) => $this->applySearch($query, (string) $filters->search),
            )
            ->with('latestVersion')
            ->withCount('versions')
            ->orderByDesc('updated_at')
            ->orderBy('name')
            ->get();
    }

    /**
     * Find a framework by address.
     */
    public function findBySlug(FrameworkSlug $slug): ?Framework
    {
        return Framework::query()->where('slug', $slug->value)->first();
    }

    /**
     * Determine whether an address is already in use.
     *
     * Framework addresses are globally unique, so this takes no scope. The
     * exception exists for renames, so a framework does not collide with itself.
     */
    public function slugExists(FrameworkSlug $slug, ?string $exceptId = null): bool
    {
        $query = Framework::query()->where('slug', $slug->value);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        return $query->exists();
    }

    /**
     * A framework's editions, oldest first.
     *
     * Oldest first because a version list is read as a history: v1, then v2, then
     * v3, with the newest at the bottom where it was added.
     *
     * @return Collection<int, FrameworkVersion>
     */
    public function versionsOf(Framework $framework, bool $includeDrafts = false): Collection
    {
        $versions = $framework->versions()
            ->when(
                ! $includeDrafts,
                fn (Builder $query) => $query->whereNot('status', FrameworkStatus::Draft),
            )
            ->withCount(['phases', 'adoptions'])
            ->get();

        return $this->withFramework($framework, $versions);
    }

    /**
     * Find one of a framework's editions by number.
     *
     * Scoped to the framework because that is how a version is identified at all on
     * a nested route: a version from another framework fails to resolve rather than
     * being caught later by a policy.
     */
    public function findVersion(Framework $framework, FrameworkVersionNumber $number): ?FrameworkVersion
    {
        $version = $framework->versions()
            ->where('version_number', $number->value)
            ->with('creator')
            ->first();

        return $version === null ? null : $version->setRelation('framework', $framework);
    }

    /**
     * The highest version number a framework has issued.
     *
     * Read under the row lock `CreateFrameworkVersion` takes, which is what makes
     * allocating the next number safe when two people press the button at once.
     */
    public function highestVersionNumber(Framework $framework): ?int
    {
        $highest = $framework->versions()->max('version_number');

        return $highest === null ? null : (int) $highest;
    }

    /**
     * How many games are following an edition.
     *
     * The number a framework author wants before publishing the next version, and
     * the number that explains why a version can never be deleted.
     */
    public function countAdoptionsOf(FrameworkVersion $version): int
    {
        return $version->adoptions()->count();
    }

    /**
     * A version's phases, in the order a designer works through them.
     *
     * @return Collection<int, DesignPhaseDefinition>
     */
    public function phasesOf(FrameworkVersion $version, bool $includeUnpublished = false): Collection
    {
        $phases = $version->phases()
            ->when(! $includeUnpublished, fn (Builder $query) => $query->visible())
            ->get();

        return $this->withVersion($version, $phases);
    }

    /**
     * Find one of a version's phases by address.
     */
    public function findPhase(FrameworkVersion $version, ContentSlug $slug): ?DesignPhaseDefinition
    {
        $phase = $version->phases()->where('slug', $slug->value)->first();

        return $phase === null ? null : $phase->setRelation('version', $version);
    }

    /**
     * Find one of a version's phases by id.
     *
     * The lookup behind filing content under a phase. A phase id arrives in a
     * request body rather than through a route binding, so scoping it to the version
     * here is what stops one version's criterion from being filed under another
     * version's phase.
     */
    public function findPhaseById(FrameworkVersion $version, string $phaseId): ?DesignPhaseDefinition
    {
        $phase = $version->phases()->whereKey($phaseId)->first();

        return $phase === null ? null : $phase->setRelation('version', $version);
    }

    /**
     * A version's content of one kind, ordered by phase and then by position.
     *
     * The phase-less content comes first, because it applies across the whole
     * methodology and reads as a preamble to the stages that follow. After that the
     * order is phase by phase, and within a phase by position — which is what makes
     * a single flat list renderable as a hierarchy without the client sorting
     * anything.
     *
     * The ordering is done with a left join rather than by loading the phases and
     * sorting in PHP, so a version with a thousand pieces of content still comes
     * back in one query and in one order. Written as a `CASE` rather than with
     * `NULLS FIRST`, because SQLite — the test database — does not support the
     * latter.
     *
     * @param  class-string<PhaseContent>  $type
     * @return Collection<int, PhaseContent>
     */
    public function contentOf(
        FrameworkVersion $version,
        string $type,
        bool $includeUnpublished = false,
    ): Collection {
        /** @var PhaseContent $prototype */
        $prototype = new $type;
        $table = $prototype->getTable();

        $content = $type::query()
            ->where("{$table}.framework_version_id", $version->getKey())
            ->when(! $includeUnpublished, fn (Builder $query) => $query->visible())
            ->leftJoin('design_phases', 'design_phases.id', '=', "{$table}.phase_id")
            ->orderByRaw('CASE WHEN design_phases.position IS NULL THEN 0 ELSE 1 END')
            ->orderBy('design_phases.position')
            ->orderBy("{$table}.position")
            ->orderBy("{$table}.created_at")
            ->select("{$table}.*")
            ->get();

        return $this->withVersion($version, $content);
    }

    /**
     * The content of one kind filed under one phase.
     *
     * @param  class-string<PhaseContent>  $type
     * @return Collection<int, PhaseContent>
     */
    public function contentInPhase(
        DesignPhaseDefinition $phase,
        string $type,
        bool $includeUnpublished = false,
    ): Collection {
        return $type::query()
            ->where('phase_id', $phase->getKey())
            ->when(! $includeUnpublished, fn (Builder $query) => $query->visible())
            ->ordered()
            ->get();
    }

    /**
     * A version's checklists, with their items loaded in order.
     *
     * The one content type read with its children, because a checklist is useless
     * without them — "prototype readiness" tells a designer nothing on its own.
     *
     * @return Collection<int, Checklist>
     */
    public function checklistsWithItems(
        FrameworkVersion $version,
        bool $includeUnpublished = false,
    ): Collection {
        $checklists = Checklist::query()
            ->where('checklists.framework_version_id', $version->getKey())
            ->when(! $includeUnpublished, fn (Builder $query) => $query->visible())
            ->leftJoin('design_phases', 'design_phases.id', '=', 'checklists.phase_id')
            ->orderByRaw('CASE WHEN design_phases.position IS NULL THEN 0 ELSE 1 END')
            ->orderBy('design_phases.position')
            ->orderBy('checklists.position')
            ->orderBy('checklists.created_at')
            ->select('checklists.*')
            ->with('items')
            ->get();

        return $this->withVersion($version, $checklists);
    }

    /**
     * The readiness gates filed under one phase, with their requirements.
     *
     * Typed for checklists rather than going through {@see contentInPhase()}, because the caller needs
     * the items and a collection of the generic content type could not offer them.
     *
     * @return Collection<int, Checklist>
     */
    public function checklistsInPhase(
        DesignPhaseDefinition $phase,
        bool $includeUnpublished = false,
    ): Collection {
        return Checklist::query()
            ->where('phase_id', $phase->getKey())
            ->when(! $includeUnpublished, fn (Builder $query) => $query->visible())
            ->with('items')
            ->ordered()
            ->get();
    }

    /**
     * Find one of a checklist's items by address.
     */
    public function findChecklistItem(Checklist $checklist, ContentSlug $slug): ?ChecklistItem
    {
        $item = $checklist->items()->where('slug', $slug->value)->first();

        return $item === null ? null : $item->setRelation('checklist', $checklist);
    }

    /**
     * The sibling set a phase is ordered within.
     *
     * Handed to `ContentSequencer`, which is the only thing that writes a position.
     * Returning the query rather than the rows is what lets the sequencer read them
     * inside its own transaction.
     *
     * @return Builder<DesignPhaseDefinition>
     */
    public function phaseSiblings(FrameworkVersion $version): Builder
    {
        return DesignPhaseDefinition::query()->where('framework_version_id', $version->getKey());
    }

    /**
     * The sibling set a piece of content is ordered within.
     *
     * Scoped by phase as well as by version, so content filed under no phase orders
     * independently of content filed under one. Two criteria in different phases
     * both being "position 1" is correct.
     *
     * @param  class-string<PhaseContent>  $type
     * @return Builder<PhaseContent>
     */
    public function contentSiblings(FrameworkVersion $version, string $type, ?string $phaseId): Builder
    {
        $query = $type::query()->where('framework_version_id', $version->getKey());

        return $phaseId === null
            ? $query->whereNull('phase_id')
            : $query->where('phase_id', $phaseId);
    }

    /**
     * The sibling set a checklist item is ordered within.
     *
     * @return Builder<ChecklistItem>
     */
    public function checklistItemSiblings(Checklist $checklist): Builder
    {
        return ChecklistItem::query()->where('checklist_id', $checklist->getKey());
    }

    /**
     * Narrow a frameworks query to those matching a search term.
     *
     * Case-insensitive by lowering both sides, which is correct on PostgreSQL and on
     * SQLite without depending on either's collation. The escape character is stated
     * explicitly because the two databases disagree about the default.
     *
     * @param  Builder<Framework>  $query
     */
    private function applySearch(Builder $query, string $term): void
    {
        $search = FrameworkSearchTerm::fromInput($term);

        if ($search === null) {
            return;
        }

        $pattern = $search->pattern();

        $query->where(function (Builder $query) use ($pattern): void {
            $query
                ->whereRaw('lower(name) like ? escape ?', [$pattern, FrameworkSearchTerm::ESCAPE])
                ->orWhereRaw('lower(description) like ? escape ?', [$pattern, FrameworkSearchTerm::ESCAPE]);
        });
    }

    /**
     * Hand every row the framework it came from.
     *
     * Saves a query per row on the way out, and — more importantly — means the whole
     * request shares one framework instance rather than each row lazily loading its
     * own and answering permission questions against a different object.
     *
     * @template TModel of Model
     *
     * @param  Collection<int, TModel>  $rows
     * @return Collection<int, TModel>
     */
    private function withFramework(Framework $framework, Collection $rows): Collection
    {
        return $rows->each(fn (Model $row) => $row->setRelation('framework', $framework));
    }

    /**
     * Hand every row the version it came from.
     *
     * @template TModel of Model
     *
     * @param  Collection<int, TModel>  $rows
     * @return Collection<int, TModel>
     */
    private function withVersion(FrameworkVersion $version, Collection $rows): Collection
    {
        return $rows->each(fn (Model $row) => $row->setRelation('version', $version));
    }
}
