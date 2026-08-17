<?php

use Illuminate\Support\Facades\Event;
use Modules\DesignFramework\Domain\Enums\GameFrameworkStatus;
use Modules\DesignFramework\Domain\Events\GameFrameworkAssigned;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * A game taking up a methodology.
 *
 * The operation that joins the two halves of the product, and the only one on the game side that
 * names a framework at all — every screen after this resolves the edition through the adoption
 * instead. What it establishes is historical: the version captured here is the version this game
 * reads for as long as it exists.
 */
beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();

    $this->game = Game::factory()
        ->inWorkspace($this->workspace)
        ->withSlug('bears-and-bridges')
        ->active()
        ->create();

    $this->framework = Framework::factory()->withSlug('bgdf')->published()->create();
    $this->version = FrameworkVersion::factory()->nextFor($this->framework)->published()->create();
});

it('points a game at a published edition', function () {
    $this->actingAs($this->designer)
        ->post(route('games.framework.store', ['studio', 'bears-and-bridges']), [
            'framework_version_id' => $this->version->id,
        ])
        ->assertRedirect(route('games.framework.show', ['studio', 'bears-and-bridges']));

    $adoption = GameFramework::query()->sole();

    expect($adoption->game_id)->toBe($this->game->id)
        ->and($adoption->framework_version_id)->toBe($this->version->id)
        ->and($adoption->status)->toBe(GameFrameworkStatus::Active)
        ->and($adoption->adopted_by)->toBe($this->designer->id)
        ->and($adoption->started_at)->not->toBeNull();
});

it('announces the adoption', function () {
    Event::fake([GameFrameworkAssigned::class]);

    $this->actingAs($this->designer)
        ->post(route('games.framework.store', ['studio', 'bears-and-bridges']), [
            'framework_version_id' => $this->version->id,
        ]);

    Event::assertDispatched(
        fn (GameFrameworkAssigned $event): bool => $event->gameId === $this->game->id
            && $event->frameworkVersionId === $this->version->id
            && $event->adoptedBy === $this->designer->id,
    );
});

/**
 * A draft would change its questions underneath the game, which is the exact failure versioning
 * exists to prevent arrived at from the other direction.
 */
it('refuses a draft edition', function () {
    $draft = FrameworkVersion::factory()->nextFor($this->framework)->create();

    $this->actingAs($this->designer)
        ->post(route('games.framework.store', ['studio', 'bears-and-bridges']), [
            'framework_version_id' => $draft->id,
        ])
        ->assertSessionHasErrors('framework_version_id');

    expect(GameFramework::query()->count())->toBe(0);
});

it('refuses an archived edition', function () {
    $retired = FrameworkVersion::factory()->nextFor($this->framework)->archived()->create();

    $this->actingAs($this->designer)
        ->post(route('games.framework.store', ['studio', 'bears-and-bridges']), [
            'framework_version_id' => $retired->id,
        ])
        ->assertSessionHasErrors('framework_version_id');

    expect(GameFramework::query()->count())->toBe(0);
});

/**
 * The version can be published and still unadoptable, because the methodology around it was retired
 * after the fact. Both halves are checked, or a shelved framework would stay adoptable through any
 * edition published before it was shelved.
 */
it('refuses a published edition of an archived framework', function () {
    $retired = Framework::factory()->withSlug('retired')->archived()->create();
    $version = FrameworkVersion::factory()->nextFor($retired)->published()->create();

    $this->actingAs($this->designer)
        ->post(route('games.framework.store', ['studio', 'bears-and-bridges']), [
            'framework_version_id' => $version->id,
        ])
        ->assertSessionHasErrors('framework_version_id');

    expect(GameFramework::query()->count())->toBe(0);
});

/**
 * Not a limitation dressed up as a rule. Adopting a second framework over the first would be
 * migration — which has real decisions in it about what happens to evaluations already recorded —
 * done silently and badly.
 */
it('refuses a second framework while the game follows one', function () {
    GameFramework::factory()->forGame($this->game)->following($this->version)->create();

    $second = FrameworkVersion::factory()
        ->nextFor(Framework::factory()->withSlug('other')->published()->create())
        ->published()
        ->create();

    $this->actingAs($this->designer)
        ->post(route('games.framework.store', ['studio', 'bears-and-bridges']), [
            'framework_version_id' => $second->id,
        ])
        ->assertSessionHasErrors('framework_version_id');

    expect(GameFramework::query()->sole()->framework_version_id)->toBe($this->version->id);
});

it('refuses to start a methodology on an archived game', function () {
    $shelved = Game::factory()->inWorkspace($this->workspace)->withSlug('shelved')->archived()->create();

    $this->actingAs($this->designer)
        ->post(route('games.framework.store', ['studio', 'shelved']), [
            'framework_version_id' => $this->version->id,
        ])
        ->assertForbidden();

    expect(GameFramework::query()->count())->toBe(0);
});

it('refuses an edition id that names nothing', function () {
    $this->actingAs($this->designer)
        ->post(route('games.framework.store', ['studio', 'bears-and-bridges']), [
            'framework_version_id' => '00000000-0000-4000-8000-000000000000',
        ])
        ->assertNotFound();
});

it('refuses an edition id that is not a uuid', function () {
    $this->actingAs($this->designer)
        ->post(route('games.framework.store', ['studio', 'bears-and-bridges']), [
            'framework_version_id' => 'the-good-one',
        ])
        ->assertSessionHasErrors('framework_version_id');
});

/**
 * Choosing a methodology is design work rather than administration, so it is open to every member of
 * the workspace — the same standing that already lets them change the game.
 */
it('lets any member of the studio adopt a framework', function () {
    $teammate = User::factory()->create();

    $this->workspace->members()->create([
        'user_id' => $teammate->id,
        'role' => WorkspaceRole::Member,
        'joined_at' => now(),
    ]);

    $this->actingAs($teammate)
        ->post(route('games.framework.store', ['studio', 'bears-and-bridges']), [
            'framework_version_id' => $this->version->id,
        ])
        ->assertRedirect();

    expect(GameFramework::query()->sole()->adopted_by)->toBe($teammate->id);
});

it('hides the game from an outsider entirely', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->post(route('games.framework.store', ['studio', 'bears-and-bridges']), [
            'framework_version_id' => $this->version->id,
        ])
        ->assertNotFound();
});

it('turns away a caller with no session at all', function () {
    $this->post(route('games.framework.store', ['studio', 'bears-and-bridges']), [
        'framework_version_id' => $this->version->id,
    ])->assertRedirect(route('login'));
});

/**
 * The adopter is the signed in account rather than anything the caller sent. Every field in this
 * module that identifies a person comes from the request context.
 */
it('ignores an adopter supplied in the body', function () {
    $someoneElse = User::factory()->create();

    $this->actingAs($this->designer)
        ->post(route('games.framework.store', ['studio', 'bears-and-bridges']), [
            'framework_version_id' => $this->version->id,
            'adopted_by' => $someoneElse->id,
        ])
        ->assertRedirect();

    expect(GameFramework::query()->sole()->adopted_by)->toBe($this->designer->id);
});
