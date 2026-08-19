<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Events\PrototypeVersionCreated;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\Workspace\Domain\Models\Workspace;

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();
    $this->game = Game::factory()->inWorkspace($this->workspace)->withSlug('bears')->active()->create();
    $this->prototype = Prototype::factory()->forGame($this->game)->named('Core Combat')->create();

    $this->base = "/api/v1/workspaces/studio/games/bears/prototypes/{$this->prototype->id}/versions";
});

it('numbers the first state v1', function () {
    $this->actingAs($this->designer)->postJson($this->base)
        ->assertCreated()
        ->assertJsonPath('data.version_number', 1)
        ->assertJsonPath('data.label', 'v1')
        ->assertJsonPath('data.prototype_id', $this->prototype->id);
});

it('numbers each state in sequence', function () {
    foreach ([1, 2, 3] as $expected) {
        $this->actingAs($this->designer)->postJson($this->base)
            ->assertCreated()
            ->assertJsonPath('data.version_number', $expected);
    }

    expect($this->prototype->versions()->pluck('version_number')->sort()->values()->all())
        ->toBe([1, 2, 3]);
});

/**
 * The numbering rule as a security property rather than a convenience: a caller who could name
 * their own number could claim v999 or overwrite the meaning of a version three iterations point at.
 */
it('ignores a version number the caller tries to supply', function () {
    $this->actingAs($this->designer)->postJson($this->base, ['version_number' => 999])
        ->assertCreated()
        ->assertJsonPath('data.version_number', 1);
});

it('accepts a state with no name or description, because cutting one has to be cheap', function () {
    $this->actingAs($this->designer)->postJson($this->base)
        ->assertCreated()
        ->assertJsonPath('data.name', null);
});

it('records a name and description when the designer gives them', function () {
    $this->actingAs($this->designer)->postJson($this->base, [
        'name' => 'Simultaneous combat',
        'description' => 'Reaction phase removed, cards reprinted.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Simultaneous combat')
        ->assertJsonPath('data.description', 'Reaction phase removed, cards reprinted.');
});

it('announces the number so a consumer need not count the rows', function () {
    Event::fake([PrototypeVersionCreated::class]);

    PrototypeVersion::factory()->nextFor($this->prototype)->create();

    $this->actingAs($this->designer)->postJson($this->base)->assertCreated();

    Event::assertDispatched(function (PrototypeVersionCreated $event): bool {
        return $event->prototypeId === $this->prototype->id
            && $event->gameId === $this->game->id
            && $event->versionNumber === 2;
    });
});

it('lists a prototype\'s states newest first, because the top one is the answer', function () {
    PrototypeVersion::factory()->forPrototype($this->prototype)->numbered(1)->create();
    PrototypeVersion::factory()->forPrototype($this->prototype)->numbered(2)->create();

    $this->actingAs($this->designer)->getJson($this->base)
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.version_number', 2)
        ->assertJsonPath('data.1.version_number', 1);
});

it('addresses a state by its number rather than by its id', function () {
    $version = PrototypeVersion::factory()->forPrototype($this->prototype)->numbered(4)->create();

    $this->actingAs($this->designer)->getJson($this->base.'/4')
        ->assertOk()
        ->assertJsonPath('data.id', $version->id)
        ->assertJsonPath('data.label', 'v4');
});

it('404s on a version number the prototype does not have', function () {
    $this->actingAs($this->designer)->getJson($this->base.'/9')->assertNotFound();
});

it('404s on a version segment that is not a plain number', function (string $segment) {
    PrototypeVersion::factory()->forPrototype($this->prototype)->numbered(1)->create();

    $this->actingAs($this->designer)->getJson($this->base.'/'.$segment)->assertNotFound();
})->with(['v1', '01', '1.0', '0', '-1']);

/**
 * A version number is only meaningful inside one prototype, which is why the lookup is scoped to
 * one. Another prototype's v1 is not reachable from this address.
 */
it('does not reach another prototype\'s state through this prototype\'s address', function () {
    $other = Prototype::factory()->forGame($this->game)->named('Digital model')->create();
    PrototypeVersion::factory()->forPrototype($other)->numbered(1)->create();

    $this->actingAs($this->designer)->getJson($this->base.'/1')->assertNotFound();
});

it('reports how many iterations a state carries, which is what freezes it', function () {
    $version = PrototypeVersion::factory()->nextFor($this->prototype)->create();
    Iteration::factory()->forPrototypeVersion($version)->count(2)->create();

    $this->actingAs($this->designer)->getJson($this->base.'/1')
        ->assertOk()
        ->assertJsonPath('data.iterations_count', 2);
});

it('refuses to cut a version of an archived prototype', function () {
    $archived = Prototype::factory()->forGame($this->game)->archived()->create();

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears/prototypes/{$archived->id}/versions")
        ->assertForbidden();

    expect($archived->versions()->count())->toBe(0);
});

/**
 * There is deliberately no route that edits or removes a prototype version. Section 8's
 * immutability is a routing property before it is a runtime refusal.
 */
it('publishes no route for editing or deleting a state', function () {
    $version = PrototypeVersion::factory()->forPrototype($this->prototype)->numbered(1)->create();

    $this->actingAs($this->designer)->patchJson($this->base.'/1', ['name' => 'Rewritten'])
        ->assertMethodNotAllowed();

    $this->actingAs($this->designer)->deleteJson($this->base.'/1')
        ->assertMethodNotAllowed();

    expect($version->refresh()->name)->not->toBe('Rewritten');
});
