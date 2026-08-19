<?php

use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\Workspace\Domain\Models\Workspace;

/*
|--------------------------------------------------------------------------
| The module's central invariant
|--------------------------------------------------------------------------
|
| An iteration names three things — a game, a design version and a prototype version — and all three
| have to describe the same project. Section 56 asks for exactly one of these cases by name:
|
|     Game A → Prototype A → Prototype Version A
|     Game B →              → Prototype Version B
|
|     Iteration(Game A, Prototype Version B)   must fail
|
| It has to fail, and the reason is worth being explicit about. A mismatched *design* version would
| be caught by GameDesign the moment anything looked at it. A mismatched *prototype* version is this
| module's own record, so nothing else in the platform would ever notice: the iteration would read
| perfectly, appear in the right list, and describe a cycle nobody ran against a build from somebody
| else's game. Every conclusion drawn from it would be attached to work that did not happen.
|
| Both studios below belong to the same owner in the first block, which is deliberate: the check has
| to hold on a game the caller *can* see, so that it is proved to be a domain invariant rather than
| a side effect of the tenancy rules.
|
*/

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();

    $this->gameA = Game::factory()->inWorkspace($this->workspace)->withSlug('game-a')->active()->create();
    $this->gameB = Game::factory()->inWorkspace($this->workspace)->withSlug('game-b')->active()->create();

    $this->versionA = GameVersion::factory()->nextFor($this->gameA)->create();
    $this->versionB = GameVersion::factory()->nextFor($this->gameB)->create();

    $this->prototypeA = Prototype::factory()->forVersion($this->versionA)->create();
    $this->prototypeB = Prototype::factory()->forVersion($this->versionB)->create();

    $this->buildA = PrototypeVersion::factory()->nextFor($this->prototypeA)->create();
    $this->buildB = PrototypeVersion::factory()->nextFor($this->prototypeB)->create();
});

function planIteration(string $gameSlug, array $payload = [])
{
    return test()->actingAs(test()->designer)->postJson(
        "/api/v1/workspaces/studio/games/{$gameSlug}/iterations",
        array_merge([
            'title' => 'Improve combat pacing',
            'objective' => 'Reduce the time players spend waiting between decisions.',
        ], $payload),
    );
}

it('plans a cycle when the game, the design version and the build all agree', function () {
    planIteration('game-a', [
        'game_version_id' => $this->versionA->id,
        'prototype_version_id' => $this->buildA->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.game_id', $this->gameA->id)
        ->assertJsonPath('data.game_version_id', $this->versionA->id)
        ->assertJsonPath('data.prototype_version_id', $this->buildA->id);
});

/**
 * The case section 56 names. This is the test the module exists to pass.
 */
it('refuses an iteration pointing at another game\'s prototype version', function () {
    planIteration('game-a', [
        'game_version_id' => $this->versionA->id,
        'prototype_version_id' => $this->buildB->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('prototype_version_id');

    expect(Iteration::query()->count())->toBe(0);
});

it('refuses an iteration pointing at another game\'s design version', function () {
    planIteration('game-a', [
        'game_version_id' => $this->versionB->id,
        'prototype_version_id' => $this->buildA->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('game_version_id');

    expect(Iteration::query()->count())->toBe(0);
});

it('refuses an iteration whose design version and build come from a different game each', function () {
    planIteration('game-a', [
        'game_version_id' => $this->versionB->id,
        'prototype_version_id' => $this->buildB->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('game_version_id')
        ->assertJsonValidationErrorFor('prototype_version_id');

    expect(Iteration::query()->count())->toBe(0);
});

it('refuses a prototype version from another workspace entirely', function () {
    $outsider = User::factory()->create();
    $elsewhere = Workspace::factory()->ownedBy($outsider)->withSlug('rivals')->create();
    $theirGame = Game::factory()->inWorkspace($elsewhere)->active()->create();
    $theirVersion = GameVersion::factory()->nextFor($theirGame)->create();
    $theirPrototype = Prototype::factory()->forVersion($theirVersion)->create();
    $theirBuild = PrototypeVersion::factory()->nextFor($theirPrototype)->create();

    planIteration('game-a', [
        'game_version_id' => $this->versionA->id,
        'prototype_version_id' => $theirBuild->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('prototype_version_id');
});

it('refuses a prototype version id that names nothing', function () {
    planIteration('game-a', [
        'game_version_id' => $this->versionA->id,
        'prototype_version_id' => 'not-a-uuid',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('prototype_version_id');
});

/**
 * The update route is a second door into the same invariant. A command that trusted an id because it
 * had been validated once before would be exactly where the forgery got in.
 */
it('refuses to repoint an open cycle at another game\'s prototype version', function () {
    $iteration = Iteration::factory()->forPrototypeVersion($this->buildA)->create([
        'created_by' => $this->designer->id,
    ]);

    $this->actingAs($this->designer)
        ->patchJson("/api/v1/workspaces/studio/games/game-a/iterations/{$iteration->id}", [
            'prototype_version_id' => $this->buildB->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('prototype_version_id');

    expect($iteration->refresh()->prototype_version_id)->toBe($this->buildA->id);
});

it('lets an open cycle be repointed at another build of the same game', function () {
    $iteration = Iteration::factory()->forPrototypeVersion($this->buildA)->create([
        'created_by' => $this->designer->id,
    ]);

    $secondBuild = PrototypeVersion::factory()->nextFor($this->prototypeA)->create();

    $this->actingAs($this->designer)
        ->patchJson("/api/v1/workspaces/studio/games/game-a/iterations/{$iteration->id}", [
            'prototype_version_id' => $secondBuild->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.prototype_version_id', $secondBuild->id);
});

/**
 * The factory is held to the same invariant, because a factory that could build the forgery would let
 * every other test in the suite be written against data no command could produce.
 */
it('builds a consistent triple by default', function () {
    $iteration = Iteration::factory()->create();

    $build = PrototypeVersion::query()->findOrFail($iteration->prototype_version_id);
    $prototype = Prototype::query()->findOrFail($build->prototype_id);
    $version = GameVersion::query()->findOrFail($iteration->game_version_id);

    expect($prototype->game_id)->toBe($iteration->game_id)
        ->and($version->game_id)->toBe($iteration->game_id);
});

it('keeps the triple consistent when built for a game', function () {
    $iteration = Iteration::factory()->forGame($this->gameA)->create();

    $build = PrototypeVersion::query()->findOrFail($iteration->prototype_version_id);
    $prototype = Prototype::query()->findOrFail($build->prototype_id);

    expect($iteration->game_id)->toBe($this->gameA->id)
        ->and($prototype->game_id)->toBe($this->gameA->id);
});
