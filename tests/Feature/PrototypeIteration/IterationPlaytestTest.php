<?php

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestParticipant;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\PrototypeIteration\Domain\Events\PlaytestAttachedToIteration;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\IterationPlaytest;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * The seam between PrototypeIteration and Playtesting.
 *
 * What is being tested is as much the shape of the integration as the behaviour: the playtest travels
 * as an id in a body rather than a route segment, nothing about it is copied into this module, and the
 * figures shown against an attached playtest are Playtesting's own at the moment of the read.
 */
beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();
    $this->game = Game::factory()->inWorkspace($this->workspace)->withSlug('bears')->active()->create();
    $this->iteration = Iteration::factory()->forGame($this->game)->inProgress()->create();
    $this->playtest = Playtest::factory()->forGame($this->game)->titled('Four-player combat')->create();

    $this->base = "/api/v1/workspaces/studio/games/bears/iterations/{$this->iteration->id}/playtests";
});

it('attaches a playtest of the same game', function () {
    $this->actingAs($this->designer)->postJson($this->base, ['playtest_id' => $this->playtest->id])
        ->assertCreated()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.playtest_id', $this->playtest->id)
        ->assertJsonPath('data.0.title', 'Four-player combat')
        ->assertJsonPath('data.0.is_available', true);

    expect(IterationPlaytest::query()->count())->toBe(1);
});

it('stores nothing about the playtest but the association', function () {
    $this->actingAs($this->designer)->postJson($this->base, ['playtest_id' => $this->playtest->id])
        ->assertCreated();

    $link = IterationPlaytest::query()->sole();

    expect(array_keys($link->getAttributes()))
        ->toEqualCanonicalizing(['id', 'iteration_id', 'playtest_id', 'created_by', 'created_at', 'updated_at']);
});

it('reads the evidence counts from Playtesting rather than from a copy', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();
    PlaytestParticipant::factory()->forSession($session)->count(4)->create();
    PlaytestObservation::factory()->forSession($session)->count(8)->create();

    IterationPlaytest::factory()
        ->forIteration($this->iteration)
        ->forPlaytest($this->playtest->id)
        ->create();

    $this->actingAs($this->designer)->getJson($this->base)
        ->assertOk()
        ->assertJsonPath('data.0.sessions_count', 1)
        ->assertJsonPath('data.0.participants_count', 4)
        ->assertJsonPath('data.0.observations_count', 8)
        ->assertJsonPath('data.0.has_evidence', true);
});

it('reflects evidence added to the playtest afterwards, with no write here', function () {
    IterationPlaytest::factory()
        ->forIteration($this->iteration)
        ->forPlaytest($this->playtest->id)
        ->create();

    $this->actingAs($this->designer)->getJson($this->base)
        ->assertOk()
        ->assertJsonPath('data.0.observations_count', 0);

    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();
    PlaytestObservation::factory()->forSession($session)->count(3)->create();

    $this->actingAs($this->designer)->getJson($this->base)
        ->assertOk()
        ->assertJsonPath('data.0.observations_count', 3);
});

