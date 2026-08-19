<?php

use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\CreateBalanceSnapshot;
use Modules\GameEconomy\Application\DTOs\BalanceSnapshotData;
use Modules\GameEconomy\Application\Queries\CompareBalanceSnapshots;
use Modules\GameEconomy\Domain\Enums\SnapshotChangeType;
use Modules\GameEconomy\Domain\Exceptions\SnapshotsAreNotComparable;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceSnapshot;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;
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
    $this->profile = BalanceProfile::factory()->forVersion($this->version)->create();
});

function takeSnapshot(string $name): BalanceSnapshot
{
    return app(CreateBalanceSnapshot::class)->handle(
        test()->designer,
        test()->profile,
        new BalanceSnapshotData(name: $name),
    );
}

it('freezes the configuration as it stands', function () {
    ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();
    BalanceVariable::factory()->forProfile($this->profile)->named('Starting gold')->valued('10')->create();

    $snapshot = takeSnapshot('v1.0');

    expect($snapshot->tally())->toMatchArray(['resources' => 1, 'variables' => 1]);
});

it('keeps a snapshot readable after the records it describes are gone', function () {
    $wood = ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();

    $snapshot = takeSnapshot('v1.0');

    $wood->delete();

    expect($snapshot->fresh()->snapshot_data['resources'][0]['name'])->toBe('Wood');
});

it('refuses to rewrite a snapshot', function () {
    $snapshot = takeSnapshot('v1.0');

    $snapshot->name = 'tampered';
    $saved = $snapshot->save();

    expect($saved)->toBeFalse()
        ->and($snapshot->fresh()->name)->toBe('v1.0');
});

it('reports a changed variable with both sides', function () {
    $variable = BalanceVariable::factory()
        ->forProfile($this->profile)
        ->named('Starting gold')
        ->valued('10')
        ->create();

    $first = takeSnapshot('v1.0');

    $variable->value = Quantity::from('12');
    $variable->save();

    $second = takeSnapshot('v1.1');

    $comparison = app(CompareBalanceSnapshots::class)->handle($first, $second);

    expect($comparison->variables)->toHaveCount(1)
        ->and($comparison->variables[0]->type)->toBe(SnapshotChangeType::Changed)
        ->and($comparison->variables[0]->fields[0]->before)->toBe('10')
        ->and($comparison->variables[0]->fields[0]->after)->toBe('12');
});

it('reports an added and a removed resource', function () {
    $wood = ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();
    $first = takeSnapshot('v1.0');

    $wood->delete();
    ResourceType::factory()->forProfile($this->profile)->named('Clay')->create();

    $second = takeSnapshot('v1.1');

    $comparison = app(CompareBalanceSnapshots::class)->handle($first, $second);
    $types = array_map(fn ($change) => $change->type, $comparison->resources);

    expect($types)->toContain(SnapshotChangeType::Removed)
        ->toContain(SnapshotChangeType::Added);
});

it('sees no change between two snapshots of an untouched configuration', function () {
    ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();

    $comparison = app(CompareBalanceSnapshots::class)->handle(
        takeSnapshot('v1.0'),
        takeSnapshot('v1.1'),
    );

    expect($comparison->isIdentical())->toBeTrue();
});

it('refuses to compare a snapshot with itself', function () {
    $snapshot = takeSnapshot('v1.0');

    expect(fn () => app(CompareBalanceSnapshots::class)->handle($snapshot, $snapshot))
        ->toThrow(SnapshotsAreNotComparable::class);
});

it('refuses to compare snapshots from different configurations', function () {
    $other = BalanceProfile::factory()->forVersion($this->version)->named('elsewhere')->create();

    $mine = takeSnapshot('v1.0');
    $theirs = BalanceSnapshot::factory()->forProfile($other)->create();

    expect(fn () => app(CompareBalanceSnapshots::class)->handle($mine, $theirs))
        ->toThrow(SnapshotsAreNotComparable::class);
});

it('lets an archived configuration still be snapshotted', function () {
    $archived = BalanceProfile::factory()->forVersion($this->version)->named('shipped')->archived()->create();
    $version = $this->version->version_number;

    $this->actingAs($this->designer)
        ->postJson(
            "/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$version}/balance-profiles/{$archived->id}/snapshots",
            ['name' => 'as shipped'],
        )
        ->assertCreated();
});

it('does not resolve a snapshot from another configuration when comparing', function () {
    $other = BalanceProfile::factory()->forVersion($this->version)->named('elsewhere')->create();

    $mine = takeSnapshot('v1.0');
    $theirs = BalanceSnapshot::factory()->forProfile($other)->create();

    $version = $this->version->version_number;

    $this->actingAs($this->designer)
        ->getJson(
            "/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$version}/balance-profiles/{$this->profile->id}/snapshots/compare"
            ."?from={$mine->id}&to={$theirs->id}",
        )
        ->assertNotFound();
});
