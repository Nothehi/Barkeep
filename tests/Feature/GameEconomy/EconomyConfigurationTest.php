<?php

use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Domain\Models\ActionCost;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\Models\ResourceType;
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

function profileUrl(?BalanceProfile $profile = null): string
{
    $profile ??= test()->profile;
    $version = test()->version;

    return "/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$version->version_number}/balance-profiles/{$profile->id}";
}

it('declares a resource and derives its handle from the name', function () {
    $this->actingAs($this->designer)
        ->postJson(profileUrl().'/resources', [
            'name' => 'Action Points',
            'category' => 'action',
            'is_tradeable' => false,
            'is_accumulative' => false,
            'starting_value' => '3',
        ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'action_points')
        ->assertJsonPath('data.category', 'action')
        ->assertJsonPath('data.is_tradeable', false)
        ->assertJsonPath('data.starting_value', '3');
});

it('refuses a second resource whose name produces the same handle', function () {
    ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();

    $this->actingAs($this->designer)
        ->postJson(profileUrl().'/resources', ['name' => 'wood'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'This balance profile already has a resource with that name.');
});

it('refuses a flow naming a resource from another configuration', function () {
    $other = BalanceProfile::factory()->forVersion($this->version)->named('elsewhere')->create();
    $stranger = ResourceType::factory()->forProfile($other)->named('Gold')->create();

    $this->actingAs($this->designer)
        ->postJson(profileUrl().'/flows', [
            'resource_type_id' => $stranger->id,
            'name' => 'Income',
            'amount' => '2',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('resource_type_id');
});

it('stores a flow amount as a magnitude, with the direction on the flow type', function () {
    $wood = ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();

    $this->actingAs($this->designer)
        ->postJson(profileUrl().'/flows', [
            'resource_type_id' => $wood->id,
            'name' => 'Upkeep',
            'flow_type' => 'consumption',
            'amount' => '2',
        ])
        ->assertCreated()
        ->assertJsonPath('data.amount', '2')
        ->assertJsonPath('data.signed_amount', '-2')
        ->assertJsonPath('data.direction', -1);
});

it('prices an action in a resource', function () {
    $action = EconomyAction::factory()->forProfile($this->profile)->named('Build')->create();
    $wood = ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();

    $this->actingAs($this->designer)
        ->postJson(profileUrl()."/actions/{$action->id}/costs", [
            'resource_type_id' => $wood->id,
            'amount' => '5',
        ])
        ->assertCreated()
        ->assertJsonPath('data.amount', '5')
        ->assertJsonPath('data.resource_name', 'Wood');
});

it('refuses a second cost line for a resource the action already names', function () {
    $action = EconomyAction::factory()->forProfile($this->profile)->named('Build')->create();
    $wood = ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();

    ActionCost::factory()->of($action, $wood, '2')->create();

    $this->actingAs($this->designer)
        ->postJson(profileUrl()."/actions/{$action->id}/costs", [
            'resource_type_id' => $wood->id,
            'amount' => '3',
        ])
        ->assertStatus(409);
});

it('refuses to price an action in a resource from another configuration', function () {
    $action = EconomyAction::factory()->forProfile($this->profile)->named('Build')->create();
    $other = BalanceProfile::factory()->forVersion($this->version)->named('elsewhere')->create();
    $stranger = ResourceType::factory()->forProfile($other)->named('Gold')->create();

    $this->actingAs($this->designer)
        ->postJson(profileUrl()."/actions/{$action->id}/costs", [
            'resource_type_id' => $stranger->id,
            'amount' => '1',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('resource_type_id');
});

it('refuses to delete a resource anything is priced in, and says how much depends on it', function () {
    $action = EconomyAction::factory()->forProfile($this->profile)->named('Build')->create();
    $wood = ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();
    ActionCost::factory()->of($action, $wood, '5')->create();

    $this->actingAs($this->designer)
        ->deleteJson(profileUrl()."/resources/{$wood->id}")
        ->assertStatus(409);

    expect(ResourceType::query()->whereKey($wood->id)->exists())->toBeTrue();
});

it('deletes a resource nothing depends on', function () {
    $wood = ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();

    $this->actingAs($this->designer)
        ->deleteJson(profileUrl()."/resources/{$wood->id}")
        ->assertNoContent();

    expect(ResourceType::query()->whereKey($wood->id)->exists())->toBeFalse();
});

it('keeps a decimal value exactly as it was typed', function () {
    $this->actingAs($this->designer)
        ->postJson(profileUrl().'/variables', [
            'name' => 'Spoilage chance',
            'value' => '0.125',
            'category' => 'probability',
        ])
        ->assertCreated()
        ->assertJsonPath('data.value', '0.125');

    expect(BalanceVariable::query()->sole()->value->label())->toBe('0.125');
});

it('lets a variable be edited one field at a time without clearing the rest', function () {
    $variable = BalanceVariable::factory()
        ->forProfile($this->profile)
        ->named('Starting gold')
        ->valued('10')
        ->bounded('0', '30')
        ->create(['unit' => 'gold']);

    $this->actingAs($this->designer)
        ->patchJson(profileUrl()."/variables/{$variable->id}", ['value' => '12'])
        ->assertOk()
        ->assertJsonPath('data.value', '12')
        ->assertJsonPath('data.unit', 'gold')
        ->assertJsonPath('data.min_value', '0')
        ->assertJsonPath('data.max_value', '30');
});

it('records a variable outside its own range rather than refusing it', function () {
    $variable = BalanceVariable::factory()
        ->forProfile($this->profile)
        ->named('Starting gold')
        ->valued('10')
        ->bounded('0', '30')
        ->create();

    $this->actingAs($this->designer)
        ->patchJson(profileUrl()."/variables/{$variable->id}", ['value' => '99'])
        ->assertOk()
        ->assertJsonPath('data.is_within_range', false);
});

it('refuses a variable pointing at an action from another configuration', function () {
    $other = BalanceProfile::factory()->forVersion($this->version)->named('elsewhere')->create();
    $stranger = EconomyAction::factory()->forProfile($other)->named('Trade')->create();

    $this->actingAs($this->designer)
        ->postJson(profileUrl().'/variables', [
            'name' => 'Trade rate',
            'value' => '2',
            'action_id' => $stranger->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('action_id');
});
