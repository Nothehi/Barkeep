<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Enums\PlaytestSessionStatus;
use Modules\Playtesting\Domain\Enums\PlaytestStatus;
use Modules\Playtesting\Domain\Events\PlaytestSessionCompleted;
use Modules\Playtesting\Domain\Events\PlaytestSessionStarted;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestParticipant;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Workspace\Domain\Models\Workspace;

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();

    $this->game = Game::factory()
        ->inWorkspace($this->workspace)
        ->withSlug('bears-and-bridges')
        ->active()
        ->create();

    $this->playtest = Playtest::factory()->forGame($this->game)->createdBy($this->designer)->create();

    $this->playtestUrl = "/api/v1/workspaces/studio/games/bears-and-bridges/playtests/{$this->playtest->id}";
});

function sessionUrl(PlaytestSession $session): string
{
    return "/api/v1/workspaces/studio/games/bears-and-bridges/playtests/{$session->playtest_id}/sessions/{$session->id}";
}

it('schedules a sitting with nothing filled in', function () {
    test()->actingAs(test()->designer)
        ->postJson("{$this->playtestUrl}/sessions")
        ->assertCreated()
        ->assertJsonPath('data.status', PlaytestSessionStatus::Planned->value)
        ->assertJsonPath('data.started_at', null)
        ->assertJsonPath('data.ended_at', null);
});

it('records where a session is happening and what was planned', function () {
    $this->actingAs($this->designer)
        ->postJson("{$this->playtestUrl}/sessions", [
            'location' => 'Dice & Slice',
            'planned_at' => '2026-09-02T19:00:00+00:00',
            'notes' => 'Four players confirmed.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.location', 'Dice & Slice')
        ->assertJsonPath('data.notes', 'Four players confirmed.');
});

it('ignores a start time supplied by the client', function () {
    $this->actingAs($this->designer)
        ->postJson("{$this->playtestUrl}/sessions", ['started_at' => '1999-01-01T00:00:00+00:00'])
        ->assertCreated()
        ->assertJsonPath('data.started_at', null);
});

it('starts a session and stamps it from the clock', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->create();

    $this->actingAs($this->designer)
        ->postJson(sessionUrl($session).'/start')
        ->assertOk()
        ->assertJsonPath('data.status', PlaytestSessionStatus::InProgress->value);

    expect($session->fresh()->started_at)->not->toBeNull();
});

/**
 * The one place acting on a session changes its parent, and it earns that: an
 * investigation whose first sitting has begun *is* under way.
 */
it('puts the playtest in progress when its first session starts', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->create();

    expect($this->playtest->status)->toBe(PlaytestStatus::Planned);

    $this->actingAs($this->designer)->postJson(sessionUrl($session).'/start')->assertOk();

    expect($this->playtest->fresh()->status)->toBe(PlaytestStatus::InProgress);
});

it('leaves an already running playtest alone when a later session starts', function () {
    $first = PlaytestSession::factory()->forPlaytest($this->playtest)->create();
    $second = PlaytestSession::factory()->forPlaytest($this->playtest)->create();

    $this->actingAs($this->designer)->postJson(sessionUrl($first).'/start')->assertOk();
    $this->actingAs($this->designer)->postJson(sessionUrl($second).'/start')->assertOk();

    expect($this->playtest->fresh()->status)->toBe(PlaytestStatus::InProgress);
});

it('does not drag a completed playtest back into progress', function () {
    $completed = Playtest::factory()->forGame($this->game)->completed()->create();
    $session = PlaytestSession::factory()->forPlaytest($completed)->create();

    $this->actingAs($this->designer)
        ->postJson(sessionUrl($session).'/start')
        ->assertForbidden();

    expect($completed->fresh()->status)->toBe(PlaytestStatus::Completed);
});

/**
 * What a lost race looks like from the losing side. Two facilitators press
 * start; the second is told it is already running rather than overwriting the
 * first one's timestamp and shortening the session.
 *
 * A conflict rather than a refusal, and the distinction is the module's whole
 * division of labour: the policy said they may act on this session, and the
 * transition matrix said this particular move is not available from here.
 */
it('refuses to start a session that is already running', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->inProgress()->create();

    $before = $session->started_at;

    $this->actingAs($this->designer)
        ->postJson(sessionUrl($session).'/start')
        ->assertStatus(409);

    expect($session->fresh()->started_at?->getTimestamp())->toBe($before?->getTimestamp());
});

it('ends a running session and records what it settled', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->inProgress()->create();

    $this->actingAs($this->designer)
        ->postJson(sessionUrl($session).'/complete', ['outcome' => 'The hypothesis held.'])
        ->assertOk()
        ->assertJsonPath('data.status', PlaytestSessionStatus::Completed->value)
        ->assertJsonPath('data.outcome', 'The hypothesis held.');

    expect($session->fresh()->ended_at)->not->toBeNull();
});

