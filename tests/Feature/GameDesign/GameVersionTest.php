<?php

use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Application\Commands\CreateGameVersion;
use Modules\GameDesign\Application\DTOs\CreateGameVersionData;
use Modules\GameDesign\Domain\Events\GameVersionCreated;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();

    $this->game = Game::factory()
        ->inWorkspace($this->workspace)
        ->withSlug('bears-and-bridges')
        ->active()
        ->create();
});

function cutVersion(array $payload = [])
{
    return test()->actingAs(test()->designer)
        ->postJson('/api/v1/workspaces/studio/games/bears-and-bridges/versions', $payload);
}

it('numbers a game\'s first version one', function () {
    cutVersion(['description' => 'The one that barely worked.'])
        ->assertCreated()
        ->assertJsonPath('data.version_number', 1)
        ->assertJsonPath('data.label', 'v1');
});

it('counts up as versions are cut', function () {
    cutVersion()->assertCreated()->assertJsonPath('data.version_number', 1);
    cutVersion()->assertCreated()->assertJsonPath('data.version_number', 2);
    cutVersion()->assertCreated()->assertJsonPath('data.version_number', 3);

    expect($this->game->versions()->pluck('version_number')->sort()->values()->all())
        ->toBe([1, 2, 3]);
});

/**
 * The rule that makes version numbers mean something: they are allocated, not
 * requested. A caller who sends one is ignored rather than obeyed.
 */
it('ignores a version number supplied by the client', function () {
    cutVersion(['version_number' => 999, 'description' => 'Cheeky.'])
        ->assertCreated()
        ->assertJsonPath('data.version_number', 1);

    expect(GameVersion::query()->where('version_number', 999)->exists())->toBeFalse();
});

