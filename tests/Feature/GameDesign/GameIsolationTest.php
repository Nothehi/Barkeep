<?php

use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceMember;

/**
 * The workspace boundary is what makes Barkeep safe to put two studios on, so
 * this file exists to try to cross it.
 *
 * Two accounts, two workspaces, a game in each. Every endpoint is asked
 * whether it will tell the wrong person something about the other side, or
 * act on it.
 *
 * An outsider gets 404 rather than 403 throughout. That is deliberate: 403
 * would confirm the game exists, which would turn a game address — a value
 * that appears in shareable links — into a way to enumerate what a studio is
 * working on.
 */
beforeEach(function () {
    $this->alice = User::factory()->create();
    $this->bob = User::factory()->create();

    $this->alpha = Workspace::factory()->ownedBy($this->alice)->withSlug('alpha')->create();
    $this->beta = Workspace::factory()->ownedBy($this->bob)->withSlug('beta')->create();

    $this->alphaGame = Game::factory()->inWorkspace($this->alpha)->withSlug('alpha-game')->create();
    $this->betaGame = Game::factory()->inWorkspace($this->beta)->withSlug('beta-game')->create();
});

it('does not admit that another workspace\'s game exists', function () {
    $this->actingAs($this->alice)
        ->getJson('/api/v1/workspaces/beta/games/beta-game')
        ->assertNotFound();

    $this->actingAs($this->bob)
        ->getJson('/api/v1/workspaces/alpha/games/alpha-game')
        ->assertNotFound();
});

it('lists only the games in the workspace being asked about', function () {
    $this->actingAs($this->alice)
        ->getJson('/api/v1/workspaces/alpha/games')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'alpha-game');

    $this->actingAs($this->bob)
        ->getJson('/api/v1/workspaces/beta/games')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'beta-game');
});

it('will not list another workspace\'s games', function () {
    $this->actingAs($this->alice)
        ->getJson('/api/v1/workspaces/beta/games')
        ->assertNotFound();
});

/**
 * The load-bearing case. Alice owns alpha, so she is allowed to do anything
 * she likes *there* — the question is whether naming her own workspace in the
 * URL lets her reach a game that lives in Bob's.
 *
 * The game address is resolved through the workspace in the URL, so Bob's
 * game is not found under Alpha at all. It fails at resolution rather than at
 * the policy, which is the stronger of the two answers.
 */
it('will not reach another workspace\'s game through a workspace the caller owns', function () {
    $this->actingAs($this->alice)
        ->getJson('/api/v1/workspaces/alpha/games/beta-game')
        ->assertNotFound();

    $this->actingAs($this->alice)
        ->patchJson('/api/v1/workspaces/alpha/games/beta-game', [
            'name' => 'Taken',
            'slug' => 'taken',
        ])
        ->assertNotFound();

    expect($this->betaGame->fresh()?->name)->not->toBe('Taken');
});

/**
 * The same attempt, made with the game's id rather than its address. Games
 * are addressed by slug, so an id is simply not a valid address and never
 * resolves — which is what closes the IDOR that ids in URLs usually open.
 */
it('will not resolve a game by its identifier', function () {
    $this->actingAs($this->bob)
        ->getJson("/api/v1/workspaces/beta/games/{$this->betaGame->id}")
        ->assertNotFound();
});

it('will not let an outsider change another workspace\'s game', function () {
    $this->actingAs($this->alice)
        ->patchJson('/api/v1/workspaces/beta/games/beta-game', [
            'name' => 'Taken',
            'slug' => 'taken',
        ])
        ->assertNotFound();

    expect($this->betaGame->fresh()?->name)->not->toBe('Taken');
});

it('will not let an outsider move another workspace\'s game through its lifecycle', function () {
    $this->actingAs($this->alice)
        ->postJson('/api/v1/workspaces/beta/games/beta-game/status', ['status' => 'active'])
        ->assertNotFound();

    $this->actingAs($this->alice)
        ->postJson('/api/v1/workspaces/beta/games/beta-game/design-phase', ['design_phase' => 'published'])
        ->assertNotFound();

    expect($this->betaGame->fresh()?->status->value)->toBe('draft')
        ->and($this->betaGame->fresh()?->design_phase->value)->toBe('idea');
});

it('will not let an outsider archive another workspace\'s game', function () {
    $this->actingAs($this->alice)
        ->postJson('/api/v1/workspaces/beta/games/beta-game/archive')
        ->assertNotFound();

    expect($this->betaGame->fresh()?->status->value)->toBe('draft');
});

it('will not let an outsider start a game in another workspace', function () {
    $this->actingAs($this->alice)
        ->postJson('/api/v1/workspaces/beta/games', ['name' => 'Trespass'])
        ->assertNotFound();

    expect(Game::query()->where('workspace_id', $this->beta->id)->count())->toBe(1);
});

it('will not show another workspace\'s versions', function () {
    GameVersion::factory()->forGame($this->betaGame)->create();

    $this->actingAs($this->alice)
        ->getJson('/api/v1/workspaces/beta/games/beta-game/versions')
        ->assertNotFound();
});

