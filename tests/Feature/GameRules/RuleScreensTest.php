<?php

use Inertia\Testing\AssertableInertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\PhaseTransition;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\GameRules\Domain\Models\RuleSet;
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
    $this->ruleSet = RuleSet::factory()->forVersion($this->version)->named('First draft')->create();
});

function screenUrl(string $path = ''): string
{
    $version = test()->version->version_number;

    return "/app/workspaces/studio/games/bears-and-bridges/versions/{$version}/rules".$path;
}

function ruleSetScreenUrl(string $path = ''): string
{
    return screenUrl('/'.test()->ruleSet->id.$path);
}

it('lists the rule sets written for a design version', function () {
    $this->actingAs($this->designer)
        ->get(screenUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('rules/index')
            ->has('ruleSets.data', 1)
            ->where('ruleSets.data.0.name', 'First draft')
            ->where('canCreate', true));
});

it('draws the dashboard from one analysis', function () {
    $phase = GamePhase::factory()->forRuleSet($this->ruleSet)->named('Action phase')->create();
    GameRule::factory()->forRuleSet($this->ruleSet)->named('Combat')->create();
    RuleAction::factory()->forRuleSet($this->ruleSet)->during($phase)->named('Build')->create();

    $this->actingAs($this->designer)
        ->get(ruleSetScreenUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('rules/show')
            ->has('analysis.data.summary')
            ->where('analysis.data.summary.rules', 1)
            ->where('analysis.data.summary.phases', 1)
            ->where('analysis.data.summary.actions', 1)
            ->has('analysis.data.rules', 1)
            ->has('analysis.data.warnings')
            ->has('analysis.data.graph.nodes')
            ->has('options.rule_types')
            ->where('economy.available', false));
});

it('tells the client what the account may do', function () {
    $this->actingAs($this->designer)
        ->get(ruleSetScreenUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ruleSet.data.permissions.canEdit', true)
            ->where('ruleSet.data.permissions.canClone', true));
});

it('offers cloning rather than editing once the rules are in play', function () {
    $setup = GamePhase::factory()->forRuleSet($this->ruleSet)->setup()->atPosition(0)->create();
    $end = GamePhase::factory()->forRuleSet($this->ruleSet)->terminal()->atPosition(1)->create();
    PhaseTransition::factory()->between($setup, $end)->create();
    RuleAction::factory()->forRuleSet($this->ruleSet)->during($setup)->create();

    $this->actingAs($this->designer)->post(ruleSetScreenUrl('/activate'))->assertRedirect();

    $this->actingAs($this->designer)
        ->get(ruleSetScreenUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ruleSet.data.status', 'active')
            ->where('ruleSet.data.is_editable', false)
            ->where('ruleSet.data.permissions.canEdit', false)
            ->where('ruleSet.data.permissions.canClone', true)
            ->where('ruleSet.data.permissions.canRename', true));
});

it('renders the builder, the phase designer, the flow and the analysis', function () {
    foreach ([
        ['/builder', 'rules/builder'],
        ['/phases', 'rules/phases'],
        ['/graph', 'rules/graph'],
        ['/analysis', 'rules/analysis'],
    ] as [$path, $component]) {
        $this->actingAs($this->designer)
            ->get(ruleSetScreenUrl($path))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component($component));
    }
});

it('shows one rule with what points at it', function () {
    $combat = GameRule::factory()->forRuleSet($this->ruleSet)->named('Combat')->create();
    $siege = GameRule::factory()->forRuleSet($this->ruleSet)->named('Siege')->create();

    $this->actingAs($this->designer)
        ->post(ruleSetScreenUrl("/rules/{$siege->id}/references"), [
            'referenced_rule_id' => $combat->id,
            'reference_type' => 'exception_to',
        ])
        ->assertRedirect();

    $this->actingAs($this->designer)
        ->get(ruleSetScreenUrl("/rules/{$combat->id}"))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('rules/rule')
            ->where('rule.data.name', 'Combat')
            ->has('referencedBy.data', 1)
            ->where('referencedBy.data.0.rule_name', 'Siege'));
});

it('shows one action with the economy it points at', function () {
    $phase = GamePhase::factory()->forRuleSet($this->ruleSet)->create();

    $action = RuleAction::factory()
        ->forRuleSet($this->ruleSet)
        ->during($phase)
        ->named('Build')
        ->pricedAs('build')
        ->create();

    $this->actingAs($this->designer)
        ->get(ruleSetScreenUrl("/actions/{$action->id}"))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('rules/action')
            ->where('action.data.name', 'Build')
            ->where('action.data.economy_action_slug', 'build')
            /*
             * No balance profile on this version, so the handle resolves to
             * nothing. That is the ordinary case rather than an error, and the
             * screen shows the handle and moves on.
             */
            ->where('economyReference.data.is_resolved', false)
            ->where('economy.available', false));
});

it('sends the whole vocabulary so no picker keeps its own copy', function () {
    $this->actingAs($this->designer)
        ->get(ruleSetScreenUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('options.rule_types.0.description')
            ->has('options.phase_types.0.is_terminal')
            ->has('options.operators.0.expects_value')
            ->has('options.effect_types.0.expects_value')
            ->has('options.reference_types.0.is_directed'));
});

it('hides the rules of a game the account cannot see', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->get(screenUrl())->assertNotFound();
    $this->actingAs($outsider)->get(ruleSetScreenUrl())->assertNotFound();
});

it('sends somebody who is not signed in to the login screen', function () {
    $this->get(screenUrl())->assertRedirect('/login');
});

it('links to the rules from a design version', function () {
    $this->actingAs($this->designer)
        ->get("/app/workspaces/studio/games/bears-and-bridges/versions/{$this->version->version_number}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('games/version'));
});
