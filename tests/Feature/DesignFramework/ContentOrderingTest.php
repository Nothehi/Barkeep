<?php

use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\Identity\Domain\Models\User;

beforeEach(function () {
    $this->author = User::factory()->create(['email' => 'author@barkeep.test']);
    config()->set('design-framework.administrators', ['author@barkeep.test']);

    $this->framework = Framework::factory()->withSlug('bgdf')->createdBy($this->author)->published()->create();
    $this->version = FrameworkVersion::factory()->nextFor($this->framework)->create();

    $this->builder = '/app/frameworks/bgdf/versions/1';
});

function phaseNames(FrameworkVersion $version): array
{
    return DesignPhaseDefinition::query()
        ->where('framework_version_id', $version->id)
        ->orderBy('position')
        ->pluck('name')
        ->all();
}

/**
 * The whole point of an explicit position. A framework author who inserts "Concept" between "Ideation" and
 * "Core loop" a week after writing both expects it in the middle, which an id or a timestamp would not give
 * them.
 */
it('moves a phase into the middle of the arc', function () {
    foreach (['Ideation', 'Core loop', 'Concept'] as $name) {
        DesignPhaseDefinition::factory()->inVersion($this->version)->named($name)->create();
    }

    $concept = DesignPhaseDefinition::query()->where('slug', 'concept')->sole();

    $this->actingAs($this->author)
        ->post($this->builder.'/phases/concept/reorder', ['position' => 2])
        ->assertRedirect();

    expect(phaseNames($this->version))->toBe(['Ideation', 'Concept', 'Core loop'])
        ->and($concept->fresh()->position)->toBe(2);
});

it('leaves positions contiguous after a move', function () {
    foreach (range(1, 5) as $index) {
        DesignPhaseDefinition::factory()->inVersion($this->version)->named("Phase {$index}")->create();
    }

    $last = DesignPhaseDefinition::query()->where('slug', 'phase-5')->sole();

    $this->actingAs($this->author)
        ->post($this->builder."/phases/{$last->slug}/reorder", ['position' => 1])
        ->assertRedirect();

    expect(DesignPhaseDefinition::query()->orderBy('position')->pluck('position')->all())->toBe([1, 2, 3, 4, 5]);
});

/**
 * Positions are rewritten rather than nudged, which makes every reorder self-healing: a list that arrived
 * with gaps or duplicates comes out contiguous.
 */
it('repairs a list that had gaps in its positions', function () {
    DesignPhaseDefinition::factory()->inVersion($this->version)->named('Alpha')->atPosition(1)->create();
    DesignPhaseDefinition::factory()->inVersion($this->version)->named('Beta')->atPosition(7)->create();
    DesignPhaseDefinition::factory()->inVersion($this->version)->named('Gamma')->atPosition(40)->create();

    $this->actingAs($this->author)
        ->post($this->builder.'/phases/gamma/reorder', ['position' => 1])
        ->assertRedirect();

    expect(phaseNames($this->version))->toBe(['Gamma', 'Alpha', 'Beta'])
        ->and(DesignPhaseDefinition::query()->orderBy('position')->pluck('position')->all())->toBe([1, 2, 3]);
});

/**
 * Refusing rather than clamping is deliberate: a clamp turns a drag that landed in the wrong place into a
 * reorder nobody asked for, silently.
 */
it('refuses a position past the end of the list', function () {
    DesignPhaseDefinition::factory()->inVersion($this->version)->named('Only')->create();

    $this->actingAs($this->author)
        ->post($this->builder.'/phases/only/reorder', ['position' => 4])
        ->assertSessionHasErrors('position');

    expect(DesignPhaseDefinition::query()->sole()->position)->toBe(1);
});

it('refuses a position below one', function () {
    DesignPhaseDefinition::factory()->inVersion($this->version)->named('Only')->create();

    $this->actingAs($this->author)
        ->post($this->builder.'/phases/only/reorder', ['position' => 0])
        ->assertSessionHasErrors('position');
});

/**
 * Content filed under no phase orders independently of content filed under one, and two criteria in
 * different phases both being "position 1" is correct.
 */
it('orders content per phase rather than per edition', function () {
    $first = DesignPhaseDefinition::factory()->inVersion($this->version)->create();
    $second = DesignPhaseDefinition::factory()->inVersion($this->version)->create();

    $a = DesignCriterion::factory()->inPhase($first)->create();
    $b = DesignCriterion::factory()->inPhase($second)->create();
    $c = DesignCriterion::factory()->inVersion($this->version)->create();

    expect([$a->position, $b->position, $c->position])->toBe([1, 1, 1]);
});

it('reorders criteria within their own phase', function () {
    $phase = DesignPhaseDefinition::factory()->inVersion($this->version)->create();

    $first = DesignCriterion::factory()->inPhase($phase)->titled('First')->create();
    $second = DesignCriterion::factory()->inPhase($phase)->titled('Second')->create();

    $this->actingAs($this->author)
        ->post($this->builder."/criteria/{$second->id}/reorder", ['position' => 1])
        ->assertRedirect();

    expect(DesignCriterion::query()->orderBy('position')->pluck('title')->all())->toBe(['Second', 'First']);
});

it('reorders checklist items within their list', function () {
    $checklist = Checklist::factory()->inVersion($this->version)->create();

    ChecklistItem::factory()->inChecklist($checklist)->titled('Core action')->create();
    ChecklistItem::factory()->inChecklist($checklist)->titled('Reward')->create();
    $third = ChecklistItem::factory()->inChecklist($checklist)->titled('Failure')->create();

    $this->actingAs($this->author)
        ->post($this->builder."/checklists/{$checklist->id}/items/{$third->id}/reorder", ['position' => 1])
        ->assertRedirect();

    expect(ChecklistItem::query()->orderBy('position')->pluck('title')->all())
        ->toBe(['Failure', 'Core action', 'Reward']);
});

/**
 * The reads have to agree with the writes: a flat list ordered phase by phase, with phase-less content
 * first, is what lets a client render a hierarchy without sorting anything.
 */
it('reads a version\'s content with phase-less content first, then phase by phase', function () {
    $first = DesignPhaseDefinition::factory()->inVersion($this->version)->named('Ideation')->create();
    $second = DesignPhaseDefinition::factory()->inVersion($this->version)->named('Concept')->create();

    DesignCriterion::factory()->inPhase($second)->titled('In concept')->create();
    DesignCriterion::factory()->inPhase($first)->titled('In ideation')->create();
    DesignCriterion::factory()->inVersion($this->version)->titled('Everywhere')->create();

    $this->actingAs($this->author)
        ->getJson('/api/v1/frameworks/bgdf/versions/1/criteria')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Everywhere')
        ->assertJsonPath('data.1.title', 'In ideation')
        ->assertJsonPath('data.2.title', 'In concept');
});
