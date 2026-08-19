<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\DesignChangeCategory;
use Modules\PrototypeIteration\Domain\Events\DesignChangeCreated;
use Modules\PrototypeIteration\Domain\Models\DesignChange;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\Workspace\Domain\Models\Workspace;

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();
    $this->game = Game::factory()->inWorkspace($this->workspace)->withSlug('bears')->active()->create();
    $this->iteration = Iteration::factory()->forGame($this->game)->inProgress()->create();

    $this->base = "/api/v1/workspaces/studio/games/bears/iterations/{$this->iteration->id}/changes";
});

function recordChange(array $payload = [])
{
    return test()->actingAs(test()->designer)->postJson(test()->base, array_merge([
        'category' => 'pacing',
        'title' => 'Remove reaction phase',
        'reason' => 'Reaction windows created excessive downtime.',
    ], $payload));
}

it('records what was changed and why', function () {
    recordChange(['description' => 'Players no longer interrupt an opponent\'s action.'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Remove reaction phase')
        ->assertJsonPath('data.category', DesignChangeCategory::Pacing->value)
        ->assertJsonPath('data.reason', 'Reaction windows created excessive downtime.')
        ->assertJsonPath('data.description', 'Players no longer interrupt an opponent\'s action.')
        ->assertJsonPath('data.iteration_id', $this->iteration->id)
        ->assertJsonPath('data.created_by', $this->designer->id);
});

/**
 * The requirement the whole table exists for. A list of edits answers "what is different"; a list of
 * edits with reasons answers "why is the game like this", which is the question somebody actually has
 * eighteen months later.
 */
it('refuses a change with no reason', function () {
    recordChange(['reason' => null])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('reason');

    expect(DesignChange::query()->count())->toBe(0);
});

it('refuses a reason too short to be an argument', function () {
    recordChange(['reason' => 'meh'])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('reason');
});

it('accepts a change with no description, because the title usually says it', function () {
    recordChange(['description' => null])
        ->assertCreated()
        ->assertJsonPath('data.description', null);
});

it('files an unspecified change under other rather than guessing', function () {
    recordChange(['category' => null])
        ->assertCreated()
        ->assertJsonPath('data.category', DesignChangeCategory::Other->value);
});

it('refuses a category it does not have', function () {
    recordChange(['category' => 'vibes'])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('category');
});

it('announces the category so a month of changes reads in aggregate', function () {
    Event::fake([DesignChangeCreated::class]);

    recordChange(['category' => 'economy'])->assertCreated();

    Event::assertDispatched(fn (DesignChangeCreated $event): bool => $event->iterationId === $this->iteration->id
        && $event->gameId === $this->game->id
        && $event->category === DesignChangeCategory::Economy);
});

it('lists a cycle\'s changes in the order they were recorded', function () {
    $first = DesignChange::factory()->forIteration($this->iteration)->titled('Removed trading')->create([
        'created_at' => now()->subHour(),
    ]);
    $second = DesignChange::factory()->forIteration($this->iteration)->titled('Reduced resources')->create();

    $this->actingAs($this->designer)->getJson($this->base)
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $first->id)
        ->assertJsonPath('data.1.id', $second->id);
});

it('rewords a change while the cycle is open', function () {
    $change = DesignChange::factory()->forIteration($this->iteration)->create();

    $this->actingAs($this->designer)->patchJson($this->base.'/'.$change->id, [
        'category' => 'balance',
        'title' => 'Reduced starting resources',
        'reason' => 'Five made the opening turn a formality.',
    ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Reduced starting resources')
        ->assertJsonPath('data.category', DesignChangeCategory::Balance->value);
});

it('removes a change entered by mistake while the cycle is open', function () {
    $change = DesignChange::factory()->forIteration($this->iteration)->create();

    $this->actingAs($this->designer)->deleteJson($this->base.'/'.$change->id)
        ->assertNoContent();

    expect(DesignChange::query()->count())->toBe(0);
});

/**
 * A change belonging to another cycle is not refused by a policy — it fails to resolve, because every
 * child is looked up through its parent.
 */
it('404s on a change belonging to another cycle', function () {
    $other = Iteration::factory()->forGame($this->game)->inProgress()->create();
    $theirChange = DesignChange::factory()->forIteration($other)->create();

    $this->actingAs($this->designer)->patchJson($this->base.'/'.$theirChange->id, [
        'title' => 'Hijacked',
        'reason' => 'Trying to edit somebody else\'s change.',
    ])->assertNotFound();

    expect($theirChange->refresh()->title)->not->toBe('Hijacked');
});

it('404s on a change id that names nothing', function () {
    $this->actingAs($this->designer)->deleteJson($this->base.'/not-a-uuid')->assertNotFound();
});
