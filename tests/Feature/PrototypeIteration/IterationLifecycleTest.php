<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\IterationOutcome;
use Modules\PrototypeIteration\Domain\Enums\IterationStatus;
use Modules\PrototypeIteration\Domain\Events\IterationCancelled;
use Modules\PrototypeIteration\Domain\Events\IterationCompleted;
use Modules\PrototypeIteration\Domain\Events\IterationStarted;
use Modules\PrototypeIteration\Domain\Models\DesignChange;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\Workspace\Domain\Models\Workspace;

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();
    $this->game = Game::factory()->inWorkspace($this->workspace)->withSlug('bears')->active()->create();
    $this->iteration = Iteration::factory()->forGame($this->game)->create();

    $this->base = "/api/v1/workspaces/studio/games/bears/iterations/{$this->iteration->id}";
});

it('starts a planned cycle and records when', function () {
    $this->actingAs($this->designer)->postJson($this->base.'/start')
        ->assertOk()
        ->assertJsonPath('data.status', IterationStatus::InProgress->value);

    expect($this->iteration->refresh()->started_at)->not->toBeNull();
});

it('announces the start with the build that was on the table', function () {
    Event::fake([IterationStarted::class]);

    $this->actingAs($this->designer)->postJson($this->base.'/start')->assertOk();

    Event::assertDispatched(function (IterationStarted $event): bool {
        return $event->iterationId === $this->iteration->id
            && $event->gameId === $this->game->id
            && $event->prototypeVersionId === $this->iteration->prototype_version_id
            && $event->startedBy === $this->designer->id;
    });
});

it('ignores a start time the caller tries to supply', function () {
    $this->actingAs($this->designer)
        ->postJson($this->base.'/start', ['started_at' => '2020-01-01T00:00:00+00:00'])
        ->assertOk();

    expect($this->iteration->refresh()->started_at?->year)->toBe((int) now()->year);
});

