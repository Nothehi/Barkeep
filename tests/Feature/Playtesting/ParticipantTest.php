<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Enums\PlaytestParticipantRole;
use Modules\Playtesting\Domain\Events\ParticipantAdded;
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

    $this->playtest = Playtest::factory()->forGame($this->game)->createdBy($this->designer)->create();
    $this->session = PlaytestSession::factory()->forPlaytest($this->playtest)->inProgress()->create();

    $this->url = "/api/v1/workspaces/studio/games/bears-and-bridges/playtests/{$this->playtest->id}"
        ."/sessions/{$this->session->id}/participants";
});

/**
 * The common participant, and the reason `user_id` is nullable: most people at
 * a playtest are a friend, somebody from the local game group, or a stranger
 * at a convention.
 */
it('seats a guest with nothing but a name', function () {
    $this->actingAs($this->designer)
        ->postJson($this->url, ['display_name' => 'Sam'])
        ->assertCreated()
        ->assertJsonPath('data.display_name', 'Sam')
        ->assertJsonPath('data.user_id', null)
        ->assertJsonPath('data.is_registered', false)
        ->assertJsonPath('data.role', PlaytestParticipantRole::Player->value);
});

it('seats a teammate and links their account', function () {
    $teammate = User::factory()->create();
    $this->workspace->members()->create([
        'user_id' => $teammate->id,
        'role' => WorkspaceRole::Member,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->designer)
        ->postJson($this->url, ['display_name' => $teammate->name, 'user_id' => $teammate->id])
        ->assertCreated()
        ->assertJsonPath('data.user_id', $teammate->id)
        ->assertJsonPath('data.is_registered', true)
        ->assertJsonPath('data.user.id', $teammate->id);
});

/**
 * Not a rule about who may play — anyone may play, as a guest. It is a
 * disclosure guard: linking an account makes its name and address readable
 * through the participant list.
 */
it('refuses to link an account from outside the workspace', function () {
    $stranger = User::factory()->create();

    $this->actingAs($this->designer)
        ->postJson($this->url, ['display_name' => 'A stranger', 'user_id' => $stranger->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('user_id');

    expect(PlaytestParticipant::query()->count())->toBe(0);
});

it('welcomes that same person as a guest instead', function () {
    $stranger = User::factory()->create();

    $this->actingAs($this->designer)
        ->postJson($this->url, ['display_name' => $stranger->name])
        ->assertCreated()
        ->assertJsonPath('data.user_id', null);
});

it('records every role somebody can have at a table', function (string $role) {
    $this->actingAs($this->designer)
        ->postJson($this->url, ['display_name' => 'Alex', 'role' => $role])
        ->assertCreated()
        ->assertJsonPath('data.role', $role);
})->with(['player', 'observer', 'facilitator', 'designer']);

it('refuses a role that is not one of the four', function () {
    $this->actingAs($this->designer)
        ->postJson($this->url, ['display_name' => 'Alex', 'role' => 'referee'])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('role');
});

it('requires a name for everybody, account or not', function () {
    $this->actingAs($this->designer)
        ->postJson($this->url, ['display_name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('display_name');
});

it('marks somebody added to a running session as having arrived', function () {
    $this->actingAs($this->designer)
        ->postJson($this->url, ['display_name' => 'Sam'])
        ->assertCreated();

    expect(PlaytestParticipant::query()->sole()->joined_at)->not->toBeNull();
});

it('does not invent an arrival time for a session nobody has started', function () {
    $planned = PlaytestSession::factory()->forPlaytest($this->playtest)->create();

    $this->actingAs($this->designer)
        ->postJson(
            "/api/v1/workspaces/studio/games/bears-and-bridges/playtests/{$this->playtest->id}"
            ."/sessions/{$planned->id}/participants",
            ['display_name' => 'Sam'],
        )
        ->assertCreated()
        ->assertJsonPath('data.joined_at', null);
});

/**
 * Somebody can only sit at a table once. A session that lists the same account
 * twice reports two players where there was one.
 */
it('refuses to seat the same account twice', function () {
    $teammate = User::factory()->create();
    $this->workspace->members()->create([
        'user_id' => $teammate->id,
        'role' => WorkspaceRole::Member,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->designer)
        ->postJson($this->url, ['display_name' => 'Alex', 'user_id' => $teammate->id])
        ->assertCreated();

    $this->actingAs($this->designer)
        ->postJson($this->url, ['display_name' => 'Alex again', 'user_id' => $teammate->id])
        ->assertStatus(409);

    expect(PlaytestParticipant::query()->count())->toBe(1);
});

/**
 * The rule reaches exactly as far as identity does. Two guests introduced as
 * "Sam" may genuinely be two people, and the platform has nothing to tell them
 * apart with — refusing the second would lose a real participant.
 */
it('allows two guests with the same name', function () {
    $this->actingAs($this->designer)->postJson($this->url, ['display_name' => 'Sam'])->assertCreated();
    $this->actingAs($this->designer)->postJson($this->url, ['display_name' => 'Sam'])->assertCreated();

    expect(PlaytestParticipant::query()->count())->toBe(2);
});

it('allows the same account at two different sessions', function () {
    $teammate = User::factory()->create();
    $this->workspace->members()->create([
        'user_id' => $teammate->id,
        'role' => WorkspaceRole::Member,
        'joined_at' => now(),
    ]);

    $second = PlaytestSession::factory()->forPlaytest($this->playtest)->inProgress()->create();

    $this->actingAs($this->designer)
        ->postJson($this->url, ['display_name' => 'Alex', 'user_id' => $teammate->id])
        ->assertCreated();

    $this->actingAs($this->designer)
        ->postJson(
            "/api/v1/workspaces/studio/games/bears-and-bridges/playtests/{$this->playtest->id}"
            ."/sessions/{$second->id}/participants",
            ['display_name' => 'Alex', 'user_id' => $teammate->id],
        )
        ->assertCreated();
});

it('lists who was at the session, in the order they were added', function () {
    foreach (['First', 'Second', 'Third'] as $name) {
        $this->actingAs($this->designer)->postJson($this->url, ['display_name' => $name])->assertCreated();
    }

    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.display_name', 'First')
        ->assertJsonPath('data.2.display_name', 'Third');
});

it('takes somebody off a session', function () {
    $participant = PlaytestParticipant::factory()->forSession($this->session)->guest('Sam')->create();

    $this->actingAs($this->designer)
        ->deleteJson("{$this->url}/{$participant->id}")
        ->assertNoContent();

    expect(PlaytestParticipant::query()->count())->toBe(0);
});

/**
 * Removal is usually a correction — the wrong name was typed. Deleting what
 * somebody said along with them would mean a typo could quietly destroy
 * evidence.
 */
it('keeps what a removed participant said, without their name on it', function () {
    $participant = PlaytestParticipant::factory()->forSession($this->session)->guest('Sam')->create();

    $observation = PlaytestObservation::factory()->about($participant)->create();
    $feedback = PlaytestFeedback::factory()->from($participant)->create();

    $this->actingAs($this->designer)
        ->deleteJson("{$this->url}/{$participant->id}")
        ->assertNoContent();

    expect($observation->fresh())->not->toBeNull()
        ->and($observation->fresh()->participant_id)->toBeNull()
        ->and($feedback->fresh())->not->toBeNull()
        ->and($feedback->fresh()->participant_id)->toBeNull();
});

it('does not find a participant belonging to another session', function () {
    $other = PlaytestSession::factory()->forPlaytest($this->playtest)->inProgress()->create();
    $theirs = PlaytestParticipant::factory()->forSession($other)->create();

    $this->actingAs($this->designer)
        ->deleteJson("{$this->url}/{$theirs->id}")
        ->assertNotFound();

    expect($theirs->fresh())->not->toBeNull();
});

it('will not seat anybody at a session that has ended', function () {
    $ended = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();

    $this->actingAs($this->designer)
        ->postJson(
            "/api/v1/workspaces/studio/games/bears-and-bridges/playtests/{$this->playtest->id}"
            ."/sessions/{$ended->id}/participants",
            ['display_name' => 'Late arrival'],
        )
        ->assertForbidden();
});

it('announces an arrival, saying whether they have an account', function () {
    Event::fake([ParticipantAdded::class]);

    $this->actingAs($this->designer)->postJson($this->url, ['display_name' => 'Sam'])->assertCreated();

    Event::assertDispatched(
        ParticipantAdded::class,
        fn (ParticipantAdded $event) => $event->sessionId === $this->session->id
            && $event->gameId === $this->game->id
            && $event->userId === null
            && $event->role === PlaytestParticipantRole::Player,
    );
});
