<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Enums\ObservationCategory;
use Modules\Playtesting\Domain\Events\ObservationCreated;
use Modules\Playtesting\Domain\Models\Playtest;
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
    $this->session = PlaytestSession::factory()->forPlaytest($this->playtest)->inProgress()->create();

    $this->url = "/api/v1/workspaces/studio/games/bears-and-bridges/playtests/{$this->playtest->id}"
        ."/sessions/{$this->session->id}/observations";
});

/**
 * The shape this endpoint is designed around: one sentence, typed with one
 * hand while the game carries on with the other.
 */
it('records an observation from nothing but a sentence', function () {
    $this->actingAs($this->designer)
        ->postJson($this->url, ['content' => 'Player misunderstood the scoring rule.'])
        ->assertCreated()
        ->assertJsonPath('data.content', 'Player misunderstood the scoring rule.')
        ->assertJsonPath('data.category', ObservationCategory::Other->value)
        ->assertJsonPath('data.participant_id', null)
        ->assertJsonPath('data.created_by', $this->designer->id);
});

it('files an observation under any of the categories', function (string $category) {
    $this->actingAs($this->designer)
        ->postJson($this->url, ['content' => 'Something happened.', 'category' => $category])
        ->assertCreated()
        ->assertJsonPath('data.category', $category);
})->with(['rules', 'gameplay', 'player_behavior', 'balance', 'ux', 'pacing', 'components', 'other']);

it('refuses a category that is not in the taxonomy', function () {
    $this->actingAs($this->designer)
        ->postJson($this->url, ['content' => 'Something happened.', 'category' => 'vibes'])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('category');
});