it('will not let an outsider record a version on another workspace\'s game', function () {
    $this->actingAs($this->alice)
        ->postJson('/api/v1/workspaces/beta/games/beta-game/versions', ['description' => 'Mine now.'])
        ->assertNotFound();

    expect(GameVersion::query()->count())->toBe(0);
});

/**
 * A version number is only meaningful inside one game, so asking for one
 * game's v1 through another game must not find it.
 */
it('will not read a version through the wrong game', function () {
    $alphaSecond = Game::factory()->inWorkspace($this->alpha)->withSlug('alpha-second')->create();

    GameVersion::factory()->forGame($this->alphaGame)->numbered(1)->create();

    $this->actingAs($this->alice)
        ->getJson('/api/v1/workspaces/alpha/games/alpha-second/versions/1')
        ->assertNotFound();

    expect($alphaSecond->versions()->count())->toBe(0);
});

it('hides another workspace\'s game screens as well as its endpoints', function () {
    $this->actingAs($this->alice)->get(route('games.index', 'beta'))->assertNotFound();
    $this->actingAs($this->alice)->get(route('games.show', ['beta', 'beta-game']))->assertNotFound();
    $this->actingAs($this->alice)->get(route('games.settings.edit', ['beta', 'beta-game']))->assertNotFound();
    $this->actingAs($this->alice)->get(route('games.versions.index', ['beta', 'beta-game']))->assertNotFound();
});

it('turns every game endpoint away from a guest', function () {
    $this->getJson('/api/v1/workspaces/alpha/games')->assertUnauthorized();
    $this->getJson('/api/v1/workspaces/alpha/games/alpha-game')->assertUnauthorized();
    $this->postJson('/api/v1/workspaces/alpha/games/alpha-game/archive')->assertUnauthorized();
    $this->getJson('/api/v1/workspaces/alpha/games/alpha-game/versions')->assertUnauthorized();
});

/**
 * A suspended workspace is closed to everybody in it, games included.
 */
it('hides the games in a suspended workspace from its own members', function () {
    $this->alpha->forceFill(['status' => 'suspended'])->save();

    $this->actingAs($this->alice)
        ->getJson('/api/v1/workspaces/alpha/games')
        ->assertNotFound();

    $this->actingAs($this->alice)
        ->getJson('/api/v1/workspaces/alpha/games/alpha-game')
        ->assertNotFound();
});

/**
 * An archived workspace stays readable — its history should not vanish — but
 * nothing inside it may change any more.
 */
it('freezes the games in an archived workspace without hiding them', function () {
    $this->alpha->forceFill(['status' => 'archived', 'archived_at' => now()])->save();

    $this->actingAs($this->alice)
        ->getJson('/api/v1/workspaces/alpha/games/alpha-game')
        ->assertOk();

    $this->actingAs($this->alice)
        ->patchJson('/api/v1/workspaces/alpha/games/alpha-game', ['name' => 'Renamed', 'slug' => 'renamed'])
        ->assertForbidden();

    $this->actingAs($this->alice)
        ->postJson('/api/v1/workspaces/alpha/games', ['name' => 'Something New'])
        ->assertForbidden();

    expect($this->alphaGame->fresh()?->name)->not->toBe('Renamed');
});

/**
 * Every member of a workspace can design in it. That is the model for now,
 * and it is what makes a shared studio work: a plain member is not a guest.
 */
it('lets any member of the workspace work on its games', function () {
    $member = User::factory()->create();

    WorkspaceMember::factory()->inWorkspace($this->alpha)->forUser($member)->create();

    $this->actingAs($member)->getJson('/api/v1/workspaces/alpha/games')->assertOk();
    $this->actingAs($member)->getJson('/api/v1/workspaces/alpha/games/alpha-game')->assertOk();

    $this->actingAs($member)
        ->postJson('/api/v1/workspaces/alpha/games', ['name' => 'Member Game'])
        ->assertCreated();

    $this->actingAs($member)
        ->patchJson('/api/v1/workspaces/alpha/games/alpha-game', [
            'name' => 'Renamed By Member',
            'slug' => 'alpha-game',
        ])
        ->assertOk();

    $this->actingAs($member)
        ->postJson('/api/v1/workspaces/alpha/games/alpha-game/versions', ['description' => 'First cut.'])
        ->assertCreated();
});

/**
 * Archiving is the exception: it ends a project for everybody and cannot be
 * undone, so it stays with the people who run the workspace. A member is
 * refused rather than hidden from — they can see the game, so 403 is the
 * honest answer.
 */
it('keeps a plain member from archiving a game', function () {
    $member = User::factory()->create();

    WorkspaceMember::factory()->inWorkspace($this->alpha)->forUser($member)->create();

    $this->actingAs($member)
        ->postJson('/api/v1/workspaces/alpha/games/alpha-game/archive')
        ->assertForbidden();

    expect($this->alphaGame->fresh()?->status->value)->toBe('draft');
});

it('lets a workspace admin archive a game', function () {
    $admin = User::factory()->create();

    WorkspaceMember::factory()->inWorkspace($this->alpha)->forUser($admin)->admin()->create();

    $this->actingAs($admin)
        ->postJson('/api/v1/workspaces/alpha/games/alpha-game/archive')
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');
});
