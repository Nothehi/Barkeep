<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\AnalyseBalanceProfile;
use Modules\GameEconomy\Domain\Enums\BalanceVariableCategory;
use Modules\GameEconomy\Domain\Enums\BalanceWarningCode;
use Modules\GameEconomy\Domain\Enums\ResourceFlowType;
use Modules\GameEconomy\Domain\Events\BalanceAnalysed;
use Modules\GameEconomy\Domain\Models\ActionCost;
use Modules\GameEconomy\Domain\Models\ActionReward;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\Models\ResourceFlow;
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

/**
 * The findings the analysis produced, by code.
 *
 * @return list<string>
 */
function codesFor(BalanceProfile $profile): array
{
    $analysis = app(AnalyseBalanceProfile::class)->handle($profile, announce: false);

    return array_map(fn ($warning): string => $warning->code->value, $analysis->warnings);
}

it('counts both the declared flows and what actions do', function () {
    $wood = ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();

    ResourceFlow::factory()->forResource($wood)->ofType(ResourceFlowType::Generation)->amounting('12')->create();

    $build = EconomyAction::factory()->forProfile($this->profile)->named('Build')->create();
    ActionCost::factory()->of($build, $wood, '8')->create();

    $analysis = app(AnalyseBalanceProfile::class)->handle($this->profile, announce: false);
    $flow = $analysis->netFlowFor($wood);

    expect($flow->generation->label())->toBe('12')
        ->and($flow->consumption->label())->toBe('8')
        ->and($flow->net()->label())->toBe('4');
});

it('reports a resource nothing generates as an error', function () {
    ResourceType::factory()->forProfile($this->profile)->named('Mana')->create();

    expect(codesFor($this->profile))->toContain(BalanceWarningCode::ResourceHasNoGeneration->value);

    expect(BalanceWarningCode::ResourceHasNoGeneration->severity()->isError())->toBeTrue();
});

it('reports a resource that arrives and never leaves', function () {
    $wood = ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();
    ResourceFlow::factory()->forResource($wood)->amounting('3')->create();

    expect(codesFor($this->profile))
        ->toContain(BalanceWarningCode::ResourceAccumulatesWithoutSink->value)
        ->toContain(BalanceWarningCode::ResourceGenerationIsUncapped->value);
});

it('reports an action that costs nothing', function () {
    ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();
    EconomyAction::factory()->forProfile($this->profile)->named('Rest')->create();

    expect(codesFor($this->profile))->toContain(BalanceWarningCode::ActionHasNoCost->value);
});

it('does not call an action pointless when it has an effect but no reward', function () {
    $wood = ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();
    $build = EconomyAction::factory()->forProfile($this->profile)->named('Build')->create();

    ActionCost::factory()->of($build, $wood, '5')->create();
    $build->effects()->create([
        'effect_type' => 'unlock',
        'target' => 'Building II',
    ]);

    expect(codesFor($this->profile))->not->toContain(BalanceWarningCode::ActionHasNoOutcome->value);
});

it('reports an action that returns more of a resource than it takes', function () {
    $wood = ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();
    $mill = EconomyAction::factory()->forProfile($this->profile)->named('Mill')->create();

    ActionCost::factory()->of($mill, $wood, '2')->create();
    ActionReward::factory()->of($mill, $wood, '3')->create();

    expect(codesFor($this->profile))->toContain(BalanceWarningCode::ActionMultipliesAResource->value);
});

it('reports a reward paying out more than the resource allows', function () {
    $gold = ResourceType::factory()->forProfile($this->profile)->named('Gold')->bounded('0', '10')->create();
    $sell = EconomyAction::factory()->forProfile($this->profile)->named('Sell')->create();

    ActionReward::factory()->of($sell, $gold, '25')->create();

    expect(codesFor($this->profile))->toContain(BalanceWarningCode::ActionRewardExceedsMaximum->value);
});

it('reports a probability written outside zero to one', function () {
    BalanceVariable::factory()
        ->forProfile($this->profile)
        ->named('Spoilage chance')
        ->valued('25')
        ->inCategory(BalanceVariableCategory::Probability)
        ->create();

    expect(codesFor($this->profile))->toContain(BalanceWarningCode::ProbabilityIsOutsideZeroToOne->value);
});

it('never changes a value while analysing', function () {
    $wood = ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();
    $variable = BalanceVariable::factory()
        ->forProfile($this->profile)
        ->named('Wood production')
        ->valued('3')
        ->bounded('0', '2')
        ->create();

    app(AnalyseBalanceProfile::class)->handle($this->profile);

    expect($variable->fresh()->value->label())->toBe('3')
        ->and($wood->fresh()->name)->toBe('Wood');
});

it('announces an explicit analysis and stays silent when a screen just reads it', function () {
    Event::fake([BalanceAnalysed::class]);

    app(AnalyseBalanceProfile::class)->handle($this->profile, announce: false);
    Event::assertNotDispatched(BalanceAnalysed::class);

    app(AnalyseBalanceProfile::class)->handle($this->profile);
    Event::assertDispatchedTimes(BalanceAnalysed::class, 1);
});

it('serves the analysis over the API with its summary', function () {
    $wood = ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();
    ResourceFlow::factory()->forResource($wood)->amounting('3')->create();
    EconomyAction::factory()->forProfile($this->profile)->named('Build')->create();

    $version = $this->version->version_number;

    $this->actingAs($this->designer)
        ->getJson("/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$version}/balance-profiles/{$this->profile->id}/analysis")
        ->assertOk()
        ->assertJsonPath('data.summary.resources', 1)
        ->assertJsonPath('data.summary.actions', 1)
        ->assertJsonPath('data.summary.flows', 1);
});

it('works out what one resource buys of another through an action', function () {
    $wood = ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();
    $gold = ResourceType::factory()->forProfile($this->profile)->named('Gold')->create();
    $trade = EconomyAction::factory()->forProfile($this->profile)->named('Trade')->create();

    ActionCost::factory()->of($trade, $wood, '2')->create();
    ActionReward::factory()->of($trade, $gold, '1')->create();

    $analysis = app(AnalyseBalanceProfile::class)->handle($this->profile, announce: false);

    expect($analysis->conversions)->toHaveCount(1)
        ->and($analysis->conversions[0]->ratio?->label())->toBe('0.5');
});

it('reports no ratio rather than dividing by zero on a free action', function () {
    $wood = ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();
    $gold = ResourceType::factory()->forProfile($this->profile)->named('Gold')->create();
    $gift = EconomyAction::factory()->forProfile($this->profile)->named('Gift')->create();

    ActionCost::factory()->of($gift, $wood, '0')->create();
    ActionReward::factory()->of($gift, $gold, '1')->create();

    $analysis = app(AnalyseBalanceProfile::class)->handle($this->profile, announce: false);

    expect($analysis->conversions)->toBeEmpty();
});

it('never totals an action across resources', function () {
    $wood = ResourceType::factory()->forProfile($this->profile)->named('Wood')->create();
    $stone = ResourceType::factory()->forProfile($this->profile)->named('Stone')->create();
    $build = EconomyAction::factory()->forProfile($this->profile)->named('Build')->create();

    ActionCost::factory()->of($build, $wood, '5')->create();
    ActionCost::factory()->of($build, $stone, '2')->create();

    $analysis = app(AnalyseBalanceProfile::class)->handle($this->profile, announce: false);
    $profitability = $analysis->profitabilityFor($build);

    expect($profitability->deltas)->toHaveCount(2)
        ->and($profitability->hasReward())->toBeFalse();
});
