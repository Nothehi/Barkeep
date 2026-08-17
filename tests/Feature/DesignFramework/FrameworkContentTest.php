<?php

use Illuminate\Support\Facades\Event;
use Modules\DesignFramework\Domain\Enums\FrameworkContentStatus;
use Modules\DesignFramework\Domain\Events\ChecklistCreated;
use Modules\DesignFramework\Domain\Events\CriterionCreated;
use Modules\DesignFramework\Domain\Events\PhaseCreated;
use Modules\DesignFramework\Domain\Events\PracticeCreated;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\DesignPractice;
use Modules\DesignFramework\Domain\Models\DesignPrinciple;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
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

it('adds a phase to a draft edition, appended at the end', function () {
    DesignPhaseDefinition::factory()->inVersion($this->version)->named('Ideation')->create();

    $this->actingAs($this->author)
        ->post($this->builder.'/phases', ['name' => 'Concept'])
        ->assertRedirect();

    $phase = DesignPhaseDefinition::query()->where('slug', 'concept')->sole();

    expect($phase->position)->toBe(2)
        ->and($phase->framework_version_id)->toBe($this->version->id)
        ->and($phase->status)->toBe(FrameworkContentStatus::Draft);
});

it('derives a phase address from its name', function () {
    $this->actingAs($this->author)
        ->post($this->builder.'/phases', ['name' => 'Core Loop'])
        ->assertRedirect();

    expect(DesignPhaseDefinition::query()->sole()->slug)->toBe('core-loop');
});

/**
 * Addresses are stable handles. A bookmarked phase URL should survive a reword, and a seeder rebuilding
 * v1 twice has to produce the same identifiers.
 */
it('keeps a phase address when the phase is renamed', function () {
    $phase = DesignPhaseDefinition::factory()->inVersion($this->version)->named('Core loop')->create();

    $this->actingAs($this->author)
        ->patch($this->builder.'/phases/core-loop', ['name' => 'The core loop'])
        ->assertRedirect();

    expect($phase->fresh()->slug)->toBe('core-loop')
        ->and($phase->fresh()->name)->toBe('The core loop');
});

it('suffixes a colliding derived address rather than refusing the title', function () {
    DesignCriterion::factory()->inVersion($this->version)->titled('Is the core loop clear?')->create();

    $this->actingAs($this->author)
        ->post($this->builder.'/criteria', ['title' => 'Is the core loop clear?'])
        ->assertRedirect();

    expect(DesignCriterion::query()->pluck('slug')->all())
        ->toBe(['is-the-core-loop-clear', 'is-the-core-loop-clear-2']);
});

it('announces the content types anything downstream could act on', function () {
    Event::fake([PhaseCreated::class, CriterionCreated::class, PracticeCreated::class, ChecklistCreated::class]);

    $this->actingAs($this->author)->post($this->builder.'/phases', ['name' => 'Core loop']);
    $this->actingAs($this->author)->post($this->builder.'/criteria', ['title' => 'Is it meaningful?']);
    $this->actingAs($this->author)->post($this->builder.'/practices', ['title' => 'Write the loop down']);
    $this->actingAs($this->author)->post($this->builder.'/checklists', ['title' => 'Core loop readiness']);

    Event::assertDispatched(PhaseCreated::class);
    Event::assertDispatched(CriterionCreated::class);
    Event::assertDispatched(PracticeCreated::class);
    Event::assertDispatched(ChecklistCreated::class);
});

/**
 * Principles and prompts announce nothing, because nothing outside this module can act on a design rule or
 * a question having been written. An event that exists only for symmetry is an event somebody will
 * subscribe to by mistake.
 */
it('announces nothing for principles and prompts', function () {
    $announced = [];

    Event::listen('*', function (string $event) use (&$announced) {
        if (str_starts_with($event, 'Modules\\DesignFramework\\Domain\\Events\\')) {
            $announced[] = $event;
        }
    });

    $this->actingAs($this->author)->post($this->builder.'/principles', ['title' => 'Decisions matter']);
    $this->actingAs($this->author)->post($this->builder.'/prompts', [
        'title' => 'Core experience',
        'prompt' => 'What should a player feel?',
    ]);

    expect($announced)->toBe([]);
});

it('files content under a phase of the same edition', function () {
    $phase = DesignPhaseDefinition::factory()->inVersion($this->version)->create();

    $this->actingAs($this->author)
        ->post($this->builder.'/criteria', ['title' => 'Is it meaningful?', 'phase_id' => $phase->id])
        ->assertRedirect();

    expect(DesignCriterion::query()->sole()->phase_id)->toBe($phase->id);
});

/**
 * A phase arrives in a request body rather than through a route binding, which makes it the one identifier
 * in the builder the URL does not already scope. It is resolved through the version, so a phase from
 * another edition is not found rather than found and rejected.
 */
it('refuses a phase belonging to another edition', function () {
    $other = FrameworkVersion::factory()->nextFor($this->framework)->create();
    $theirPhase = DesignPhaseDefinition::factory()->inVersion($other)->create();

    $this->actingAs($this->author)
        ->post($this->builder.'/criteria', ['title' => 'Is it meaningful?', 'phase_id' => $theirPhase->id])
        ->assertSessionHasErrors('phase_id');

    expect(DesignCriterion::query()->count())->toBe(0);
});

