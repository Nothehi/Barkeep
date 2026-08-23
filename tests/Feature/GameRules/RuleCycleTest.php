<?php

use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Domain\Enums\ReferenceType;
use Modules\GameRules\Domain\Enums\ValidationCode;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\RuleReference;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\ValueObjects\ValidationError;
use Modules\GameRules\Infrastructure\Analysis\RuleSetValidator;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * Section 54 of the module brief: four things in this module can loop, and none
 * of them may. The checks come in pairs — the command refuses the edge that would
 * close the loop, and the validator reports one that predates the check, because
 * data can arrive from a restore or from a clone of a set written before the
 * refusal existed.
 */

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();

    $this->game = Game::factory()
        ->inWorkspace($this->workspace)
        ->withSlug('bears-and-bridges')
        ->active()
        ->create();

    $this->version = GameVersion::factory()->nextFor($this->game)->create();
    $this->ruleSet = RuleSet::factory()->forVersion($this->version)->create();
});

function cycleUrl(string $path): string
{
    $ruleSet = test()->ruleSet;
    $version = test()->version->version_number;

    return "/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$version}/rule-sets/{$ruleSet->id}".$path;
}

/**
 * @return list<string>
 */
function findingCodes(RuleSet $ruleSet): array
{
    return array_map(
        fn (ValidationError $finding): string => $finding->code->value,
        app(RuleSetValidator::class)->validate($ruleSet),
    );
}

it('refuses to make a rule its own parent', function () {
    $rule = GameRule::factory()->forRuleSet($this->ruleSet)->create();

    $this->actingAs($this->designer)
        ->patchJson(cycleUrl("/rules/{$rule->id}"), ['parent_rule_id' => $rule->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('parent_rule_id');
});

it('refuses a reparent that would close a loop three levels up', function () {
    $a = GameRule::factory()->forRuleSet($this->ruleSet)->named('A')->create();
    $b = GameRule::factory()->forRuleSet($this->ruleSet)->under($a)->named('B')->create();
    $c = GameRule::factory()->forRuleSet($this->ruleSet)->under($b)->named('C')->create();

    $this->actingAs($this->designer)
        ->patchJson(cycleUrl("/rules/{$a->id}"), ['parent_rule_id' => $c->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('parent_rule_id');

    expect($a->fresh()->parent_rule_id)->toBeNull();
});

it('allows a reparent that does not close a loop', function () {
    $a = GameRule::factory()->forRuleSet($this->ruleSet)->named('A')->create();
    $b = GameRule::factory()->forRuleSet($this->ruleSet)->named('B')->create();

    $this->actingAs($this->designer)
        ->patchJson(cycleUrl("/rules/{$b->id}"), ['parent_rule_id' => $a->id])
        ->assertOk();

    expect($b->fresh()->parent_rule_id)->toBe($a->id);
});

it('refuses to nest a phase inside itself', function () {
    $phase = GamePhase::factory()->forRuleSet($this->ruleSet)->create();

    $this->actingAs($this->designer)
        ->patchJson(cycleUrl("/phases/{$phase->id}"), ['parent_phase_id' => $phase->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('parent_phase_id');
});

it('refuses a phase hierarchy that loops', function () {
    $round = GamePhase::factory()->forRuleSet($this->ruleSet)->named('Round')->create();
    $turn = GamePhase::factory()->forRuleSet($this->ruleSet)->under($round)->named('Turn')->create();

    $this->actingAs($this->designer)
        ->patchJson(cycleUrl("/phases/{$round->id}"), ['parent_phase_id' => $turn->id])
        ->assertStatus(422);
});

it('refuses a rule that references itself', function () {
    $rule = GameRule::factory()->forRuleSet($this->ruleSet)->create();

    $this->actingAs($this->designer)
        ->postJson(cycleUrl("/rules/{$rule->id}/references"), ['referenced_rule_id' => $rule->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('referenced_rule_id');
});

it('refuses a directed reference that would close a loop', function () {
    $a = GameRule::factory()->forRuleSet($this->ruleSet)->named('A')->create();
    $b = GameRule::factory()->forRuleSet($this->ruleSet)->named('B')->create();

    $this->actingAs($this->designer)
        ->postJson(cycleUrl("/rules/{$a->id}/references"), [
            'referenced_rule_id' => $b->id,
            'reference_type' => ReferenceType::DependsOn->value,
        ])
        ->assertCreated();

    $this->actingAs($this->designer)
        ->postJson(cycleUrl("/rules/{$b->id}/references"), [
            'referenced_rule_id' => $a->id,
            'reference_type' => ReferenceType::DependsOn->value,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('referenced_rule_id');
});

it('permits two rules to be mutually related, which carries no order', function () {
    $a = GameRule::factory()->forRuleSet($this->ruleSet)->named('A')->create();
    $b = GameRule::factory()->forRuleSet($this->ruleSet)->named('B')->create();

    $this->actingAs($this->designer)
        ->postJson(cycleUrl("/rules/{$a->id}/references"), [
            'referenced_rule_id' => $b->id,
            'reference_type' => ReferenceType::RelatedTo->value,
        ])
        ->assertCreated();

    $this->actingAs($this->designer)
        ->postJson(cycleUrl("/rules/{$b->id}/references"), [
            'referenced_rule_id' => $a->id,
            'reference_type' => ReferenceType::RelatedTo->value,
        ])
        ->assertCreated();
});

it('reports a rule hierarchy loop that predates the refusal', function () {
    $a = GameRule::factory()->forRuleSet($this->ruleSet)->named('A')->create();
    $b = GameRule::factory()->forRuleSet($this->ruleSet)->under($a)->named('B')->create();

    /*
     * Written straight to the column, as a restore or an older clone would.
     * Nothing in the module can produce this shape through a command.
     */
    $a->forceFill(['parent_rule_id' => $b->id])->save();

    expect(findingCodes($this->ruleSet))
        ->toContain(ValidationCode::RuleHierarchyIsCircular->value);
});

it('reports a reference loop that predates the refusal', function () {
    $a = GameRule::factory()->forRuleSet($this->ruleSet)->named('A')->create();
    $b = GameRule::factory()->forRuleSet($this->ruleSet)->named('B')->create();

    RuleReference::factory()
        ->from($a, $b)
        ->ofType(ReferenceType::Overrides)
        ->create();

    RuleReference::factory()
        ->from($b, $a)
        ->ofType(ReferenceType::Overrides)
        ->create();

    expect(findingCodes($this->ruleSet))
        ->toContain(ValidationCode::RuleReferenceIsCircular->value);
});