it('completes a running cycle with an outcome and a summary', function () {
    $this->iteration->forceFill(['status' => IterationStatus::InProgress, 'started_at' => now()])->save();

    $this->actingAs($this->designer)->postJson($this->base.'/complete', [
        'outcome' => 'partial',
        'summary' => 'Combat became more interesting, but downtime remains too high.',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', IterationStatus::Completed->value)
        ->assertJsonPath('data.outcome', IterationOutcome::Partial->value)
        ->assertJsonPath('data.summary', 'Combat became more interesting, but downtime remains too high.');

    expect($this->iteration->refresh()->completed_at)->not->toBeNull();
});

/**
 * Section 47: both are required, and completion is refused without them. An iteration with no outcome
 * is a period of time rather than a turn of a loop the next turn can be built on.
 */
it('refuses to complete a cycle without an outcome or a summary', function (string $field) {
    $this->iteration->forceFill(['status' => IterationStatus::InProgress, 'started_at' => now()])->save();

    $payload = ['outcome' => 'success', 'summary' => 'Downtime fell by about a fifth.'];
    $payload[$field] = null;

    $this->actingAs($this->designer)->postJson($this->base.'/complete', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor($field);

    expect($this->iteration->refresh()->status)->toBe(IterationStatus::InProgress);
})->with(['outcome', 'summary']);

it('refuses an outcome it does not have', function () {
    $this->iteration->forceFill(['status' => IterationStatus::InProgress, 'started_at' => now()])->save();

    $this->actingAs($this->designer)->postJson($this->base.'/complete', [
        'outcome' => 'magnificent',
        'summary' => 'Downtime fell by about a fifth.',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('outcome');
});

it('refuses a summary too short to tell the next designer anything', function () {
    $this->iteration->forceFill(['status' => IterationStatus::InProgress, 'started_at' => now()])->save();

    $this->actingAs($this->designer)->postJson($this->base.'/complete', [
        'outcome' => 'success',
        'summary' => 'good',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('summary');
});

/**
 * Stricter than a playtest's lifecycle on purpose. A cycle that completed without ever starting would
 * carry an outcome nobody gathered evidence for; the honest ending for work that never happened is
 * cancellation.
 *
 * A 409 rather than a 403, and the difference is the one the module draws everywhere: the caller is
 * allowed to act on this cycle — it is theirs and it is open — but the move they asked for is not one
 * the lifecycle has. The interface never offers it, because `available_transitions` is derived from
 * the same matrix.
 */
it('refuses to complete a cycle that never started', function () {
    $this->actingAs($this->designer)->postJson($this->base.'/complete', [
        'outcome' => 'success',
        'summary' => 'Everything went perfectly without anybody doing anything.',
    ])
        ->assertConflict();

    expect($this->iteration->refresh()->status)->toBe(IterationStatus::Planned);
});

it('does not offer completion on a cycle that has not started', function () {
    $this->actingAs($this->designer)->getJson($this->base)
        ->assertOk()
        ->assertJsonMissing(['status' => IterationStatus::Completed->value]);
});

it('announces completion with what the cycle produced', function () {
    Event::fake([IterationCompleted::class]);

    $this->iteration->forceFill(['status' => IterationStatus::InProgress, 'started_at' => now()])->save();

    DesignChange::factory()->forIteration($this->iteration)->count(3)->create();
    DesignExperiment::factory()->forIteration($this->iteration)->count(2)->create();
    DesignDecision::factory()->forIteration($this->iteration)->create();

    $this->actingAs($this->designer)->postJson($this->base.'/complete', [
        'outcome' => 'partial',
        'summary' => 'Combat improved, downtime did not.',
    ])->assertOk();

    Event::assertDispatched(function (IterationCompleted $event): bool {
        return $event->outcome === IterationOutcome::Partial
            && $event->changeCount === 3
            && $event->experimentCount === 2
            && $event->decisionCount === 1
            && $event->playtestCount === 0;
    });
});

/**
 * Section 22, and the reason the summary counts completed experiments separately. An experiment still
 * running when the cycle closed stayed unanswered; marking it complete would put a result into the
 * record that nobody observed.
 */
it('does not complete the cycle\'s experiments along with it', function () {
    $this->iteration->forceFill(['status' => IterationStatus::InProgress, 'started_at' => now()])->save();

    $running = DesignExperiment::factory()->forIteration($this->iteration)->running()->create();
    $planned = DesignExperiment::factory()->forIteration($this->iteration)->create();

    $this->actingAs($this->designer)->postJson($this->base.'/complete', [
        'outcome' => 'inconclusive',
        'summary' => 'We ran out of time before the experiments finished.',
    ])->assertOk();

    expect($running->refresh()->status->value)->toBe('running')
        ->and($running->actual_result)->toBeNull()
        ->and($planned->refresh()->status->value)->toBe('planned');
});

it('cancels a planned cycle', function () {
    $this->actingAs($this->designer)->postJson($this->base.'/cancel')
        ->assertOk()
        ->assertJsonPath('data.status', IterationStatus::Cancelled->value);
});

it('cancels a running cycle', function () {
    $this->iteration->forceFill(['status' => IterationStatus::InProgress, 'started_at' => now()])->save();

    $this->actingAs($this->designer)->postJson($this->base.'/cancel')
        ->assertOk()
        ->assertJsonPath('data.status', IterationStatus::Cancelled->value);
});

/**
 * A cancelled cycle did not fail — failing is a result, and a result means somebody looked. Recording
 * one would make the outcome column a record of the studio's calendar rather than of its findings.
 */
it('records no outcome when a cycle is cancelled', function () {
    $this->actingAs($this->designer)->postJson($this->base.'/cancel')
        ->assertOk()
        ->assertJsonPath('data.outcome', null)
        ->assertJsonPath('data.summary', null);
});

it('announces cancellation', function () {
    Event::fake([IterationCancelled::class]);

    $this->actingAs($this->designer)->postJson($this->base.'/cancel')->assertOk();

    Event::assertDispatched(fn (IterationCancelled $event): bool => $event->iterationId === $this->iteration->id
        && $event->cancelledBy === $this->designer->id);
});

it('refuses every lifecycle move on a completed cycle', function (string $action) {
    $iteration = Iteration::factory()->forGame($this->game)->completed()->create();

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears/iterations/{$iteration->id}/{$action}", [
            'outcome' => 'success',
            'summary' => 'Trying to reopen a finished cycle.',
        ])
        ->assertForbidden();
})->with(['start', 'complete', 'cancel']);

it('refuses every lifecycle move on a cancelled cycle', function (string $action) {
    $iteration = Iteration::factory()->forGame($this->game)->cancelled()->create();

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears/iterations/{$iteration->id}/{$action}", [
            'outcome' => 'success',
            'summary' => 'Trying to resume an abandoned cycle.',
        ])
        ->assertForbidden();
})->with(['start', 'complete', 'cancel']);

it('offers only the moves the cycle can actually make', function () {
    $this->actingAs($this->designer)->getJson($this->base)
        ->assertOk()
        ->assertJsonPath('data.available_transitions.0.status', IterationStatus::InProgress->value)
        ->assertJsonPath('data.available_transitions.1.status', IterationStatus::Cancelled->value)
        ->assertJsonCount(2, 'data.available_transitions');
});

it('offers no moves on a completed cycle', function () {
    $iteration = Iteration::factory()->forGame($this->game)->completed()->create();

    $this->actingAs($this->designer)
        ->getJson("/api/v1/workspaces/studio/games/bears/iterations/{$iteration->id}")
        ->assertOk()
        ->assertJsonCount(0, 'data.available_transitions');
});
