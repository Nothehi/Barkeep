<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Enums\DesignPhase;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Events\GameUpdated;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();
});

it('lists the workspace\'s games with what a card needs', function () {
    Game::factory()->inWorkspace($this->workspace)->named('Bears And Bridges')->active()->create();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'workspace_id', 'name', 'slug', 'description', 'status', 'status_label', 'design_phase', 'design_phase_label', 'design_phase_position', 'versions_count', 'updated_at']],
        ]);
});

it('shows a game with the shape the module documents', function () {
    Game::factory()->inWorkspace($this->workspace)->withSlug('bears-and-bridges')->create();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games/bears-and-bridges')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id', 'workspace_id', 'name', 'slug', 'description',
                'status', 'design_phase', 'created_by', 'created_at', 'updated_at',
                'permissions' => ['canView', 'canUpdate', 'canChangeStatus', 'canChangeDesignPhase', 'canArchive', 'canCreateVersion'],
                'available_transitions',
            ],
        ]);
});

it('updates a game\'s name, address and description', function () {
    $game = Game::factory()->inWorkspace($this->workspace)->withSlug('bears-and-bridges')->create();

    $this->actingAs($this->designer)
        ->patchJson('/api/v1/workspaces/studio/games/bears-and-bridges', [
            'name' => 'Bears & Beams',
            'slug' => 'bears-and-beams',
            'description' => 'Fewer planks.',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Bears & Beams')
        ->assertJsonPath('data.slug', 'bears-and-beams');

    expect($game->fresh()?->description)->toBe('Fewer planks.');
});

it('lets a game keep its own address when nothing else changes', function () {
    Game::factory()->inWorkspace($this->workspace)->withSlug('bears-and-bridges')->create();

    $this->actingAs($this->designer)
        ->patchJson('/api/v1/workspaces/studio/games/bears-and-bridges', [
            'name' => 'Bears & Bridges',
            'slug' => 'bears-and-bridges',
        ])
        ->assertOk();
});

it('refuses an address another game in the workspace already has', function () {
    Game::factory()->inWorkspace($this->workspace)->withSlug('bears-and-bridges')->create();
    Game::factory()->inWorkspace($this->workspace)->withSlug('otters-and-oars')->create();

    $this->actingAs($this->designer)
        ->patchJson('/api/v1/workspaces/studio/games/otters-and-oars', [
            'name' => 'Otters',
            'slug' => 'bears-and-bridges',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('slug');
});

/**
 * Status and design phase are moved by their own endpoints, so a rename must
 * not be able to carry one along and slip past the transition matrix.
 */
it('will not change a status or a phase through the update endpoint', function () {
    $game = Game::factory()->inWorkspace($this->workspace)->withSlug('bears-and-bridges')->create();

    $this->actingAs($this->designer)
        ->patchJson('/api/v1/workspaces/studio/games/bears-and-bridges', [
            'name' => 'Bears & Bridges',
            'slug' => 'bears-and-bridges',
            'status' => 'completed',
            'design_phase' => 'published',
        ])
        ->assertOk();

    expect($game->fresh()?->status)->toBe(GameStatus::Draft)
        ->and($game->fresh()?->design_phase)->toBe(DesignPhase::Idea);
});

it('announces only the attributes that actually changed', function () {
    Event::fake([GameUpdated::class]);

    $game = Game::factory()->inWorkspace($this->workspace)
        ->named('Bears And Bridges')
        ->create(['description' => 'Two bears, one river.']);

    $this->actingAs($this->designer)
        ->patchJson('/api/v1/workspaces/studio/games/bears-and-bridges', [
            'name' => 'Bears & Beams',
            'slug' => 'bears-and-bridges',
            'description' => $game->description,
        ])
        ->assertOk();

    Event::assertDispatched(
        GameUpdated::class,
        fn (GameUpdated $event) => $event->changed === ['name'],
    );
});

it('says nothing happened when an update changes nothing', function () {
    Event::fake([GameUpdated::class]);

    $game = Game::factory()->inWorkspace($this->workspace)
        ->named('Bears And Bridges')
        ->create(['description' => 'Two bears, one river.']);

    $this->actingAs($this->designer)
        ->patchJson('/api/v1/workspaces/studio/games/bears-and-bridges', [
            'name' => $game->name,
            'slug' => $game->slug,
            'description' => $game->description,
        ])
        ->assertOk();

    Event::assertNotDispatched(GameUpdated::class);
});

/**
 * The update is a replacement rather than a patch of whatever was sent, which
 * is how the workspace settings form behaves too: clearing the description
 * field and saving is how somebody removes a description.
 */
it('clears a description that was left out of the update', function () {
    $game = Game::factory()->inWorkspace($this->workspace)
        ->withSlug('bears-and-bridges')
        ->create(['description' => 'Two bears, one river.']);

    $this->actingAs($this->designer)
        ->patchJson('/api/v1/workspaces/studio/games/bears-and-bridges', [
            'name' => $game->name,
            'slug' => $game->slug,
        ])
        ->assertOk()
        ->assertJsonPath('data.description', null);
});

it('narrows the list by status', function () {
    Game::factory()->inWorkspace($this->workspace)->withSlug('one')->active()->create();
    Game::factory()->inWorkspace($this->workspace)->withSlug('two')->create();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games?status=active')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'one');
});

it('narrows the list by design phase', function () {
    Game::factory()->inWorkspace($this->workspace)->withSlug('one')->inPhase(DesignPhase::Playtesting)->create();
    Game::factory()->inWorkspace($this->workspace)->withSlug('two')->create();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games?design_phase=playtesting')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'one');
});

it('searches names and descriptions, ignoring case', function () {
    Game::factory()->inWorkspace($this->workspace)->named('Bears And Bridges')->create();
    Game::factory()->inWorkspace($this->workspace)->named('Otters And Oars')->create();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games?search=BEARS')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'bears-and-bridges');
});

/**
 * A wildcard typed into a search box is a character, not an instruction. If
 * it were not escaped, searching "%" would return everything.
 */
it('treats a wildcard in the search box as a literal character', function () {
    Game::factory()->inWorkspace($this->workspace)->named('Bears And Bridges')->create();
    Game::factory()->inWorkspace($this->workspace)->named('Fifty Percent')->create();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games?search=%25')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

/**
 * A filter is a view of a list, so a stale bookmark should show an unfiltered
 * list rather than an error page.
 */
it('ignores a filter value that names nothing', function () {
    Game::factory()->inWorkspace($this->workspace)->withSlug('one')->create();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games?status=shipped')
        ->assertUnprocessable();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games?status=')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns an empty list for a workspace with no games', function () {
    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('does not find a game that was never created', function () {
    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games/never-existed')
        ->assertNotFound();
});

it('does not find a game in a workspace that was never created', function () {
    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/no-such-studio/games')
        ->assertNotFound();
});

it('renders the game screens', function () {
    Game::factory()->inWorkspace($this->workspace)->withSlug('bears-and-bridges')->create();

    $this->actingAs($this->designer)->get(route('games.index', 'studio'))->assertOk();
    $this->actingAs($this->designer)->get(route('games.show', ['studio', 'bears-and-bridges']))->assertOk();
    $this->actingAs($this->designer)->get(route('games.settings.edit', ['studio', 'bears-and-bridges']))->assertOk();
    $this->actingAs($this->designer)->get(route('games.versions.index', ['studio', 'bears-and-bridges']))->assertOk();
});

/**
 * The overview is what the game dashboard renders, so it has to carry the
 * version count and the current version.
 */
it('gives the overview screen its version summary', function () {
    $game = Game::factory()->inWorkspace($this->workspace)->withSlug('bears-and-bridges')->active()->create();

    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/studio/games/bears-and-bridges/versions', ['description' => 'First.']);
    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/studio/games/bears-and-bridges/versions', ['description' => 'Second.']);

    $this->actingAs($this->designer)
        ->get(route('games.show', ['studio', 'bears-and-bridges']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('games/show')
            ->where('dashboard.versions_count', 2)
            ->where('dashboard.latest_version.data.version_number', 2)
            ->where('game.data.slug', 'bears-and-bridges'));

    expect($game->fresh()?->versions()->count())->toBe(2);
});