it('reports how long a session actually ran', function () {
    $session = PlaytestSession::factory()
        ->forPlaytest($this->playtest)
        ->inProgress(now()->subMinutes(75))
        ->create();

    $this->actingAs($this->designer)
        ->postJson(sessionUrl($session).'/complete')
        ->assertOk()
        ->assertJsonPath('data.duration_label', '1h 15m');
});

/**
 * "Completed" asserts the game was played. The timestamps that make a session
 * useful as evidence only exist because somebody started it.
 */
it('refuses to complete a session that never started', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->create();

    $this->actingAs($this->designer)
        ->postJson(sessionUrl($session).'/complete')
        ->assertStatus(409);

    expect($session->fresh()->status)->toBe(PlaytestSessionStatus::Planned);
});

it('keeps notes typed during the session when the closing dialog sends none', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->inProgress()->create();

    $this->actingAs($this->designer)
        ->patchJson(sessionUrl($session), ['notes' => 'Players struggled with scoring.'])
        ->assertOk();

    $this->actingAs($this->designer)
        ->postJson(sessionUrl($session).'/complete', ['outcome' => 'Scoring needs work.'])
        ->assertOk()
        ->assertJsonPath('data.notes', 'Players struggled with scoring.');
});

it('cancels a session before anybody sits down', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->create();

    $this->actingAs($this->designer)
        ->postJson(sessionUrl($session).'/cancel')
        ->assertOk()
        ->assertJsonPath('data.status', PlaytestSessionStatus::Cancelled->value)
        ->assertJsonPath('data.started_at', null);
});

it('cancels a session abandoned halfway, keeping the start time', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->inProgress()->create();

    $this->actingAs($this->designer)
        ->postJson(sessionUrl($session).'/cancel')
        ->assertOk()
        ->assertJsonPath('data.status', PlaytestSessionStatus::Cancelled->value);

    expect($session->fresh()->started_at)->not->toBeNull();
});

/**
 * A finished sitting must not be reclassifiable and quietly dropped out of
 * the evidence.
 */
it('refuses to cancel a completed session', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();

    $this->actingAs($this->designer)
        ->postJson(sessionUrl($session).'/cancel')
        ->assertForbidden();
});

it('refuses to restart a completed session', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();

    $this->actingAs($this->designer)
        ->postJson(sessionUrl($session).'/start')
        ->assertForbidden();
});

it('lists a playtest\'s sittings in the order they were planned', function () {
    PlaytestSession::factory()->forPlaytest($this->playtest)->at('Second')->create(['planned_at' => now()->addDay()]);
    PlaytestSession::factory()->forPlaytest($this->playtest)->at('First')->create(['planned_at' => now()]);

    $this->actingAs($this->designer)
        ->getJson("{$this->playtestUrl}/sessions")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.location', 'First')
        ->assertJsonPath('data.1.location', 'Second');
});

it('announces a start with the timestamp that was written', function () {
    Event::fake([PlaytestSessionStarted::class]);

    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->create();

    $this->actingAs($this->designer)->postJson(sessionUrl($session).'/start')->assertOk();

    Event::assertDispatched(
        PlaytestSessionStarted::class,
        fn (PlaytestSessionStarted $event) => $event->sessionId === $session->id
            && $event->gameId === $this->game->id
            && $event->startedAt->getTimestamp() === $session->fresh()->started_at?->getTimestamp(),
    );
});

it('announces completion with what the session produced', function () {
    Event::fake([PlaytestSessionCompleted::class]);

    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->inProgress()->create();

    PlaytestParticipant::factory()->forSession($session)->count(4)->create();
    PlaytestObservation::factory()->forSession($session)->count(7)->create();
    PlaytestFeedback::factory()->forSession($session)->count(2)->create();

    $this->actingAs($this->designer)->postJson(sessionUrl($session).'/complete')->assertOk();

    Event::assertDispatched(
        PlaytestSessionCompleted::class,
        fn (PlaytestSessionCompleted $event) => $event->participantCount === 4
            && $event->observationCount === 7
            && $event->feedbackCount === 2
            && $event->durationSeconds !== null,
    );
});

it('does not find a session belonging to another playtest', function () {
    $otherPlaytest = Playtest::factory()->forGame($this->game)->create();
    $theirSession = PlaytestSession::factory()->forPlaytest($otherPlaytest)->create();

    $this->actingAs($this->designer)
        ->getJson("{$this->playtestUrl}/sessions/{$theirSession->id}")
        ->assertNotFound();
});

it('does not choke on a session id that is not a uuid', function () {
    $this->actingAs($this->designer)
        ->getJson("{$this->playtestUrl}/sessions/latest")
        ->assertNotFound();
});
