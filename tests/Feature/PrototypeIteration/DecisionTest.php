<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\DecisionStatus;
use Modules\PrototypeIteration\Domain\Events\DecisionAccepted;
use Modules\PrototypeIteration\Domain\Events\DecisionCreated;
use Modules\PrototypeIteration\Domain\Events\DecisionDeferred;
use Modules\PrototypeIteration\Domain\Events\DecisionRejected;
use Modules\PrototypeIteration\Domain\Models\DecisionEvidence;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\Workspace\Domain\Models\Workspace;

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();
    $this->game = Game::factory()->inWorkspace($this->workspace)->withSlug('bears')->active()->create();
    $this->iteration = Iteration::factory()->forGame($this->game)->inProgress()->create();

    $this->base = "/api/v1/workspaces/studio/games/bears/iterations/{$this->iteration->id}/decisions";
});

function proposeDecision(array $payload = [])
{
    return test()->actingAs(test()->designer)->postJson(test()->base, array_merge([
        'title' => 'Reaction phase',
        'decision' => 'Remove the reaction phase permanently.',
        'reason' => 'Players made decisions faster and average downtime fell by about a fifth.',
    ], $payload));
}

it('proposes a conclusion, unattributed and unsettled', function () {
    proposeDecision()
        ->assertCreated()
        ->assertJsonPath('data.decision', 'Remove the reaction phase permanently.')
        ->assertJsonPath('data.reason', 'Players made decisions faster and average downtime fell by about a fifth.')
        ->assertJsonPath('data.status', DecisionStatus::Proposed->value)
        ->assertJsonPath('data.decided_by', null)
        ->assertJsonPath('data.decided_at', null)
        ->assertJsonPath('data.is_settled', false)
        ->assertJsonPath('data.created_by', $this->designer->id);
});

it('requires a title, a decision and a reason', function (string $field) {
    proposeDecision([$field => null])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor($field);

    expect(DesignDecision::query()->count())->toBe(0);
})->with(['title', 'decision', 'reason']);

it('ignores a status the create request tries to set', function () {
    proposeDecision(['status' => 'accepted'])
        ->assertCreated()
        ->assertJsonPath('data.status', DecisionStatus::Proposed->value);
});

it('announces a proposal separately from its settlement', function () {
    Event::fake([DecisionCreated::class, DecisionAccepted::class]);

    proposeDecision()->assertCreated();

    Event::assertDispatched(DecisionCreated::class);
    Event::assertNotDispatched(DecisionAccepted::class);
});

it('accepts a proposal and records who settled it, and when', function () {
    $decision = DesignDecision::factory()->forIteration($this->iteration)->create();

    $this->actingAs($this->designer)->postJson($this->base.'/'.$decision->id.'/accept')
        ->assertOk()
        ->assertJsonPath('data.status', DecisionStatus::Accepted->value)
        ->assertJsonPath('data.is_settled', true)
        ->assertJsonPath('data.decided_by', $this->designer->id);

    expect($decision->refresh()->decided_at)->not->toBeNull();
});

it('rejects a proposal as a real recorded ending', function () {
    $decision = DesignDecision::factory()->forIteration($this->iteration)->create();

    $this->actingAs($this->designer)->postJson($this->base.'/'.$decision->id.'/reject')
        ->assertOk()
        ->assertJsonPath('data.status', DecisionStatus::Rejected->value)
        ->assertJsonPath('data.is_settled', true);

    expect($decision->refresh()->reason)->not->toBeNull();
});

it('defers a proposal without settling it', function () {
    $decision = DesignDecision::factory()->forIteration($this->iteration)->create();

    $this->actingAs($this->designer)->postJson($this->base.'/'.$decision->id.'/defer')
        ->assertOk()
        ->assertJsonPath('data.status', DecisionStatus::Deferred->value)
        ->assertJsonPath('data.is_settled', false);
});

it('lets a deferred decision be taken up again', function (string $action, string $expected) {
    $decision = DesignDecision::factory()->forIteration($this->iteration)->deferred()->create();

    $this->actingAs($this->designer)->postJson($this->base.'/'.$decision->id.'/'.$action)
        ->assertOk()
        ->assertJsonPath('data.status', $expected);
})->with([
    ['accept', 'accepted'],
    ['reject', 'rejected'],
]);

/**
 * The strictest rule in the module. Reversing an accepted decision in place would leave the design
 * carrying a change whose recorded justification now argues against it.
 */
