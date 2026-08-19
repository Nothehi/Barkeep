<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\PrototypeStatus;
use Modules\PrototypeIteration\Domain\Enums\PrototypeType;
use Modules\PrototypeIteration\Domain\Events\PrototypeArchived;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\Workspace\Domain\Models\Workspace;

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();
    $this->game = Game::factory()->inWorkspace($this->workspace)->withSlug('bears')->active()->create();
    $this->prototype = Prototype::factory()->forGame($this->game)->named('Core Combat')->create();

    $this->base = "/api/v1/workspaces/studio/games/bears/prototypes/{$this->prototype->id}";
});

it('changes a prototype\'s name, description and kind', function () {
    $this->actingAs($this->designer)->patchJson($this->base, [
        'name' => 'Core Combat Prototype',
        'description' => 'Printed cards and 3D printed markers.',
        'type' => 'hybrid',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Core Combat Prototype')
        ->assertJsonPath('data.description', 'Printed cards and 3D printed markers.')
        ->assertJsonPath('data.type', PrototypeType::Hybrid->value);
});

it('clears a description when the request says to', function () {
    $this->prototype->forceFill(['description' => 'Something.'])->save();

    $this->actingAs($this->designer)->patchJson($this->base, ['description' => null])
        ->assertOk()
        ->assertJsonPath('data.description', null);
});

it('leaves a description alone when the request does not mention it', function () {
    $this->prototype->forceFill(['description' => 'Printed cards.'])->save();

    $this->actingAs($this->designer)->patchJson($this->base, ['name' => 'Renamed'])
        ->assertOk()
        ->assertJsonPath('data.description', 'Printed cards.');
});

/**
 * A prototype records the design state it was built from; rewriting that would change what every
 * iteration against it claims to have been working with.
 */
it('ignores an attempt to repoint a prototype at a different design version', function () {
    $originalVersion = $this->prototype->game_version_id;
    $newVersion = GameVersion::factory()->nextFor($this->game)->create();

    $this->actingAs($this->designer)->patchJson($this->base, ['game_version_id' => $newVersion->id])
        ->assertOk()
        ->assertJsonPath('data.game_version_id', $originalVersion);
});

/**
 * Archival is an action with its own endpoint, so an irreversible move is never one field value away from
 * a reversible one.
 */
it('ignores a status the update request tries to set', function () {
    $this->actingAs($this->designer)->patchJson($this->base, ['status' => 'archived'])
        ->assertOk()
        ->assertJsonPath('data.status', PrototypeStatus::Draft->value);
});

it('archives a prototype through its own action', function () {
    $this->actingAs($this->designer)->postJson($this->base.'/archive')
        ->assertOk()
        ->assertJsonPath('data.status', PrototypeStatus::Archived->value);
});

it('announces archival with how much rework it represents', function () {
    Event::fake([PrototypeArchived::class]);

    PrototypeVersion::factory()->forPrototype($this->prototype)->numbered(1)->create();
    PrototypeVersion::factory()->forPrototype($this->prototype)->numbered(2)->create();

    $this->actingAs($this->designer)->postJson($this->base.'/archive')->assertOk();

    Event::assertDispatched(fn (PrototypeArchived $event): bool => $event->prototypeId === $this->prototype->id
        && $event->gameId === $this->game->id
        && $event->versionCount === 2
        && $event->archivedBy === $this->designer->id);
});

it('archives a draft prototype directly, because abandonment is a real outcome', function () {
    expect($this->prototype->status)->toBe(PrototypeStatus::Draft);

    $this->actingAs($this->designer)->postJson($this->base.'/archive')
        ->assertOk()
        ->assertJsonPath('data.status', PrototypeStatus::Archived->value);
});

/**
 * Archival is terminal. A studio picking the approach back up creates a new prototype, which is also how
 * they would describe it — so there is no un-archive to test, only that nothing gets past.
 */
it('refuses every write on an archived prototype', function () {
    $archived = Prototype::factory()->forGame($this->game)->archived()->create();
    $base = "/api/v1/workspaces/studio/games/bears/prototypes/{$archived->id}";

    $this->actingAs($this->designer)->patchJson($base, ['name' => 'Revived'])->assertForbidden();
    $this->actingAs($this->designer)->postJson($base.'/archive')->assertForbidden();
    $this->actingAs($this->designer)->postJson($base.'/versions')->assertForbidden();

    expect($archived->refresh()->name)->not->toBe('Revived');
});

it('keeps an archived prototype readable', function () {
    $archived = Prototype::factory()->forGame($this->game)->archived()->create();

    $this->actingAs($this->designer)
        ->getJson("/api/v1/workspaces/studio/games/bears/prototypes/{$archived->id}")
        ->assertOk()
        ->assertJsonPath('data.status', PrototypeStatus::Archived->value)
        ->assertJsonCount(0, 'data.available_transitions');
});

it('offers archival as the only move on an open prototype', function () {
    $this->actingAs($this->designer)->getJson($this->base)
        ->assertOk()
        ->assertJsonCount(1, 'data.available_transitions')
        ->assertJsonPath('data.available_transitions.0.status', PrototypeStatus::Archived->value);
});

it('refuses to touch a prototype once the game is archived', function () {
    $this->game->forceFill(['status' => GameStatus::Archived])->save();

    $this->actingAs($this->designer)->patchJson($this->base, ['name' => 'Renamed'])->assertForbidden();
    $this->actingAs($this->designer)->postJson($this->base.'/versions')->assertForbidden();
});

it('keeps a prototype readable once the game is archived', function () {
    $this->game->forceFill(['status' => GameStatus::Archived])->save();

    $this->actingAs($this->designer)->getJson($this->base)->assertOk();
});

it('publishes no route for deleting a prototype', function () {
    $this->actingAs($this->designer)->deleteJson($this->base)->assertMethodNotAllowed();

    expect(Prototype::query()->count())->toBe(1);
});

it('filters the prototypes list by status and kind', function () {
    Prototype::factory()->forGame($this->game)->active()->ofType(PrototypeType::Digital)->create();
    Prototype::factory()->forGame($this->game)->archived()->create();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games/bears/prototypes?status=archived')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', PrototypeStatus::Archived->value);

    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games/bears/prototypes?type=digital')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', PrototypeType::Digital->value);
});

it('finds a prototype by what somebody remembers of it', function () {
    Prototype::factory()->forGame($this->game)->named('Hex tile draft')->create();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games/bears/prototypes?search=hex')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Hex tile draft');
});

it('treats a filter value that names nothing as no filter', function () {
    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games/bears/prototypes?status=')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
