<?php

use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\PrototypeIteration\Domain\Models\DecisionEvidence;
use Modules\PrototypeIteration\Domain\Models\DesignChange;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\IterationPlaytest;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeArtifact;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\Workspace\Domain\Models\Workspace;

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();

    $this->game = Game::factory()
        ->inWorkspace($this->workspace)
        ->withSlug('bears-and-bridges')
        ->active()
        ->create();

    $this->version = GameVersion::factory()->nextFor($this->game)->create();

    $this->prototype = Prototype::factory()
        ->forVersion($this->version)
        ->createdBy($this->designer)
        ->named('Core Combat')
        ->create();

    $this->build = PrototypeVersion::factory()->nextFor($this->prototype)->create();
});

it('shows the prototypes of a game', function () {
    $this->actingAs($this->designer)
        ->get(route('prototypes.index', ['studio', 'bears-and-bridges']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('prototypes/index')
                ->has('prototypes.data', 1)
                ->where('prototypes.data.0.name', 'Core Combat')
                ->where('can.create', true)
                ->has('versions.data', 1)
                ->has('options.types')
                ->has('options.statuses')
                ->has('filters'),
        );
});

/**
 * The vocabulary comes from the server so that the labels, the ordering and the sets themselves have one
 * definition — a client that hard-coded them would be a second opinion waiting to go stale.
 */
it('sends the prototype vocabulary already worded', function () {
    $this->actingAs($this->designer)
        ->get(route('prototypes.index', ['studio', 'bears-and-bridges']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('options.types.0.value', 'paper')
                ->where('options.types.0.label', 'Paper')
                ->has('options.types.0.description')
                ->has('options.artifact_types'),
        );
});

it('shows a prototype and its versions', function () {
    $this->actingAs($this->designer)
        ->get(route('prototypes.show', ['studio', 'bears-and-bridges', $this->prototype]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('prototypes/show')
                ->where('prototype.data.name', 'Core Combat')
                ->has('versions.data', 1)
                ->where('versions.data.0.version_number', 1)
                ->has('prototype.data.permissions'),
        );
});

it('shows one state of a prototype and its files', function () {
    PrototypeArtifact::factory()->forVersion($this->build)->create(['name' => 'card-fronts.pdf']);

    $this->actingAs($this->designer)
        ->get(route('prototypes.versions.show', [
            'studio',
            'bears-and-bridges',
            $this->prototype,
            $this->build->version_number,
        ]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('prototypes/version')
                ->where('version.data.label', 'v1')
                ->has('artifacts.data', 1)
                ->where('artifacts.data.0.name', 'card-fronts.pdf')
                ->missing('artifacts.data.0.storage_reference'),
        );
});

it('addresses a prototype state by its number in the URL', function () {
    $this->actingAs($this->designer)
        ->get("/app/workspaces/studio/games/bears-and-bridges/prototypes/{$this->prototype->id}/versions/1")
        ->assertOk();
});

it('shows the design cycles of a game', function () {
    Iteration::factory()->forPrototypeVersion($this->build)->titled('Improve combat pacing')->create();

    $this->actingAs($this->designer)
        ->get(route('iterations.index', ['studio', 'bears-and-bridges']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('iterations/index')
                ->has('iterations.data', 1)
                ->where('iterations.data.0.title', 'Improve combat pacing')
                ->has('prototype_versions.data', 1)
                ->where('can.create', true)
                ->has('options.outcomes')
                ->has('options.change_categories'),
        );
});

/**
 * The whole cycle in one response. A design cycle is read as a whole — what we changed, what we tested, what
 * we decided — and a page that filled in section by section would be unreadable for the second it took.
 */
it('shows a design cycle with everything it consists of', function () {
    $iteration = Iteration::factory()
        ->forPrototypeVersion($this->build)
        ->inProgress()
        ->titled('Improve combat pacing')
        ->create(['created_by' => $this->designer->id]);

    DesignChange::factory()->forIteration($iteration)->count(2)->create();
    DesignExperiment::factory()->forIteration($iteration)->create();
    DesignDecision::factory()->forIteration($iteration)->create();

    $playtest = Playtest::factory()->forGame($this->game)->create();
    IterationPlaytest::factory()->forIteration($iteration)->forPlaytest($playtest->id)->create();

    $this->actingAs($this->designer)
        ->get(route('iterations.show', ['studio', 'bears-and-bridges', $iteration]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('iterations/show')
                ->where('iteration.data.title', 'Improve combat pacing')
                ->has('changes.data', 2)
                ->has('experiments.data', 1)
                ->has('decisions.data', 1)
                ->has('playtests.data', 1)
                ->has('summary.data')
                ->has('timeline.data')
                ->has('evidence')
                ->has('available_playtests')
                ->has('options'),
        );
});

/**
 * Section 46: the playtest picker is populated by the server through this module's Playtesting adapter, so the
 * client never talks to Playtesting itself.
 */
it('offers the game\'s playtests to attach, from the server', function () {
    $iteration = Iteration::factory()->forPrototypeVersion($this->build)->inProgress()->create();
    Playtest::factory()->forGame($this->game)->titled('Four-player combat')->create();

    $this->actingAs($this->designer)
        ->get(route('iterations.show', ['studio', 'bears-and-bridges', $iteration]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has('available_playtests', 1)
                ->where('available_playtests.0.title', 'Four-player combat'),
        );
});

/**
 * The evidence panel shows the cited record's own words, read live from Playtesting at render time.
 */
it('resolves each decision\'s citations for the screen', function () {
    $iteration = Iteration::factory()->forPrototypeVersion($this->build)->inProgress()->create();
    $decision = DesignDecision::factory()->forIteration($iteration)->create();

    DecisionEvidence::factory()
        ->forDecision($decision)
        ->create(['description' => 'Marco\'s group said the same thing.']);

    $this->actingAs($this->designer)
        ->get(route('iterations.show', ['studio', 'bears-and-bridges', $iteration]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has("evidence.{$decision->id}", 1)
                ->where("evidence.{$decision->id}.0.description", 'Marco\'s group said the same thing.')
                ->where("evidence.{$decision->id}.0.is_resolved", true),
        );
});

/**
 * Both pickers the "plan an iteration" dialog needs have to reach the *list* screen, because that is where a
 * cycle is planned from — there is no iteration to open yet. Sending only one of them leaves the dialog
 * permanently showing its "you need a version first" empty state on a game that has both.
 */
it('sends both pickers to the iterations list, where the plan dialog lives', function () {
    $this->actingAs($this->designer)
        ->get(route('iterations.index', ['studio', 'bears-and-bridges']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has('versions.data', 1)
                ->where('versions.data.0.id', $this->version->id)
                ->has('prototype_versions.data', 1)
                ->where('prototype_versions.data.0.id', $this->build->id),
        );
});

it('narrows the prototypes list from the query string', function () {
    Prototype::factory()->forGame($this->game)->named('Hex tile draft')->create();

    $this->actingAs($this->designer)
        ->get(route('prototypes.index', ['studio', 'bears-and-bridges', 'search' => 'hex']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has('prototypes.data', 1)
                ->where('prototypes.data.0.name', 'Hex tile draft')
                ->where('filters.search', 'hex'),
        );
});

it('tells a reader who cannot record work that they cannot', function () {
    $this->game->forceFill(['status' => GameStatus::Archived])->save();

    $this->actingAs($this->designer)
        ->get(route('prototypes.index', ['studio', 'bears-and-bridges']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('can.create', false));
});

it('hides another studio\'s screens entirely', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->get(route('prototypes.index', ['studio', 'bears-and-bridges']))
        ->assertNotFound();

    $this->actingAs($outsider)
        ->get(route('iterations.index', ['studio', 'bears-and-bridges']))
        ->assertNotFound();
});

it('sends unauthenticated visitors to sign in', function () {
    $this->get(route('prototypes.index', ['studio', 'bears-and-bridges']))
        ->assertRedirect(route('login'));
});
