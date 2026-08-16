<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Events\GameCreated;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('prototype-lab')->create();
});

it('creates a game in the workspace named by the URL', function () {
    $response = $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/prototype-lab/games', [
            'name' => 'Bears & Bridges',
            'slug' => 'bears-and-bridges',
            'description' => 'Two bears, one river, not enough planks.',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Bears & Bridges')
        ->assertJsonPath('data.slug', 'bears-and-bridges')
        ->assertJsonPath('data.workspace_id', $this->workspace->id)
        ->assertJsonPath('data.created_by', $this->designer->id);

    $this->assertDatabaseHas('games', [
        'workspace_id' => $this->workspace->id,
        'slug' => 'bears-and-bridges',
        'created_by' => $this->designer->id,
    ]);
});

/**
 * A new game is nobody's active project and nothing is designed yet. Those
 * are two separate defaults because they are two separate facts.
 */
it('starts a game as a draft in the idea phase', function () {
    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/prototype-lab/games', ['name' => 'Bears & Bridges'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.design_phase', 'idea');
});

it('lets the creator start a game further along than an idea', function () {
    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/prototype-lab/games', [
            'name' => 'Bears & Bridges',
            'design_phase' => 'prototyping',
        ])
        ->assertCreated()
        ->assertJsonPath('data.design_phase', 'prototyping')
        ->assertJsonPath('data.status', 'draft');
});

it('derives an address when none is supplied', function () {
    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/prototype-lab/games', ['name' => 'Bears & Bridges'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'bears-bridges');
});

/**
 * A derived address may be adjusted around a collision, because the person
 * did not ask for that specific address in the first place.
 */
it('numbers a derived address around a collision', function () {
    Game::factory()->inWorkspace($this->workspace)->withSlug('bears-bridges')->create();

    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/prototype-lab/games', ['name' => 'Bears & Bridges'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'bears-bridges-2');
});

/**
 * An address somebody typed is theirs, so a collision is reported rather than
 * quietly worked around.
 */
it('reports a collision on an address that was supplied', function () {
    Game::factory()->inWorkspace($this->workspace)->withSlug('bears-and-bridges')->create();

    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/prototype-lab/games', [
            'name' => 'Bears & Bridges',
            'slug' => 'bears-and-bridges',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('slug');
});

/**
 * The whole point of scoping addresses to a workspace: another studio's game
 * at the same address is not a collision.
 */
it('allows the same address in two different workspaces', function () {
    $other = User::factory()->create();
    $otherWorkspace = Workspace::factory()->ownedBy($other)->withSlug('second-studio')->create();

    Game::factory()->inWorkspace($otherWorkspace)->withSlug('bears-and-bridges')->create();

    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/prototype-lab/games', [
            'name' => 'Bears & Bridges',
            'slug' => 'bears-and-bridges',
        ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'bears-and-bridges');

    expect(Game::query()->where('slug', 'bears-and-bridges')->count())->toBe(2);
});

it('rejects an address that is not URL safe', function (string $slug) {
    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/prototype-lab/games', [
            'name' => 'Bears & Bridges',
            'slug' => $slug,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('slug');
})->with([
    'spaces' => 'bears and bridges',
    'punctuation' => 'bears_and_bridges',
    'accents' => 'béars-and-bridges',
    'reserved' => 'settings',
    'reserved action' => 'versions',
    'too short' => 'b',
]);

/**
 * Case is the one thing normalised rather than refused: folding it cannot
 * change which game the address means.
 */
it('folds the case of an address rather than rejecting it', function () {
    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/prototype-lab/games', [
            'name' => 'Bears & Bridges',
            'slug' => 'Bears-And-Bridges',
        ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'bears-and-bridges');
});

it('requires a name', function () {
    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/prototype-lab/games', ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

/**
 * Games have short names. "Go" and "Uno" must both be creatable.
 */
it('accepts a two character address', function () {
    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/prototype-lab/games', ['name' => 'Go', 'slug' => 'go'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'go');
});

it('announces the new game', function () {
    Event::fake([GameCreated::class]);

    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/prototype-lab/games', ['name' => 'Bears & Bridges'])
        ->assertCreated();

    Event::assertDispatched(
        GameCreated::class,
        fn (GameCreated $event) => $event->workspaceId === $this->workspace->id
            && $event->createdBy === $this->designer->id
            && $event->slug === 'bears-bridges',
    );
});

it('will not create a game for a guest', function () {
    $this->postJson('/api/v1/workspaces/prototype-lab/games', ['name' => 'Bears & Bridges'])
        ->assertUnauthorized();

    expect(Game::query()->count())->toBe(0);
});

/**
 * The creator is taken from the session, never from the request. A caller who
 * names somebody else is credited with the game anyway.
 */
it('ignores a creator supplied by the client', function () {
    $someoneElse = User::factory()->create();

    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/prototype-lab/games', [
            'name' => 'Bears & Bridges',
            'created_by' => $someoneElse->id,
            'workspace_id' => 'anything-at-all',
            'status' => 'completed',
        ])
        ->assertCreated()
        ->assertJsonPath('data.created_by', $this->designer->id)
        ->assertJsonPath('data.workspace_id', $this->workspace->id)
        ->assertJsonPath('data.status', 'draft');
});

it('creates a game from the web form and lands inside it', function () {
    $this->actingAs($this->designer)
        ->post(route('games.store', 'prototype-lab'), [
            'name' => 'Bears & Bridges',
            'slug' => 'bears-and-bridges',
        ])
        ->assertRedirect(route('games.show', ['prototype-lab', 'bears-and-bridges']));
});
