<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Domain\Enums\GamePhaseType;
use Modules\GameRules\Domain\Enums\RuleSetStatus;
use Modules\GameRules\Domain\Events\RuleSetActivated;
use Modules\GameRules\Domain\Events\RuleSetArchived;
use Modules\GameRules\Domain\Events\RuleSetCreated;
use Modules\GameRules\Domain\Models\GamePhase;
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
});

function ruleSetsUrl(?GameVersion $version = null): string
{
    $version ??= test()->version;

    return "/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$version->version_number}/rule-sets";
}

function ruleSetUrl(RuleSet $ruleSet): string
{
    return ruleSetsUrl($ruleSet->version).'/'.$ruleSet->id;
}

function createRuleSet(array $payload = [])
{
    return test()->actingAs(test()->designer)->postJson(
        ruleSetsUrl(),
        array_merge(['name' => 'First draft'], $payload),
    );
}

/**
 * A rule set that the validator has nothing fatal to say about, so activation
 * tests are exercising the lifecycle rather than the error gate.
 */
function coherentRuleSet(?GameVersion $version = null): RuleSet
{
    $ruleSet = RuleSet::factory()->forVersion($version ?? test()->version)->create();

    $setup = GamePhase::factory()->forRuleSet($ruleSet)->setup()->named('Setup')->atPosition(0)->create();
    $end = GamePhase::factory()->forRuleSet($ruleSet)->terminal()->named('Game end')->atPosition(1)->create();

    PhaseTransition::factory()->between($setup, $end)->create();

    RuleAction::factory()->forRuleSet($ruleSet)->during($setup)->create();

    return $ruleSet;
}

it('starts a rule set for a design version', function () {
    Event::fake([RuleSetCreated::class]);

    $response = createRuleSet(['description' => 'The rules as of the convention build.']);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'First draft')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.game_version_id', $this->version->id);

    Event::assertDispatched(RuleSetCreated::class);
});

it('refuses a second rule set with the same name on one version', function () {
    createRuleSet()->assertCreated();

    createRuleSet()->assertStatus(422)->assertJsonValidationErrors('name');
});

it('allows the same rule set name on two design versions', function () {
    createRuleSet()->assertCreated();

    $second = GameVersion::factory()->nextFor($this->game)->create();

    $this->actingAs($this->designer)
        ->postJson(ruleSetsUrl($second), ['name' => 'First draft'])
        ->assertCreated();
});

it('puts a rule set into play and retires whichever was in play before', function () {
    Event::fake([RuleSetActivated::class, RuleSetArchived::class]);

    $first = coherentRuleSet();
    $second = coherentRuleSet();

    $this->actingAs($this->designer)->postJson(ruleSetUrl($first).'/activate')->assertOk();
    $this->actingAs($this->designer)->postJson(ruleSetUrl($second).'/activate')->assertOk();

    expect($first->fresh()->status)->toBe(RuleSetStatus::Archived)
        ->and($second->fresh()->status)->toBe(RuleSetStatus::Active);

    Event::assertDispatched(RuleSetActivated::class, fn (RuleSetActivated $event): bool => $event->ruleSetId === $second->id
        && $event->supersededRuleSetId === $first->id);
});

it('refuses to put a rule set with structural errors into play', function () {
    $ruleSet = RuleSet::factory()->forVersion($this->version)->create();

    /*
     * An action with no phase is an error: nobody can place it in the turn, so
     * "these are the rules now" would be a claim the rule set cannot support.
     */
    RuleAction::factory()->forRuleSet($ruleSet)->create();

    $this->actingAs($this->designer)
        ->postJson(ruleSetUrl($ruleSet).'/activate')
        ->assertStatus(409);

    expect($ruleSet->fresh()->status)->toBe(RuleSetStatus::Draft);
});

it('does not let warnings block activation', function () {
    $ruleSet = coherentRuleSet();

    /*
     * This rule set has no victory condition, no mechanics and no rules — every
     * one of them a warning. None of them is a reason to refuse.
     */
    $this->actingAs($this->designer)
        ->postJson(ruleSetUrl($ruleSet).'/activate')
        ->assertOk();
});

it('refuses to edit the rules of a rule set that is in play', function () {
    $ruleSet = coherentRuleSet();

    $this->actingAs($this->designer)->postJson(ruleSetUrl($ruleSet).'/activate')->assertOk();

    $this->actingAs($this->designer)
        ->postJson(ruleSetUrl($ruleSet).'/rules', ['name' => 'Line of sight'])
        ->assertForbidden();
});

it('still allows the title of a rule set in play to be corrected', function () {
    $ruleSet = coherentRuleSet();

    $this->actingAs($this->designer)->postJson(ruleSetUrl($ruleSet).'/activate')->assertOk();

    $this->actingAs($this->designer)
        ->patchJson(ruleSetUrl($ruleSet), ['name' => 'Convention rules'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Convention rules');
});

it('archives a rule set and refuses everything afterwards', function () {
    Event::fake([RuleSetArchived::class]);

    $ruleSet = RuleSet::factory()->forVersion($this->version)->create();

    $this->actingAs($this->designer)->postJson(ruleSetUrl($ruleSet).'/archive')->assertOk();

    expect($ruleSet->fresh()->status)->toBe(RuleSetStatus::Archived);

    $this->actingAs($this->designer)
        ->patchJson(ruleSetUrl($ruleSet), ['name' => 'Renamed'])
        ->assertForbidden();

    $this->actingAs($this->designer)
        ->postJson(ruleSetUrl($ruleSet).'/activate')
        ->assertForbidden();

    Event::assertDispatched(RuleSetArchived::class);
});

it('keeps an archived rule set readable', function () {
    $ruleSet = RuleSet::factory()->forVersion($this->version)->archived()->create();

    $this->actingAs($this->designer)
        ->getJson(ruleSetUrl($ruleSet))
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');
});

it('reports what the signed in account may do with a rule set', function () {
    $ruleSet = RuleSet::factory()->forVersion($this->version)->create();

    $this->actingAs($this->designer)
        ->getJson(ruleSetUrl($ruleSet))
        ->assertOk()
        ->assertJsonPath('data.permissions.canEdit', true)
        ->assertJsonPath('data.permissions.canClone', true);

    $active = coherentRuleSet();
    $this->actingAs($this->designer)->postJson(ruleSetUrl($active).'/activate');

    $this->actingAs($this->designer)
        ->getJson(ruleSetUrl($active))
        ->assertOk()
        ->assertJsonPath('data.permissions.canEdit', false)
        ->assertJsonPath('data.permissions.canClone', true);
});

it('never lets a design version hold two rule sets in play', function () {
    $first = coherentRuleSet();
    $second = coherentRuleSet();

    $this->actingAs($this->designer)->postJson(ruleSetUrl($first).'/activate')->assertOk();
    $this->actingAs($this->designer)->postJson(ruleSetUrl($second).'/activate')->assertOk();

    expect(RuleSet::query()
        ->where('game_version_id', $this->version->id)
        ->where('status', RuleSetStatus::Active)
        ->count())->toBe(1);
});

it('places a phase of a chosen type', function () {
    $ruleSet = RuleSet::factory()->forVersion($this->version)->create();

    $this->actingAs($this->designer)
        ->postJson(ruleSetUrl($ruleSet).'/phases', [
            'name' => 'Action phase',
            'phase_type' => GamePhaseType::Action->value,
        ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'action_phase')
        ->assertJsonPath('data.phase_type', 'action')
        ->assertJsonPath('data.is_terminal', false);
});
