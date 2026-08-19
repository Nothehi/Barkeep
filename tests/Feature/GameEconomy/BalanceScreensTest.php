<?php

use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\CreateBalanceSnapshot;
use Modules\GameEconomy\Application\DTOs\BalanceSnapshotData;
use Modules\GameEconomy\Domain\Models\ActionCost;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\Models\ResourceFlow;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Domain\Models\ScenarioVariable;
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

    $this->profile = BalanceProfile::factory()
        ->forVersion($this->version)
        ->createdBy($this->designer)
        ->named('First pass')
        ->create();

    $this->wood = ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();
    $this->build = EconomyAction::factory()->forProfile($this->profile)->named('Build')->create();

    ResourceFlow::factory()->forResource($this->wood)->named('Harvest')->amounting('3')->create();
    ActionCost::factory()->of($this->build, $this->wood, '5')->create();

    $this->route = ['studio', 'bears-and-bridges', $this->version->version_number];
});

it('shows the balance profiles of a design version', function () {
    $this->actingAs($this->designer)
        ->get(route('balance.index', $this->route))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('balance/index')
                ->has('profiles.data', 1)
                ->where('profiles.data.0.name', 'First pass')
                ->where('can.create', true)
                ->has('version.data')
                ->has('options.resource_categories'),
        );
});

it('shows the dashboard with the whole configuration in one response', function () {
    $this->actingAs($this->designer)
        ->get(route('balance.show', [...$this->route, $this->profile]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('balance/show')
                ->where('profile.data.name', 'First pass')
                ->has('analysis.data.resources', 1)
                ->has('analysis.data.actions', 1)
                ->has('analysis.data.flows', 1)
                ->has('analysis.data.net_flows', 1)
                ->where('analysis.data.summary.resources', 1)
                ->where('analysis.data.summary.actions', 1)
                ->has('analysis.data.warnings')
                ->has('scenarios.data')
                ->has('assumptions.data')
                ->has('observations.data')
                ->has('snapshots.data')
                ->has('options.flow_types'),
        );
});

it('sends amounts as exact strings rather than as numbers', function () {
    BalanceVariable::factory()
        ->forProfile($this->profile)
        ->named('Spoilage chance')
        ->valued('0.125')
        ->create();

    $this->actingAs($this->designer)
        ->get(route('balance.show', [...$this->route, $this->profile]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('analysis.data.variables.0.value', '0.125')
                ->where('analysis.data.net_flows.0.generation', '3'),
        );
});

it('shows one resource with what fills and empties it', function () {
    $this->actingAs($this->designer)
        ->get(route('balance.resources.show', [...$this->route, $this->profile, $this->wood]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('balance/resource')
                ->where('resource.data.name', 'Wood')
                ->where('net_flow.data.generation', '3')
                ->where('net_flow.data.consumption', '5')
                ->where('net_flow.data.net', '-2')
                ->has('flows.data', 1)
                ->has('actions.data', 1),
        );
});

it('shows one action with its costs, rewards and effects', function () {
    $this->actingAs($this->designer)
        ->get(route('balance.actions.show', [...$this->route, $this->profile, $this->build]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('balance/action')
                ->where('action.data.name', 'Build')
                ->has('action.data.costs', 1)
                ->where('action.data.costs.0.amount', '5')
                ->where('action.data.costs.0.resource_name', 'Wood')
                ->has('resources.data', 1)
                ->where('profitability.data.has_cost', true)
                ->where('profitability.data.has_reward', false),
        );
});

it('shows a scenario\'s overrides beside the numbers they replace', function () {
    $variable = BalanceVariable::factory()
        ->forProfile($this->profile)
        ->named('Starting gold')
        ->valued('10')
        ->create();

    $scenario = BalanceScenario::factory()->forProfile($this->profile)->named('Rich economy')->create();

    ScenarioVariable::factory()->of($scenario, $variable, '15')->create();

    $this->actingAs($this->designer)
        ->get(route('balance.show', [...$this->route, $this->profile]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has('scenarios.data', 1)
                ->where('scenarios.data.0.overrides.0.base_value', '10')
                ->where('scenarios.data.0.overrides.0.value', '15')
                ->where('scenarios.data.0.overrides.0.delta', '5'),
        );
});

it('shows the difference between two snapshots', function () {
    $variable = BalanceVariable::factory()
        ->forProfile($this->profile)
        ->named('Starting gold')
        ->valued('10')
        ->create();

    $snapshots = app(CreateBalanceSnapshot::class);

    $first = $snapshots->handle($this->designer, $this->profile, new BalanceSnapshotData(name: 'v1.0'));

    $variable->value = Quantity::from('12');
    $variable->save();

    $second = $snapshots->handle($this->designer, $this->profile, new BalanceSnapshotData(name: 'v1.1'));

    $this->actingAs($this->designer)
        ->get(route('balance.snapshots.compare', [...$this->route, $this->profile, 'from' => $first->id, 'to' => $second->id]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('balance/comparison')
                ->where('comparison.data.from.name', 'v1.0')
                ->where('comparison.data.to.name', 'v1.1')
                ->has('comparison.data.variables', 1)
                ->where('comparison.data.variables.0.fields.0.before', '10')
                ->where('comparison.data.variables.0.fields.0.after', '12'),
        );
});

it('tells the client what it may do with the configuration', function () {
    $this->actingAs($this->designer)
        ->get(route('balance.show', [...$this->route, $this->profile]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('profile.data.permissions.canConfigure', true)
                ->where('profile.data.permissions.canCreateSnapshot', true),
        );
});

it('offers no configuration controls on an archived profile, and still allows a snapshot', function () {
    $archived = BalanceProfile::factory()
        ->forVersion($this->version)
        ->named('Shipped')
        ->archived()
        ->create();

    $this->actingAs($this->designer)
        ->get(route('balance.show', [...$this->route, $archived]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('profile.data.permissions.canConfigure', false)
                ->where('profile.data.permissions.canCreateSnapshot', true)
                ->where('profile.data.available_transitions', []),
        );
});

it('hides the balance screens from another studio', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->get(route('balance.index', $this->route))
        ->assertNotFound();

    $this->actingAs($outsider)
        ->get(route('balance.show', [...$this->route, $this->profile]))
        ->assertNotFound();
});