it('refuses a playtest belonging to another game', function () {
    $otherGame = Game::factory()->inWorkspace($this->workspace)->withSlug('other')->active()->create();
    $theirPlaytest = Playtest::factory()->forGame($otherGame)->create();

    $this->actingAs($this->designer)->postJson($this->base, ['playtest_id' => $theirPlaytest->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('playtest_id');

    expect(IterationPlaytest::query()->count())->toBe(0);
});

it('refuses a playtest from another workspace entirely', function () {
    $outsider = User::factory()->create();
    $elsewhere = Workspace::factory()->ownedBy($outsider)->withSlug('rivals')->create();
    $theirGame = Game::factory()->inWorkspace($elsewhere)->active()->create();
    $theirPlaytest = Playtest::factory()->forGame($theirGame)->create();

    $this->actingAs($this->designer)->postJson($this->base, ['playtest_id' => $theirPlaytest->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('playtest_id');
});

it('refuses a playtest id that names nothing', function () {
    $this->actingAs($this->designer)->postJson($this->base, ['playtest_id' => 'not-a-uuid'])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('playtest_id');
});

it('requires a playtest', function () {
    $this->actingAs($this->designer)->postJson($this->base, [])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('playtest_id');
});

/**
 * A second attachment says nothing the first did not, and a duplicate would make "four playtests" on a
 * card a count of button presses rather than of evidence.
 */
it('refuses to attach the same playtest twice', function () {
    $this->actingAs($this->designer)->postJson($this->base, ['playtest_id' => $this->playtest->id])
        ->assertCreated();

    /*
     * A 409 to an API client: nothing about the request is malformed, the state simply already says
     * what the caller was asking for. The same violation names a field, so on the screens it surfaces
     * as a validation error beside the playtest picker instead of as a toast.
     */
    $this->actingAs($this->designer)->postJson($this->base, ['playtest_id' => $this->playtest->id])
        ->assertConflict();

    expect(IterationPlaytest::query()->count())->toBe(1);
});

it('is refused by the database as well as by the check', function () {
    IterationPlaytest::factory()
        ->forIteration($this->iteration)
        ->forPlaytest($this->playtest->id)
        ->create();

    expect(fn () => IterationPlaytest::factory()
        ->forIteration($this->iteration)
        ->forPlaytest($this->playtest->id)
        ->create())->toThrow(UniqueConstraintViolationException::class);
});

it('lets one playtest be attached to several cycles', function () {
    $second = Iteration::factory()->forGame($this->game)->inProgress()->create();

    $this->actingAs($this->designer)->postJson($this->base, ['playtest_id' => $this->playtest->id])
        ->assertCreated();

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears/iterations/{$second->id}/playtests", [
            'playtest_id' => $this->playtest->id,
        ])
        ->assertCreated();

    expect(IterationPlaytest::query()->count())->toBe(2);
});

it('announces both sides of the connection', function () {
    Event::fake([PlaytestAttachedToIteration::class]);

    $this->actingAs($this->designer)->postJson($this->base, ['playtest_id' => $this->playtest->id])
        ->assertCreated();

    Event::assertDispatched(fn (PlaytestAttachedToIteration $event): bool => $event->iterationId === $this->iteration->id
        && $event->playtestId === $this->playtest->id
        && $event->gameId === $this->game->id);
});

it('detaches by addressing the association, not the playtest', function () {
    $link = IterationPlaytest::factory()
        ->forIteration($this->iteration)
        ->forPlaytest($this->playtest->id)
        ->create();

    $this->actingAs($this->designer)->deleteJson($this->base.'/'.$link->id)
        ->assertNoContent();

    expect(IterationPlaytest::query()->count())->toBe(0)
        ->and(Playtest::query()->whereKey($this->playtest->id)->exists())->toBeTrue();
});

it('404s on a link belonging to another cycle', function () {
    $other = Iteration::factory()->forGame($this->game)->inProgress()->create();
    $theirLink = IterationPlaytest::factory()
        ->forIteration($other)
        ->forPlaytest($this->playtest->id)
        ->create();

    $this->actingAs($this->designer)->deleteJson($this->base.'/'.$theirLink->id)
        ->assertNotFound();

    expect(IterationPlaytest::query()->count())->toBe(1);
});

it('refuses to attach or detach once the cycle has closed', function () {
    $closed = Iteration::factory()->forGame($this->game)->completed()->create();
    $link = IterationPlaytest::factory()
        ->forIteration($closed)
        ->forPlaytest($this->playtest->id)
        ->create();

    $closedBase = "/api/v1/workspaces/studio/games/bears/iterations/{$closed->id}/playtests";

    $this->actingAs($this->designer)->postJson($closedBase, ['playtest_id' => $this->playtest->id])
        ->assertForbidden();

    $this->actingAs($this->designer)->deleteJson($closedBase.'/'.$link->id)
        ->assertForbidden();

    expect(IterationPlaytest::query()->count())->toBe(1);
});

it('publishes no route that names a playtest in the URL', function () {
    $this->actingAs($this->designer)
        ->deleteJson($this->base.'/'.$this->playtest->id)
        ->assertNotFound();
});