it('records who cut the version and what changed', function () {
    cutVersion(['name' => 'Convention build', 'description' => 'Trimmed the endgame.'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Convention build')
        ->assertJsonPath('data.description', 'Trimmed the endgame.')
        ->assertJsonPath('data.created_by', $this->designer->id)
        ->assertJsonPath('data.creator.id', $this->designer->id);
});

it('accepts a version with no name', function () {
    cutVersion(['description' => 'Just a checkpoint.'])
        ->assertCreated()
        ->assertJsonPath('data.name', null);
});

it('lists a game\'s versions newest first', function () {
    cutVersion();
    cutVersion();
    cutVersion();

    test()->actingAs(test()->designer)
        ->getJson('/api/v1/workspaces/studio/games/bears-and-bridges/versions')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.version_number', 3)
        ->assertJsonPath('data.1.version_number', 2)
        ->assertJsonPath('data.2.version_number', 1);
});

it('shows one version by its number', function () {
    cutVersion(['description' => 'First.']);
    cutVersion(['description' => 'Second.']);

    test()->actingAs(test()->designer)
        ->getJson('/api/v1/workspaces/studio/games/bears-and-bridges/versions/2')
        ->assertOk()
        ->assertJsonPath('data.version_number', 2)
        ->assertJsonPath('data.description', 'Second.');
});

it('does not find a version that was never cut', function () {
    cutVersion();

    test()->actingAs(test()->designer)
        ->getJson('/api/v1/workspaces/studio/games/bears-and-bridges/versions/7')
        ->assertNotFound();
});

it('does not find a version whose number is not a number', function (string $segment) {
    cutVersion();

    test()->actingAs(test()->designer)
        ->getJson("/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$segment}")
        ->assertNotFound();
})->with([
    'a label' => 'v1',
    'a decimal' => '1.0',
    'padded' => '01',
    'zero' => '0',
    'negative' => '-1',
    'nonsense' => 'latest',
]);

it('reports the number the module allocated back to the caller', function () {
    Event::fake([GameVersionCreated::class]);

    cutVersion()->assertCreated();
    cutVersion()->assertCreated();

    Event::assertDispatched(
        GameVersionCreated::class,
        fn (GameVersionCreated $event) => $event->gameId === $this->game->id
            && $event->workspaceId === $this->workspace->id
            && $event->versionNumber === 2,
    );
});

/**
 * The database is the last line of defence for version numbering, and it has
 * to actually be there. Writing a duplicate directly must fail.
 */
it('refuses a duplicate version number at the database', function () {
    GameVersion::factory()->forGame($this->game)->numbered(1)->create();

    expect(fn () => GameVersion::factory()->forGame($this->game)->numbered(1)->create())
        ->toThrow(UniqueConstraintViolationException::class);
});

/**
 * Two games each get their own sequence. A number is only meaningful inside
 * one game, so both may have a v1.
 */
it('numbers each game independently', function () {
    $other = Game::factory()->inWorkspace($this->workspace)->withSlug('other-game')->active()->create();

    cutVersion()->assertCreated()->assertJsonPath('data.version_number', 1);

    test()->actingAs(test()->designer)
        ->postJson('/api/v1/workspaces/studio/games/other-game/versions')
        ->assertCreated()
        ->assertJsonPath('data.version_number', 1);

    expect($other->versions()->count())->toBe(1);
});

/**
 * Numbers are read from the table, not counted or cached, so versions that
 * arrived by any other route are still respected.
 */
it('allocates the number after whatever the game already has', function () {
    GameVersion::factory()->forGame($this->game)->numbered(1)->create();
    GameVersion::factory()->forGame($this->game)->numbered(2)->create();
    GameVersion::factory()->forGame($this->game)->numbered(3)->create();

    cutVersion()->assertCreated()->assertJsonPath('data.version_number', 4);
});

/**
 * The concurrency guard, exercised the only way a single-threaded test can.
 *
 * A real lost race looks like this: our caller reads the highest number,
 * another writer commits at that number first, and our insert is refused by
 * the unique constraint. Our transaction rolls back; theirs is already
 * committed and survives.
 *
 * That shape is reproduced by refusing the first insert and letting the
 * competing row land on the rollback — after our transaction is gone, so it
 * survives exactly as a committed one would. The command has to notice,
 * re-read, and take the number *after* theirs. Producing a second v2, or
 * giving up, are both failures.
 */
it('recovers when another writer takes the number first', function () {
    GameVersion::factory()->forGame($this->game)->numbered(1)->create();

    $game = $this->game;
    $contested = null;
    $competitorHasCommitted = false;

    /** Refuse our insert once, the way the unique index would. */
    GameVersion::creating(function (GameVersion $version) use ($game, &$contested) {
        if ($contested !== null || $version->game_id !== $game->getKey()) {
            return;
        }

        $contested = $version->version_number;

        throw new UniqueConstraintViolationException(
            'testing',
            'insert into "game_versions"',
            [],
            new RuntimeException('UNIQUE constraint failed: game_versions.game_id, game_versions.version_number'),
        );
    });

    /** The competitor's row appears once ours is rolled back, not before. */
    Event::listen(TransactionRolledBack::class, function () use ($game, &$contested, &$competitorHasCommitted) {
        if ($contested === null || $competitorHasCommitted) {
            return;
        }

        $competitorHasCommitted = true;

        GameVersion::factory()->forGame($game)->numbered($contested)->create();
    });

    try {
        $version = app(CreateGameVersion::class)->handle(
            $this->designer,
            $this->game,
            new CreateGameVersionData(description: 'Mine.'),
        );
    } finally {
        GameVersion::flushEventListeners();
    }

    expect($contested)->toBe(2)
        ->and($competitorHasCommitted)->toBeTrue()
        ->and($version->version_number)->toBe(3)
        ->and($this->game->versions()->pluck('version_number')->sort()->values()->all())
        ->toBe([1, 2, 3]);
});

it('records a version from the web screens and lands on it', function () {
    $this->actingAs($this->designer)
        ->post(route('games.versions.store', ['studio', 'bears-and-bridges']), [
            'description' => 'Convention build.',
        ])
        ->assertRedirect(route('games.versions.show', ['studio', 'bears-and-bridges', 1]));
});

/**
 * Which version is current is stated by the server rather than inferred by
 * the screen, so a page cannot get it wrong by counting.
 */
it('tells the version screen whether it is looking at the current one', function () {
    cutVersion();
    cutVersion();

    $this->actingAs($this->designer)
        ->get(route('games.versions.show', ['studio', 'bears-and-bridges', 2]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('games/version')->where('is_current', true));

    $this->actingAs($this->designer)
        ->get(route('games.versions.show', ['studio', 'bears-and-bridges', 1]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('games/version')->where('is_current', false));
});