it('attributes an observation to somebody at the table', function () {
    $participant = PlaytestParticipant::factory()->forSession($this->session)->guest('Sam')->create();

    $this->actingAs($this->designer)
        ->postJson($this->url, [
            'content' => 'Sam never read the reference card.',
            'participant_id' => $participant->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.participant_id', $participant->id)
        ->assertJsonPath('data.participant.display_name', 'Sam');
});

/**
 * The one identifier in the module that arrives without a route binding to
 * scope it. Attributing one session's observation to another session's
 * participant would produce a record that reads perfectly and is false.
 */
it('refuses to attribute an observation to another session\'s participant', function () {
    $other = PlaytestSession::factory()->forPlaytest($this->playtest)->inProgress()->create();
    $theirs = PlaytestParticipant::factory()->forSession($other)->guest('Not here')->create();

    $this->actingAs($this->designer)
        ->postJson($this->url, ['content' => 'Something.', 'participant_id' => $theirs->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('participant_id');

    expect(PlaytestObservation::query()->count())->toBe(0);
});

it('stamps an observation made during a running session', function () {
    $this->actingAs($this->designer)
        ->postJson($this->url, ['content' => 'The market was ignored.'])
        ->assertCreated();

    expect(PlaytestObservation::query()->sole()->observed_at)->not->toBeNull();
});

/**
 * Half of all observations are written up afterwards from memory. Demanding a
 * moment for those would produce invented timestamps, which is worse than none
 * — a timeline would then order things by a fiction.
 */
it('leaves an observation on a session nobody started undated', function () {
    $planned = PlaytestSession::factory()->forPlaytest($this->playtest)->create();

    $this->actingAs($this->designer)
        ->postJson(
            "/api/v1/workspaces/studio/games/bears-and-bridges/playtests/{$this->playtest->id}"
            ."/sessions/{$planned->id}/observations",
            ['content' => 'Thought of this in advance.'],
        )
        ->assertCreated()
        ->assertJsonPath('data.observed_at', null);
});

it('accepts a moment the designer supplies', function () {
    $this->actingAs($this->designer)
        ->postJson($this->url, [
            'content' => 'Game stalled after round five.',
            'observed_at' => '2026-08-16T20:14:00+00:00',
        ])
        ->assertCreated();

    expect(PlaytestObservation::query()->sole()->observed_at?->format('H:i'))->toBe('20:14');
});

/**
 * An undated observation still has somewhere to sit on the timeline, so it
 * does not drop out of the account entirely.
 */
it('falls back to when an undated observation was written down', function () {
    $planned = PlaytestSession::factory()->forPlaytest($this->playtest)->create();

    $this->actingAs($this->designer)
        ->postJson(
            "/api/v1/workspaces/studio/games/bears-and-bridges/playtests/{$this->playtest->id}"
            ."/sessions/{$planned->id}/observations",
            ['content' => 'Written up later.'],
        )
        ->assertCreated()
        ->assertJsonPath('data.observed_at', null);

    expect(PlaytestObservation::query()->sole()->occurredAt())->not->toBeNull();
});

it('requires something to actually be written', function () {
    $this->actingAs($this->designer)
        ->postJson($this->url, ['content' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('content');
});

it('lists what was noticed in the order it was noticed', function () {
    PlaytestObservation::factory()->forSession($this->session)
        ->saying('Second')->observedAt(now()->subMinutes(10))->create();
    PlaytestObservation::factory()->forSession($this->session)
        ->saying('First')->observedAt(now()->subMinutes(30))->create();

    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.content', 'First')
        ->assertJsonPath('data.1.content', 'Second');
});

it('corrects an observation while the session is still open', function () {
    $observation = PlaytestObservation::factory()->forSession($this->session)->saying('Typo here')->create();

    $this->actingAs($this->designer)
        ->patchJson("{$this->url}/{$observation->id}", [
            'content' => 'Players argued about the trade rule.',
            'category' => ObservationCategory::Rules->value,
        ])
        ->assertOk()
        ->assertJsonPath('data.content', 'Players argued about the trade rule.')
        ->assertJsonPath('data.category', 'rules');
});

it('withdraws an observation while the session is still open', function () {
    $observation = PlaytestObservation::factory()->forSession($this->session)->create();

    $this->actingAs($this->designer)
        ->deleteJson("{$this->url}/{$observation->id}")
        ->assertNoContent();

    expect(PlaytestObservation::query()->count())->toBe(0);
});

/**
 * The boundary that makes a completed session trustworthy: a reader knows they
 * are seeing everything that was noticed, not only the parts somebody still
 * agreed with afterwards.
 */
it('will not add, change or remove anything once the session has ended', function (string $method) {
    $ended = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();
    $observation = PlaytestObservation::factory()->forSession($ended)->create();

    $base = "/api/v1/workspaces/studio/games/bears-and-bridges/playtests/{$this->playtest->id}"
        ."/sessions/{$ended->id}/observations";

    $response = match ($method) {
        'create' => $this->actingAs($this->designer)->postJson($base, ['content' => 'One more thing.']),
        'update' => $this->actingAs($this->designer)->patchJson("{$base}/{$observation->id}", ['content' => 'Actually...']),
        'delete' => $this->actingAs($this->designer)->deleteJson("{$base}/{$observation->id}"),
    };

    $response->assertForbidden();
})->with(['create', 'update', 'delete']);

it('does not find an observation belonging to another session', function () {
    $other = PlaytestSession::factory()->forPlaytest($this->playtest)->inProgress()->create();
    $theirs = PlaytestObservation::factory()->forSession($other)->create();

    $this->actingAs($this->designer)
        ->deleteJson("{$this->url}/{$theirs->id}")
        ->assertNotFound();
});

it('announces an observation with the one part of it that is machine readable', function () {
    Event::fake([ObservationCreated::class]);

    $this->actingAs($this->designer)
        ->postJson($this->url, ['content' => 'Turn order confused everybody.', 'category' => 'rules'])
        ->assertCreated();

    Event::assertDispatched(
        ObservationCreated::class,
        fn (ObservationCreated $event) => $event->sessionId === $this->session->id
            && $event->gameId === $this->game->id
            && $event->category === ObservationCategory::Rules,
    );
});
