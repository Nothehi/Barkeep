<?php

use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Domain\Enums\BalanceScenarioStatus;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Domain\Models\ScenarioVariable;
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

    $this->gold = BalanceVariable::factory()
        ->forProfile($this->profile)
        ->named('Starting gold')
        ->valued('10')
        ->create();

    $this->scenario = BalanceScenario::factory()->forProfile($this->profile)->named('Rich economy')->create();

    $this->base = "/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$this->version->version_number}"
        ."/balance-profiles/{$this->profile->id}";
});

it('states a value differently without touching the base variable', function () {
    $this->actingAs($this->designer)
        ->postJson("{$this->base}/scenarios/{$this->scenario->id}/variables", [
            'balance_variable_id' => $this->gold->id,
            'value' => '15',
        ])
        ->assertCreated()
        ->assertJsonPath('data.value', '15')
        ->assertJsonPath('data.base_value', '10')
        ->assertJsonPath('data.delta', '5');

    expect($this->gold->fresh()->value->label())->toBe('10');
});

it('replaces an existing override rather than storing two answers', function () {
    foreach (['15', '20'] as $value) {
        $this->actingAs($this->designer)
            ->postJson("{$this->base}/scenarios/{$this->scenario->id}/variables", [
                'balance_variable_id' => $this->gold->id,
                'value' => $value,
            ])
            ->assertSuccessful();
    }

    expect(ScenarioVariable::query()->count())->toBe(1)
        ->and(ScenarioVariable::query()->sole()->value->label())->toBe('20');
});

it('leaves the base variable alone when an override is removed', function () {
    $override = ScenarioVariable::factory()->of($this->scenario, $this->gold, '15')->create();

    $this->actingAs($this->designer)
        ->deleteJson("{$this->base}/scenarios/{$this->scenario->id}/variables/{$override->id}")
        ->assertNoContent();

    expect($this->gold->fresh()->value->label())->toBe('10')
        ->and(ScenarioVariable::query()->count())->toBe(0);
});

it('refuses an override naming a variable from another configuration', function () {
    $other = BalanceProfile::factory()->forVersion($this->version)->named('elsewhere')->create();
    $stranger = BalanceVariable::factory()->forProfile($other)->named('Starting gold')->create();

    $this->actingAs($this->designer)
        ->postJson("{$this->base}/scenarios/{$this->scenario->id}/variables", [
            'balance_variable_id' => $stranger->id,
            'value' => '15',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('balance_variable_id');
});

it('drops a scenario override when the variable it changed is removed', function () {
    ScenarioVariable::factory()->of($this->scenario, $this->gold, '15')->create();

    $this->actingAs($this->designer)
        ->deleteJson("{$this->base}/variables/{$this->gold->id}")
        ->assertNoContent();

    expect(ScenarioVariable::query()->count())->toBe(0);
});

it('lets several scenarios be active at once', function () {
    $second = BalanceScenario::factory()->forProfile($this->profile)->named('Poor economy')->create();

    foreach ([$this->scenario, $second] as $scenario) {
        $this->actingAs($this->designer)
            ->patchJson("{$this->base}/scenarios/{$scenario->id}", ['status' => 'active'])
            ->assertOk();
    }

    expect(BalanceScenario::query()->where('status', BalanceScenarioStatus::Active)->count())->toBe(2);
});

it('refuses to change an archived scenario', function () {
    $archived = BalanceScenario::factory()->forProfile($this->profile)->named('Old guess')->archived()->create();

    $this->actingAs($this->designer)
        ->postJson("{$this->base}/scenarios/{$archived->id}/variables", [
            'balance_variable_id' => $this->gold->id,
            'value' => '15',
        ])
        ->assertStatus(409);
});
