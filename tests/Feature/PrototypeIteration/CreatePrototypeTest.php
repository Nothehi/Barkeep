<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\PrototypeStatus;
use Modules\PrototypeIteration\Domain\Enums\PrototypeType;
use Modules\PrototypeIteration\Domain\Events\PrototypeCreated;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\Workspace\Domain\Models\Workspace;

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();

    $this->game = Game::factory()
        ->inWorkspace($this->workspace)
        ->withSlug('bears-and-bridges')
        ->active()
        ->create();

    $this->version = GameVersion::factory()->nextFor($this->game)->create();
});

function createPrototype(array $payload = [])
{
    return test()->actingAs(test()->designer)->postJson(
        '/api/v1/workspaces/studio/games/bears-and-bridges/prototypes',
        array_merge([
            'game_version_id' => test()->version->id,
            'name' => 'Core Combat Prototype',
        ], $payload),
    );
}

it('starts a prototype against a version of the game design', function () {
    createPrototype(['description' => 'Printed cards and borrowed cubes.', 'type' => 'paper'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Core Combat Prototype')
        ->assertJsonPath('data.description', 'Printed cards and borrowed cubes.')
        ->assertJsonPath('data.type', PrototypeType::Paper->value)
        ->assertJsonPath('data.status', PrototypeStatus::Draft->value)
        ->assertJsonPath('data.game_id', $this->game->id)
        ->assertJsonPath('data.game_version_id', $this->version->id)
        ->assertJsonPath('data.created_by', $this->designer->id)
        ->assertJsonPath('data.versions_count', 0);
});

it('starts a prototype with no versions, because building one is a separate act', function () {
    createPrototype()->assertCreated();

    expect(Prototype::query()->sole()->versions()->count())->toBe(0);
});

it('defaults an unspecified kind to paper, which is what prototypes start as', function () {
    createPrototype(['type' => null])
        ->assertCreated()
        ->assertJsonPath('data.type', PrototypeType::Paper->value);
});

it('names the signed in account as the builder, whatever the body says', function () {
    $someoneElse = User::factory()->create();

    createPrototype(['created_by' => $someoneElse->id])
        ->assertCreated()
        ->assertJsonPath('data.created_by', $this->designer->id);
});

/**
 * Half the module's central invariant. A prototype whose design version came from another game
 * claims to implement a design nobody was working on.
 */
it('refuses a design version that belongs to a different game', function () {
    $otherGame = Game::factory()->inWorkspace($this->workspace)->withSlug('other-game')->active()->create();
    $otherVersion = GameVersion::factory()->nextFor($otherGame)->create();

    createPrototype(['game_version_id' => $otherVersion->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('game_version_id');

    expect(Prototype::query()->count())->toBe(0);
});

it('refuses a design version from another workspace entirely', function () {
    $outsider = User::factory()->create();
    $elsewhere = Workspace::factory()->ownedBy($outsider)->withSlug('rivals')->create();
    $theirGame = Game::factory()->inWorkspace($elsewhere)->active()->create();
    $theirVersion = GameVersion::factory()->nextFor($theirGame)->create();

    createPrototype(['game_version_id' => $theirVersion->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('game_version_id');
});

it('refuses a version id that names nothing', function () {
    createPrototype(['game_version_id' => 'not-a-uuid'])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('game_version_id');
});

it('requires a design version and a name', function (string $field) {
    createPrototype([$field => null])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor($field);
})->with(['game_version_id', 'name']);

it('refuses a kind it does not have', function () {
    createPrototype(['type' => 'holographic'])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('type');
});

it('announces the kind alongside the design version, not just the game', function () {
    Event::fake([PrototypeCreated::class]);

    createPrototype(['type' => 'digital'])->assertCreated();

    Event::assertDispatched(function (PrototypeCreated $event): bool {
        return $event->gameId === $this->game->id
            && $event->gameVersionId === $this->version->id
            && $event->type === PrototypeType::Digital
            && $event->createdBy === $this->designer->id;
    });
});

it('lists a game\'s prototypes newest first', function () {
    $older = Prototype::factory()->forGame($this->game)->named('Paper draft')->create([
        'created_at' => now()->subWeek(),
    ]);
    $newer = Prototype::factory()->forGame($this->game)->named('Digital model')->create();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games/bears-and-bridges/prototypes')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $newer->id)
        ->assertJsonPath('data.1.id', $older->id);
});