it('accepts content filed under no phase at all', function () {
    $this->actingAs($this->author)
        ->post($this->builder.'/principles', ['title' => 'Decisions should matter'])
        ->assertRedirect();

    expect(DesignPrinciple::query()->sole()->phase_id)->toBeNull();
});

it('stores the body field each content type actually has', function () {
    $this->actingAs($this->author)->post($this->builder.'/practices', [
        'title' => 'Run a two-player test',
        'description' => 'The cheapest useful session.',
        'instructions' => 'Two players, one table, one question written down first.',
    ])->assertRedirect();

    $this->actingAs($this->author)->post($this->builder.'/prompts', [
        'title' => 'Core experience',
        'prompt' => 'What should a player feel when this game works?',
    ])->assertRedirect();

    expect(DesignPractice::query()->sole()->instructions)->toBe('Two players, one table, one question written down first.')
        ->and(DesignPrompt::query()->sole()->prompt)->toBe('What should a player feel when this game works?');
});

it('requires a prompt to ask something', function () {
    $this->actingAs($this->author)
        ->post($this->builder.'/prompts', ['title' => 'Core experience'])
        ->assertSessionHasErrors('prompt');
});

it('moves content between phases, appending it at the end of the new one', function () {
    $first = DesignPhaseDefinition::factory()->inVersion($this->version)->create();
    $second = DesignPhaseDefinition::factory()->inVersion($this->version)->create();

    DesignCriterion::factory()->inPhase($second)->create();
    $moving = DesignCriterion::factory()->inPhase($first)->create();

    $this->actingAs($this->author)
        ->patch($this->builder."/criteria/{$moving->id}", ['phase_id' => $second->id])
        ->assertRedirect();

    expect($moving->fresh()->phase_id)->toBe($second->id)
        ->and($moving->fresh()->position)->toBe(2);
});

it('adds items to a checklist, appended in order', function () {
    $checklist = Checklist::factory()->inVersion($this->version)->create();

    foreach (['Core action identified', 'Reward identified'] as $title) {
        $this->actingAs($this->author)
            ->post($this->builder."/checklists/{$checklist->id}/items", ['title' => $title])
            ->assertRedirect();
    }

    expect(ChecklistItem::query()->orderBy('position')->pluck('title')->all())
        ->toBe(['Core action identified', 'Reward identified'])
        ->and(ChecklistItem::query()->orderBy('position')->pluck('position')->all())->toBe([1, 2]);
});

it('makes checklist items required by default', function () {
    $checklist = Checklist::factory()->inVersion($this->version)->create();

    $this->actingAs($this->author)
        ->post($this->builder."/checklists/{$checklist->id}/items", ['title' => 'Win condition implemented'])
        ->assertRedirect();

    expect(ChecklistItem::query()->sole()->required)->toBeTrue();
});

it('accepts an optional checklist item when asked', function () {
    $checklist = Checklist::factory()->inVersion($this->version)->create();

    $this->actingAs($this->author)
        ->post($this->builder."/checklists/{$checklist->id}/items", [
            'title' => 'Nice box insert',
            'required' => false,
        ])
        ->assertRedirect();

    expect(ChecklistItem::query()->sole()->required)->toBeFalse();
});

/**
 * `required` is only written when the caller sent it. A partial request must not silently promote an
 * optional item, and it must not demote a required one — either would move every following game's progress.
 */
it('leaves an item\'s required flag alone when the caller did not send it', function () {
    $checklist = Checklist::factory()->inVersion($this->version)->create();
    $item = ChecklistItem::factory()->inChecklist($checklist)->optional()->create();

    $this->actingAs($this->author)
        ->patch($this->builder."/checklists/{$checklist->id}/items/{$item->id}", ['title' => 'Reworded'])
        ->assertRedirect();

    expect($item->fresh()->required)->toBeFalse();
});

it('refuses a checklist item belonging to another checklist', function () {
    $checklist = Checklist::factory()->inVersion($this->version)->create();
    $other = Checklist::factory()->inVersion($this->version)->create();
    $item = ChecklistItem::factory()->inChecklist($other)->create();

    $this->actingAs($this->author)
        ->patch($this->builder."/checklists/{$checklist->id}/items/{$item->id}", ['title' => 'Reworded'])
        ->assertNotFound();
});

it('refuses content belonging to another edition', function () {
    $other = FrameworkVersion::factory()->nextFor($this->framework)->create();
    $theirs = DesignCriterion::factory()->inVersion($other)->create();

    $this->actingAs($this->author)
        ->patch($this->builder."/criteria/{$theirs->id}", ['title' => 'Reworded'])
        ->assertNotFound();
});

it('treats a content id that is not a uuid as no such content', function () {
    $this->actingAs($this->author)
        ->patch($this->builder.'/criteria/not-a-uuid', ['title' => 'Reworded'])
        ->assertNotFound();
});
