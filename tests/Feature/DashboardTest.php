<?php

use Modules\GameDesign\Domain\Enums\DesignPhase;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Enums\PlaytestStatus;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceMember;

/**
 * Sign in and choose a workspace, which is what every request below assumes.
 */
function workingIn(User $user, Workspace $workspace): void
{
    test()->actingAs($user)->post(route('workspaces.activate', $workspace->slug));
}

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

/**
 * The dashboard is about the workspace being worked in, so reaching it takes
 * both a session and a chosen workspace. Choosing one is the step signing in
 * lands on — see tests/Feature/Workspace/ActiveWorkspaceTest.php.
 */
test('authenticated users can visit the dashboard once they have chosen a workspace', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->ownedBy($user)->withSlug('studio')->create();

    workingIn($user, $workspace);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('dashboard')
                ->where('workspace.data.slug', 'studio'),
        );
});

test('authenticated users without a chosen workspace are asked to choose one', function () {
    $user = User::factory()->create();
    Workspace::factory()->ownedBy($user)->withSlug('studio')->create();

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertRedirect(route('workspaces.select'));
});

/**
 * The one number a workspace with nothing in it should report is nought, and
 * it should report it rather than omitting the section — a screen that
 * disappears when it has nothing to say cannot be relied on to say anything.
 */
test('an empty workspace reports zeroes rather than nothing', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->ownedBy($user)->withSlug('studio')->create();

    workingIn($user, $workspace);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('games.total', 0)
                ->where('games.versions_count', 0)
                ->has('games.recent.data', 0)
                ->where('playtesting.total', 0)
                ->where('playtesting.sessions_count', 0)
                ->has('playtesting.recent.data', 0)
                ->where('iteration.prototypes_count', 0)
                ->where('iteration.iterations_count', 0)
                ->where('iteration.open_iterations_count', 0),
        );
});

test('it counts the games in the workspace and the versions cut from them', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->ownedBy($user)->withSlug('studio')->create();

    $game = Game::factory()->inWorkspace($workspace)->active()->create();
    Game::factory()->inWorkspace($workspace)->create();

    Playtest::factory()->forGame($game)->createdBy($user)->create();

    workingIn($user, $workspace);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('games.total', 2)
                ->where('games.versions_count', 1)
                ->has('games.recent.data', 2),
        );
});

/**
 * The whole point of the screen. A playtest belongs to a game and a game to a
 * workspace, so gathering the studio's investigations means following that
 * chain up rather than trusting a column — and following it up is exactly
 * where a leak between two studios would appear.
 */
test('it gathers playtests from every game in the workspace and no others', function () {
    $designer = User::factory()->create();
    $mine = Workspace::factory()->ownedBy($designer)->withSlug('studio')->create();

    $first = Game::factory()->inWorkspace($mine)->createdBy($designer)->active()->create();
    $second = Game::factory()->inWorkspace($mine)->createdBy($designer)->active()->create();

    Playtest::factory()->forGame($first)->createdBy($designer)->titled('Opening stalls')->create();
    $tested = Playtest::factory()->forGame($second)->createdBy($designer)->inProgress()->create();

    PlaytestSession::factory()->forPlaytest($tested)->createdBy($designer)->create();

    $stranger = User::factory()->create();
    $theirs = Workspace::factory()->ownedBy($stranger)->withSlug('rivals')->create();
    $theirGame = Game::factory()->inWorkspace($theirs)->createdBy($stranger)->active()->create();
    Playtest::factory()->forGame($theirGame)->createdBy($stranger)->create();

    workingIn($designer, $mine);

    $this->actingAs($designer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('playtesting.total', 2)
                ->where('playtesting.sessions_count', 1)
                ->has('playtesting.recent.data', 2),
        );
});

/**
 * A playtest's address is nested under its game, so a row on a studio-wide
 * list is unreachable without it. This is the assertion that stops the link
 * from being built out of the wrong slug.
 */
test('every playtest on the dashboard carries the game it belongs to', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->ownedBy($user)->withSlug('studio')->create();

    $game = Game::factory()
        ->inWorkspace($workspace)
        ->createdBy($user)
        ->withSlug('bears-and-bridges')
        ->named('Bears and Bridges')
        ->active()
        ->create();

    Playtest::factory()->forGame($game)->createdBy($user)->create();

    workingIn($user, $workspace);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('playtesting.recent.data.0.game.slug', 'bears-and-bridges')
                ->where('playtesting.recent.data.0.game.name', 'Bears and Bridges'),
        );
});

test('it counts the prototypes and the cycles run against them', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->ownedBy($user)->withSlug('studio')->create();

    $game = Game::factory()->inWorkspace($workspace)->createdBy($user)->active()->create();

    Prototype::factory()->forGame($game)->createdBy($user)->create();

    Iteration::factory()->forGame($game)->createdBy($user)->create();
    Iteration::factory()->forGame($game)->createdBy($user)->completed()->create();

    workingIn($user, $workspace);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                /**
                 * Three rather than one: `Iteration::factory()->forGame()`
                 * builds the prototype each cycle needs, which is the shape a
                 * command would have produced.
                 */
                ->where('iteration.prototypes_count', 3)
                ->where('iteration.iterations_count', 2)
                /**
                 * A completed cycle is closed; only the planned one is still
                 * work somebody is expected to come back to.
                 */
                ->where('iteration.open_iterations_count', 1),
        );
});

/**
 * The labels, the ordering and the sets themselves have one definition, on the
 * server. A client that hard-coded them would be a second opinion waiting to
 * go stale — and a distribution missing the phases nothing has reached cannot
 * be drawn at all.
 */
test('it sends complete distributions rather than making the client know the enums', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->ownedBy($user)->withSlug('studio')->create();

    Game::factory()->inWorkspace($workspace)->inPhase(DesignPhase::Prototyping)->create();

    workingIn($user, $workspace);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has('games.by_status', count(GameStatus::cases()))
                ->has('games.by_design_phase', DesignPhase::count())
                ->has('playtesting.by_status', count(PlaytestStatus::cases()))
                ->where('games.by_design_phase.0.value', DesignPhase::Idea->value)
                ->where('games.by_design_phase.0.count', 0)
                ->where('games.by_design_phase.3.value', DesignPhase::Prototyping->value)
                ->where('games.by_design_phase.3.count', 1),
        );
});

/**
 * Somebody who has left the workspace they were working in is sent back to the
 * chooser rather than shown a studio they no longer belong to. The middleware
 * catches this first; the controller re-checks anyway, because the session's
 * copy of the choice carries no authority.
 */
test('a workspace the account no longer belongs to sends it back to the chooser', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $workspace = Workspace::factory()->ownedBy($owner)->withSlug('studio')->create();
    WorkspaceMember::factory()->inWorkspace($workspace)->forUser($member)->create();

    Workspace::factory()->ownedBy($member)->withSlug('elsewhere')->create();

    workingIn($member, $workspace);

    $this->actingAs($member)
        ->post(route('workspaces.leave', $workspace->slug))
        ->assertRedirect(route('workspaces.index'));

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertRedirect(route('workspaces.select'));
});
