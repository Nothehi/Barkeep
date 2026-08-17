<?php

use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\DesignPractice;
use Modules\DesignFramework\Domain\Models\DesignPrinciple;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * The five screens, and the props each of them is unusable without.
 *
 * These are not snapshot tests of a layout. What they hold is the contract between the controllers
 * and the pages: which collections arrive, that framework content and a studio's record stay in
 * separate ones, and that every choice the interface offers was worded by the server.
 */
beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->admin = User::factory()->create(['email' => 'author@barkeep.test']);
    config()->set('design-framework.administrators', ['author@barkeep.test']);

    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();

    $this->game = Game::factory()
        ->inWorkspace($this->workspace)
        ->withSlug('bears-and-bridges')
        ->active()
        ->create();

    $this->framework = Framework::factory()
        ->named('Board Game Design Framework')
        ->withSlug('bgdf')
        ->published()
        ->create();

    $this->version = FrameworkVersion::factory()->nextFor($this->framework)->published()->create();

    $this->phase = DesignPhaseDefinition::factory()
        ->inVersion($this->version)
        ->named('Core loop')
        ->create();
});

it('shows the catalogue of methodologies', function () {
    $this->actingAs($this->designer)
        ->get(route('frameworks.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('frameworks/index')
                ->has('frameworks.data', 1)
                ->where('frameworks.data.0.name', 'Board Game Design Framework')
                ->where('can.create', false)
                ->has('options.statuses'),
        );
});

/**
 * An installation with nobody configured to administer frameworks shows a read-only catalogue and
 * says why, which is more useful to whoever is setting Barkeep up than a missing button.
 */
it('tells the catalogue whether anybody can administer frameworks', function () {
    config()->set('design-framework.administrators', []);

    $this->actingAs($this->designer)
        ->get(route('frameworks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('administration_configured', false));

    config()->set('design-framework.administrators', ['author@barkeep.test']);

    $this->actingAs($this->designer)
        ->get(route('frameworks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('administration_configured', true));
});

it('offers the create button only to a framework administrator', function () {
    $this->actingAs($this->admin)
        ->get(route('frameworks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('can.create', true));
});

/**
 * A draft is a methodology still being written. Showing it to every designer on the platform would
 * invite adopting something whose questions are about to change.
 */
it('keeps draft methodologies out of the catalogue for ordinary designers', function () {
    Framework::factory()->named('Half written')->withSlug('half')->create();

    $this->actingAs($this->designer)
        ->get(route('frameworks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('frameworks.data', 1));

    $this->actingAs($this->admin)
        ->get(route('frameworks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('frameworks.data', 2));
});

it('shows one methodology and its editions', function () {
    $this->actingAs($this->designer)
        ->get(route('frameworks.show', 'bgdf'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('frameworks/show')
                ->where('framework.data.slug', 'bgdf')
                ->has('versions.data', 1)
                ->where('versions.data.0.version_number', 1),
        );
});

/**
 * Two modules address a `{version}`, and Laravel keeps one binder per parameter name — so the
 * provider registered last can take the name from the one registered first and 404 every screen on
 * the other chain.
 *
 * The failure is silent and total, and it is invisible to either module's own tests, so it is held
 * here: both kinds of version resolve in one run, or something has taken the name.
 */
it('resolves a framework edition and a game version under the same parameter name', function () {
    $gameVersion = GameVersion::factory()->nextFor($this->game)->create();

    $this->actingAs($this->admin)
        ->get(route('frameworks.versions.show', ['bgdf', 1]))
        ->assertOk();

    $this->actingAs($this->designer)
        ->get(route('games.versions.show', ['studio', 'bears-and-bridges', $gameVersion->version_number]))
        ->assertOk();
});

it('shows the builder for one edition', function () {
    DesignPrinciple::factory()->inPhase($this->phase)->create();
    DesignCriterion::factory()->inPhase($this->phase)->create();
    DesignPractice::factory()->inPhase($this->phase)->create();
    DesignPrompt::factory()->inPhase($this->phase)->create();
    Checklist::factory()->inPhase($this->phase)->create();

    $this->actingAs($this->admin)
        ->get(route('frameworks.versions.show', ['bgdf', 1]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('frameworks/builder')
                ->where('version.data.version_number', 1)
                ->has('phases.data', 1)
                ->has('principles.data', 1)
                ->has('criteria.data', 1)
                ->has('practices.data', 1)
                ->has('prompts.data', 1)
                ->has('checklists.data', 1),
        );
});

/**
 * The single fact the whole builder is arranged around. A published edition is frozen for
 * everybody, including the administrator who wrote it, and the client is told rather than left to
 * infer it from a status string.
 */
it('tells the builder that a published edition is frozen', function () {
    $this->actingAs($this->admin)
        ->get(route('frameworks.versions.show', ['bgdf', 1]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('version.data.is_editable', false));

    $draft = FrameworkVersion::factory()->nextFor($this->framework)->create();

    $this->actingAs($this->admin)
        ->get(route('frameworks.versions.show', ['bgdf', $draft->version_number]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('version.data.is_editable', true));
});

/**
 * An author sees their own unfinished content while the edition is still being written, because
 * they are the person writing it. Nobody else does.
 *
 * The visibility follows the edition rather than the account, which is why the check below is on a
 * draft: once an edition is published it is frozen for everybody, and a frozen edition has no draft
 * content anybody needs to see.
 */
it('shows draft content to its author while the edition is still being written', function () {
    $draft = FrameworkVersion::factory()->nextFor($this->framework)->create();
    $phase = DesignPhaseDefinition::factory()->inVersion($draft)->named('Ideation')->create();

    DesignCriterion::factory()->inPhase($phase)->draft()->create();

    $this->actingAs($this->admin)
        ->get(route('frameworks.versions.show', ['bgdf', $draft->version_number]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('criteria.data', 1));

    $this->actingAs($this->designer)
        ->get(route('frameworks.versions.show', ['bgdf', $draft->version_number]))
        ->assertNotFound();
});

/**
 * Draft content inside a published edition is invisible to everybody, its author included. That is
 * not a permission — it is what "frozen" means.
 */
it('hides draft content inside a published edition from everybody', function () {
    DesignCriterion::factory()->inPhase($this->phase)->draft()->create();

    $this->actingAs($this->admin)
        ->get(route('frameworks.versions.show', ['bgdf', 1]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('criteria.data', 0));
});

it('offers the catalogue to a game that follows nothing yet', function () {
    $this->actingAs($this->designer)
        ->get(route('games.framework.show', ['studio', 'bears-and-bridges']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('games/framework')
                ->where('adoption', null)
                ->where('progress', null)
                ->has('available.data', 1)
                ->where('can.assign', true),
        );
});

/**
 * The catalogue is only fetched when there is a choice to make. Sending it to a game that already
 * follows a methodology would invite a screen offering a switch the module does not implement.
 */
it('sends no catalogue to a game that already follows a framework', function () {
    GameFramework::factory()->forGame($this->game)->following($this->version)->create();

    $this->actingAs($this->designer)
        ->get(route('games.framework.show', ['studio', 'bears-and-bridges']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has('available.data', 0)
                ->where('adoption.data.framework_version_id', $this->version->id)
                ->has('progress.data')
                ->has('phases.data', 1),
        );
});

/**
 * The labels, the ordering and the scale itself have one definition, on the server. A client that
 * hard-coded them would be a second opinion waiting to go stale — and "not evaluated" is absent,
 * because it is not a grade anybody may choose.
 */
it('sends the grades a designer may choose, with what each one claims', function () {
    GameFramework::factory()->forGame($this->game)->following($this->version)->create();

    $this->actingAs($this->designer)
        ->get(route('games.framework.show', ['studio', 'bears-and-bridges']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has('options.ratings', 4)
                ->where('options.ratings.0.value', 'weak')
                ->has('options.ratings.0.description'),
        );
});

it('shows one phase with the content and the record side by side', function () {
    $adoption = GameFramework::factory()->forGame($this->game)->following($this->version)->create();

    DesignPrinciple::factory()->inPhase($this->phase)->create();
    $criterion = DesignCriterion::factory()->inPhase($this->phase)->create();
    DesignPractice::factory()->inPhase($this->phase)->create();
    DesignPrompt::factory()->inPhase($this->phase)->create();

    $checklist = Checklist::factory()->inPhase($this->phase)->create();
    ChecklistItem::factory()->inChecklist($checklist)->create();

    $this->actingAs($this->designer)
        ->post("/app/workspaces/studio/games/bears-and-bridges/framework/criteria/{$criterion->id}/evaluate", [
            'status' => 'good',
        ]);

    $this->actingAs($this->designer)
        ->get(route('games.framework.phases.show', ['studio', 'bears-and-bridges', 'core-loop']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('games/framework-phase')
                ->where('phase.data.slug', 'core-loop')
                ->has('principles.data', 1)
                ->has('criteria.data', 1)
                ->has('practices.data', 1)
                ->has('prompts.data', 1)
                ->has('checklists.data', 1)
                /*
                 * The studio's own state, in its own collection. The client joins it to the content
                 * above by id, which is what stops a criterion from ever carrying somebody's grade.
                 */
                ->has('evaluations.data', 1)
                ->where('evaluations.data.0.criterion_id', $criterion->id)
                ->where('adoption.data.id', $adoption->id),
        );
});

/**
 * The builder shows draft phases to their author; the working screen must not, because a game is
 * following a published edition and a draft phase inside it is not part of what it adopted.
 */
it('hides a draft phase from the working screen', function () {
    GameFramework::factory()->forGame($this->game)->following($this->version)->create();

    DesignPhaseDefinition::factory()->inVersion($this->version)->named('Unfinished')->draft()->create();

    $this->actingAs($this->designer)
        ->get(route('games.framework.phases.show', ['studio', 'bears-and-bridges', 'unfinished']))
        ->assertNotFound();
});

it('has no phase screen for a game that follows no framework', function () {
    $this->actingAs($this->designer)
        ->get(route('games.framework.phases.show', ['studio', 'bears-and-bridges', 'core-loop']))
        ->assertNotFound();
});

/**
 * An archived game keeps its framework screens and offers nothing new on them. The assessment done
 * on a shelved design a year ago is the reason to have kept it.
 */
it('keeps the framework readable on an archived game and closes it to writing', function () {
    GameFramework::factory()->forGame($this->game)->following($this->version)->create();

    $this->game->status = GameStatus::Archived;
    $this->game->save();

    $this->actingAs($this->designer)
        ->get(route('games.framework.phases.show', ['studio', 'bears-and-bridges', 'core-loop']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page->where('adoption.data.permissions.canRecordProgress', false),
        );
});