it('refuses to reverse an accepted decision', function () {
    $decision = DesignDecision::factory()->forIteration($this->iteration)->accepted()->create();

    $this->actingAs($this->designer)->postJson($this->base.'/'.$decision->id.'/reject')
        ->assertConflict();

    expect($decision->refresh()->status)->toBe(DecisionStatus::Accepted);
});

it('refuses to reverse a rejected decision', function () {
    $decision = DesignDecision::factory()->forIteration($this->iteration)->rejected()->create();

    $this->actingAs($this->designer)->postJson($this->base.'/'.$decision->id.'/accept')
        ->assertConflict();

    expect($decision->refresh()->status)->toBe(DecisionStatus::Rejected);
});

it('refuses to re-accept a decision already accepted', function () {
    $decision = DesignDecision::factory()->forIteration($this->iteration)->accepted()->create();

    $this->actingAs($this->designer)->postJson($this->base.'/'.$decision->id.'/accept')
        ->assertConflict();
});

it('refuses to reword a settled decision', function (string $state) {
    $decision = DesignDecision::factory()
        ->forIteration($this->iteration)
        ->{$state}()
        ->create(['decision' => 'Remove the reaction phase.']);

    $this->actingAs($this->designer)->patchJson($this->base.'/'.$decision->id, [
        'title' => 'Reaction phase',
        'decision' => 'Keep the reaction phase, obviously.',
        'reason' => 'Rewriting what the studio agreed to.',
    ])->assertConflict();

    expect($decision->refresh()->decision)->toBe('Remove the reaction phase.');
})->with(['accepted', 'rejected']);

it('lets an open decision be reworded', function (string $state) {
    $decision = $state === 'deferred'
        ? DesignDecision::factory()->forIteration($this->iteration)->deferred()->create()
        : DesignDecision::factory()->forIteration($this->iteration)->create();

    $this->actingAs($this->designer)->patchJson($this->base.'/'.$decision->id, [
        'title' => 'Reaction phase',
        'decision' => 'Remove the reaction phase permanently.',
        'reason' => 'Downtime fell measurably and nobody used the window after round two.',
    ])
        ->assertOk()
        ->assertJsonPath('data.decision', 'Remove the reaction phase permanently.');
})->with(['proposed', 'deferred']);

it('announces each settlement as its own event', function (string $action, string $event) {
    Event::fake([DecisionAccepted::class, DecisionRejected::class, DecisionDeferred::class]);

    $decision = DesignDecision::factory()->forIteration($this->iteration)->create();

    $this->actingAs($this->designer)->postJson($this->base.'/'.$decision->id.'/'.$action)->assertOk();

    Event::assertDispatched($event);
})->with([
    ['accept', DecisionAccepted::class],
    ['reject', DecisionRejected::class],
    ['defer', DecisionDeferred::class],
]);

it('announces how much evidence an accepted decision cited', function () {
    Event::fake([DecisionAccepted::class]);

    $decision = DesignDecision::factory()->forIteration($this->iteration)->create();
    DecisionEvidence::factory()->forDecision($decision)->count(3)->create();

    $this->actingAs($this->designer)->postJson($this->base.'/'.$decision->id.'/accept')->assertOk();

    Event::assertDispatched(fn (DecisionAccepted $event): bool => $event->evidenceCount === 3);
});

it('lists a cycle\'s decisions in the order they were proposed', function () {
    $first = DesignDecision::factory()->forIteration($this->iteration)->titled('Reactions')->create([
        'created_at' => now()->subHour(),
    ]);
    $second = DesignDecision::factory()->forIteration($this->iteration)->titled('Resources')->create();

    $this->actingAs($this->designer)->getJson($this->base)
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $first->id)
        ->assertJsonPath('data.1.id', $second->id);
});

it('offers no moves on a settled decision', function () {
    DesignDecision::factory()->forIteration($this->iteration)->accepted()->create();

    $this->actingAs($this->designer)->getJson($this->base)
        ->assertOk()
        ->assertJsonCount(0, 'data.0.available_transitions');
});

it('publishes no route for deleting a decision', function () {
    $decision = DesignDecision::factory()->forIteration($this->iteration)->create();

    $this->actingAs($this->designer)->deleteJson($this->base.'/'.$decision->id)
        ->assertMethodNotAllowed();

    expect(DesignDecision::query()->count())->toBe(1);
});

it('refuses to settle a decision once the cycle around it has closed', function (string $action) {
    $closed = Iteration::factory()->forGame($this->game)->completed()->create();
    $decision = DesignDecision::factory()->forIteration($closed)->create();

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears/iterations/{$closed->id}/decisions/{$decision->id}/{$action}")
        ->assertForbidden();
})->with(['accept', 'reject', 'defer']);
