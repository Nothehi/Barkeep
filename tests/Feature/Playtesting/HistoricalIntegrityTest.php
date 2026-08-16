<?php

use Illuminate\Database\QueryException;
use Modules\GameDesign\Domain\Enums\DesignPhase;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * Playtesting is evidence, and evidence that changes when the thing it
 * describes changes is not evidence.
 *
 * These tests hold the line from both directions: a playtest keeps pointing at
 * the version that was actually on the table however far the design moves on,
 * and it stays readable after the project around it has been put away.
 */
beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();

    $this->game = Game::factory()
        ->inWorkspace($this->workspace)
        ->withSlug('bears-and-bridges')
        ->active()
        ->create();

    $this->version = GameVersion::factory()->nextFor($this->game)->create(['name' => 'Combat v3']);

    $this->playtest = Playtest::factory()
        ->forVersion($this->version)
        ->createdBy($this->designer)
        ->titled('Combat pacing')
        ->create();

    $this->session = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();

    PlaytestObservation::factory()->forSession($this->session)
        ->saying('Combat dragged in the third round.')
        ->create();

    $this->url = "/api/v1/workspaces/studio/games/bears-and-bridges/playtests/{$this->playtest->id}";
});

it('still names the version that was on the table after newer ones are cut', function () {
    GameVersion::factory()->nextFor($this->game)->create(['name' => 'Combat v4']);
    GameVersion::factory()->nextFor($this->game)->create(['name' => 'Combat v5']);

    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.game_version_id', $this->version->id)
        ->assertJsonPath('data.version.name', 'Combat v3');
});

it('stays readable after the game moves through its own lifecycle', function (string $status) {
    $this->game->forceFill(['status' => GameStatus::from($status)])->save();

    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.title', 'Combat pacing');
})->with(['on_hold', 'completed', 'draft']);

it('stays readable after the game moves on to a later design phase', function () {
    $this->game->forceFill(['design_phase' => DesignPhase::Production])->save();

    $this->actingAs($this->designer)->getJson($this->url)->assertOk();
});

/**
 * The important one. Archiving a project must not take its evidence with it —
 * the whole reason to keep old playtests is to read them after the design has
 * moved on.
 */
it('stays readable after the game is archived', function () {
    $this->game->forceFill(['status' => GameStatus::Archived])->save();

    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.title', 'Combat pacing')
        ->assertJsonPath('data.version.name', 'Combat v3');
});

it('still reads back the sessions and observations of an archived game', function () {
    $this->game->forceFill(['status' => GameStatus::Archived])->save();

    $this->actingAs($this->designer)
        ->getJson("{$this->url}/sessions")
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($this->designer)
        ->getJson("{$this->url}/sessions/{$this->session->id}/observations")
        ->assertOk()
        ->assertJsonPath('data.0.content', 'Combat dragged in the third round.');
});

it('still reports the summary of an archived game\'s playtest', function () {
    $this->game->forceFill(['status' => GameStatus::Archived])->save();

    $this->actingAs($this->designer)
        ->getJson("{$this->url}/summary")
        ->assertOk()
        ->assertJsonPath('data.observation_count', 1);
});

/**
 * Readable, not writable. An archived project has stopped changing, and so has
 * everything recorded about it.
 */
it('refuses new playtests against an archived game', function () {
    $this->game->forceFill(['status' => GameStatus::Archived])->save();

    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/studio/games/bears-and-bridges/playtests', [
            'game_version_id' => $this->version->id,
            'title' => 'One more thing',
            'objective' => 'Find out something about a project that is closed.',
        ])
        ->assertForbidden();
});

it('refuses new sessions and changes on an archived game\'s playtest', function () {
    $this->game->forceFill(['status' => GameStatus::Archived])->save();

    $this->actingAs($this->designer)->postJson("{$this->url}/sessions")->assertForbidden();
    $this->actingAs($this->designer)->patchJson($this->url, ['title' => 'Renamed'])->assertForbidden();
    $this->actingAs($this->designer)->postJson("{$this->url}/complete")->assertForbidden();
});

/**
 * The historical integrity rule, enforced by the database rather than by
 * convention: a version somebody actually played cannot be removed out from
 * under the record of them playing it.
 */
it('will not let a version be deleted while a playtest points at it', function () {
    expect(fn () => $this->version->delete())->toThrow(QueryException::class);

    expect(GameVersion::query()->whereKey($this->version->id)->exists())->toBeTrue();
});

it('lets an untested version be deleted freely', function () {
    $untested = GameVersion::factory()->nextFor($this->game)->create();

    $untested->delete();

    expect(GameVersion::query()->whereKey($untested->id)->exists())->toBeFalse();
});

/**
 * The counterpart: nothing about the version is copied into the playtest, so
 * a correction to a version's own description is visible everywhere at once
 * rather than only in the places somebody remembered to update.
 */
it('does not keep its own copy of the version', function () {
    $this->version->forceFill(['name' => 'Combat v3 (revised notes)'])->save();

    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.version.name', 'Combat v3 (revised notes)');
});
