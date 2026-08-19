<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Domain\Enums\BalanceProfileStatus;
use Modules\GameEconomy\Domain\Events\BalanceProfileActivated;
use Modules\GameEconomy\Domain\Events\BalanceProfileCreated;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
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

    $this->version = GameVersion::factory()->nextFor($this->game)->create();
});

function profilesUrl(?GameVersion $version = null): string
{
    $version ??= test()->version;

    return "/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$version->version_number}/balance-profiles";
}

function createProfile(array $payload = [])
{
    return test()->actingAs(test()->designer)->postJson(
        profilesUrl(),
        array_merge(['name' => 'First pass'], $payload),
    );
}

it('starts a configuration against one design state', function () {
    Event::fake([BalanceProfileCreated::class]);

    createProfile(['description' => 'Numbers for the convention build.'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'First pass')
        ->assertJsonPath('data.status', BalanceProfileStatus::Draft->value)
        ->assertJsonPath('data.game_version_id', $this->version->id)
        ->assertJsonPath('data.created_by', $this->designer->id);

    Event::assertDispatched(BalanceProfileCreated::class);
});

it('starts a configuration empty, because the platform does not decide what resources a game has', function () {
    createProfile()->assertCreated();

    $profile = BalanceProfile::query()->sole();

    expect($profile->resources()->count())->toBe(0)
        ->and($profile->actions()->count())->toBe(0)
        ->and($profile->variables()->count())->toBe(0);
});

it('refuses a second configuration with the same name on one design state', function () {
    createProfile()->assertCreated();

    createProfile()
        ->assertStatus(422)
        ->assertJsonPath('message', 'This version already has a balance profile with that name.');
});

it('allows the same name on a different design state, because the numbers are per version', function () {
    createProfile()->assertCreated();

    $next = GameVersion::factory()->nextFor($this->game)->create();

    $this->actingAs($this->designer)
        ->postJson(profilesUrl($next), ['name' => 'First pass'])
        ->assertCreated();

    expect(BalanceProfile::query()->count())->toBe(2);
});

it('retires the configuration in play when another is activated', function () {
    Event::fake([BalanceProfileActivated::class]);

    $first = BalanceProfile::factory()->forVersion($this->version)->named('v1 numbers')->create();
    $second = BalanceProfile::factory()->forVersion($this->version)->named('v2 numbers')->create();

    $this->actingAs($this->designer)
        ->postJson(profilesUrl()."/{$first->id}/activate")
        ->assertOk()
        ->assertJsonPath('data.status', BalanceProfileStatus::Active->value);

    $this->actingAs($this->designer)
        ->postJson(profilesUrl()."/{$second->id}/activate")
        ->assertOk();

    expect($first->fresh()->status)->toBe(BalanceProfileStatus::Archived)
        ->and($second->fresh()->status)->toBe(BalanceProfileStatus::Active);

    Event::assertDispatchedTimes(BalanceProfileActivated::class, 2);
});

it('leaves one active configuration per design state at most', function () {
    $first = BalanceProfile::factory()->forVersion($this->version)->named('one')->create();
    $second = BalanceProfile::factory()->forVersion($this->version)->named('two')->create();

    foreach ([$first, $second] as $profile) {
        $this->actingAs($this->designer)->postJson(profilesUrl()."/{$profile->id}/activate")->assertOk();
    }

    expect(BalanceProfile::query()->where('status', BalanceProfileStatus::Active)->count())->toBe(1);
});

it('refuses to bring an archived configuration back into play', function () {
    $profile = BalanceProfile::factory()->forVersion($this->version)->archived()->create();

    $this->actingAs($this->designer)
        ->postJson(profilesUrl()."/{$profile->id}/activate")
        ->assertStatus(403);

    expect($profile->fresh()->status)->toBe(BalanceProfileStatus::Archived);
});

it('keeps an archived configuration readable', function () {
    $profile = BalanceProfile::factory()->forVersion($this->version)->archived()->create();

    $this->actingAs($this->designer)
        ->getJson(profilesUrl()."/{$profile->id}")
        ->assertOk()
        ->assertJsonPath('data.status', BalanceProfileStatus::Archived->value);
});

it('refuses to configure an archived configuration', function () {
    $profile = BalanceProfile::factory()->forVersion($this->version)->archived()->create();

    $this->actingAs($this->designer)
        ->postJson(profilesUrl()."/{$profile->id}/resources", ['name' => 'Wood'])
        ->assertStatus(403);
});

it('hides a configuration belonging to another studio', function () {
    $outsider = User::factory()->create();
    $profile = BalanceProfile::factory()->forVersion($this->version)->create();

    $this->actingAs($outsider)
        ->getJson(profilesUrl()."/{$profile->id}")
        ->assertNotFound();
});

it('does not resolve a configuration through a different design state', function () {
    $other = GameVersion::factory()->nextFor($this->game)->create();
    $profile = BalanceProfile::factory()->forVersion($this->version)->create();

    $this->actingAs($this->designer)
        ->getJson(profilesUrl($other)."/{$profile->id}")
        ->assertNotFound();
});
