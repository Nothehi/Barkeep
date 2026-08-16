<?php

use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestParticipant;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
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

    $this->playtest = Playtest::factory()
        ->forVersion($this->version)
        ->createdBy($this->designer)
        ->titled('First-player advantage')
        ->create();
});

it('shows the playtests of a game', function () {
    $this->actingAs($this->designer)
        ->get(route('playtests.index', ['studio', 'bears-and-bridges']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('playtests/index')
                ->has('playtests.data', 1)
                ->where('playtests.data.0.title', 'First-player advantage')
                ->where('can.create', true),
        );
});

/**
 * The version picker cannot be filled in from the client, so the versions the
 * caller may test have to arrive with the screen.
 */
it('sends the versions a playtest could be planned against', function () {
    $this->actingAs($this->designer)
        ->get(route('playtests.index', ['studio', 'bears-and-bridges']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('versions.data', 1));
});

/**
 * The labels, the ordering and the sets themselves have one definition, on the
 * server. A client that hard-coded them would be a second opinion waiting to
 * go stale.
 */
it('sends the choices the screens offer rather than making the client know them', function () {
    $this->actingAs($this->designer)
        ->get(route('playtests.index', ['studio', 'bears-and-bridges']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has('options.statuses', 4)
                ->has('options.categories', 8)
                ->has('options.roles', 4)
                ->has('options.rating_scale', 5),
        );
});

it('echoes back the filters the list is currently showing', function () {
    $this->actingAs($this->designer)
        ->get(route('playtests.index', ['studio', 'bears-and-bridges']).'?status=planned&search=advantage')
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('filters.status', 'planned')
                ->where('filters.search', 'advantage'),
        );
});

it('plans a playtest from the screens and lands on it', function () {
    $this->actingAs($this->designer)
        ->post(route('playtests.store', ['studio', 'bears-and-bridges']), [
            'game_version_id' => $this->version->id,
            'title' => 'Scoring clarity',
            'objective' => 'Find out whether the endgame scoring reads clearly.',
        ])
        ->assertRedirect();

    expect(Playtest::query()->where('title', 'Scoring clarity')->exists())->toBeTrue();
});

it('shows a playtest with its sessions and what they produced', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();
    PlaytestObservation::factory()->forSession($session)->count(2)->create();

    $this->actingAs($this->designer)
        ->get(route('playtests.show', ['studio', 'bears-and-bridges', $this->playtest]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('playtests/show')
                ->where('playtest.data.title', 'First-player advantage')
                ->has('sessions.data', 1)
                ->where('summary.data.observation_count', 2),
        );
});

it('shows a session with everything recorded in it', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->inProgress()->create();
    $participant = PlaytestParticipant::factory()->forSession($session)->guest('Sam')->create();

    PlaytestObservation::factory()->about($participant)->saying('Sam misread the market.')->create();
    PlaytestFeedback::factory()->from($participant)->saying('I never knew my best move.')->rated(3)->create();

    $this->actingAs($this->designer)
        ->get(route('playtests.sessions.show', ['studio', 'bears-and-bridges', $this->playtest, $session]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('playtests/session')
                ->has('participants.data', 1)
                ->has('observations.data', 1)
                ->has('feedback.data', 1)
                ->where('observations.data.0.content', 'Sam misread the market.')
                ->where('feedback.data.0.rating', 3),
        );
});

/**
 * The picker that lets somebody add a teammate by name rather than by pasting
 * an identifier. Only people who already share the workspace appear, because
 * linking an account discloses its name and address.
 */
it('offers the team as candidates on the session screen', function () {
    $teammate = User::factory()->create();
    $this->workspace->members()->create([
        'user_id' => $teammate->id,
        'role' => WorkspaceRole::Member,
        'joined_at' => now(),
    ]);

    User::factory()->create();

    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->create();

    $this->actingAs($this->designer)
        ->get(route('playtests.sessions.show', ['studio', 'bears-and-bridges', $this->playtest, $session]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('teammates.data', 2));
});

it('starts a session from the screens and stays on it', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->create();

    $this->actingAs($this->designer)
        ->from(route('playtests.sessions.show', ['studio', 'bears-and-bridges', $this->playtest, $session]))
        ->post(route('playtests.sessions.start', ['studio', 'bears-and-bridges', $this->playtest, $session]))
        ->assertRedirect(route('playtests.sessions.show', ['studio', 'bears-and-bridges', $this->playtest, $session]));

    expect($session->fresh()->started_at)->not->toBeNull();
});

it('records an observation from the screens', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->inProgress()->create();

    $this->actingAs($this->designer)
        ->from(route('playtests.sessions.show', ['studio', 'bears-and-bridges', $this->playtest, $session]))
        ->post(
            route('playtests.sessions.observations.store', ['studio', 'bears-and-bridges', $this->playtest, $session]),
            ['content' => 'The market was ignored for three rounds.', 'category' => 'player_behavior'],
        )
        ->assertRedirect();

    expect(PlaytestObservation::query()->count())->toBe(1);
});

/**
 * Landing on the session rather than back on the playtest is the point: the
 * reason somebody creates a session is almost always that they are about to
 * run one.
 */
it('scheduling a session lands on the session, because that is why you made it', function () {
    $this->actingAs($this->designer)
        ->post(route('playtests.sessions.store', ['studio', 'bears-and-bridges', $this->playtest]))
        ->assertRedirect();

    $session = PlaytestSession::query()->sole();

    $this->actingAs($this->designer)
        ->post(route('playtests.sessions.store', ['studio', 'bears-and-bridges', $this->playtest]))
        ->assertRedirect(route('playtests.sessions.show', [
            'studio',
            'bears-and-bridges',
            $this->playtest,
            PlaytestSession::query()->whereKeyNot($session->id)->sole(),
        ]));
});

/**
 * Reported next to the version picker rather than as a toast, because that is
 * the field the caller has to change.
 */
it('reports a mismatched version against the field that caused it', function () {
    $otherGame = Game::factory()->inWorkspace($this->workspace)->withSlug('other-game')->active()->create();
    $theirs = GameVersion::factory()->nextFor($otherGame)->create();

    $this->actingAs($this->designer)
        ->post(route('playtests.store', ['studio', 'bears-and-bridges']), [
            'game_version_id' => $theirs->id,
            'title' => 'Wrong version',
            'objective' => 'Find out something about the wrong game entirely.',
        ])
        ->assertSessionHasErrors('game_version_id');
});

it('hides another studio\'s playtest screens', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->get(route('playtests.index', ['studio', 'bears-and-bridges']))
        ->assertNotFound();

    $this->actingAs($outsider)
        ->get(route('playtests.show', ['studio', 'bears-and-bridges', $this->playtest]))
        ->assertNotFound();
});
