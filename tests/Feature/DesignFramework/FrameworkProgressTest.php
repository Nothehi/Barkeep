<?php

use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\DesignPractice;
use Modules\DesignFramework\Domain\Models\DesignPrinciple;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * What framework progress counts, and — mostly — what it refuses to count.
 *
 * Every figure is counted on read from the rows it came from, so these are tests of a decision
 * rather than of arithmetic. The decisions are the interesting part: a bar that moved when somebody
 * ticked "I have read this principle" would be measuring reading, and one that paid more for
 * "strong" than for "weak" would make a designer who graded honestly look worse than one who did
 * not.
 */
beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();

    $this->game = Game::factory()
        ->inWorkspace($this->workspace)
        ->withSlug('bears-and-bridges')
        ->active()
        ->create();

    $this->framework = Framework::factory()->withSlug('bgdf')->published()->create();
    $this->version = FrameworkVersion::factory()->nextFor($this->framework)->published()->create();
    $this->phase = DesignPhaseDefinition::factory()->inVersion($this->version)->named('Core loop')->create();

    $this->adoption = GameFramework::factory()
        ->forGame($this->game)
        ->following($this->version)
        ->adoptedBy($this->designer)
        ->create();

    $this->progress = fn (): array => $this
        ->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games/bears-and-bridges/framework/progress')
        ->assertOk()
        ->json('data');

    $this->evaluate = fn (DesignCriterion $criterion, string $status = 'good') => $this
        ->actingAs($this->designer)
        ->post(
            "/app/workspaces/studio/games/bears-and-bridges/framework/criteria/{$criterion->id}/evaluate",
            ['status' => $status],
        )
        ->assertRedirect();

    $this->complete = fn (DesignPractice $practice) => $this
        ->actingAs($this->designer)
        ->post("/app/workspaces/studio/games/bears-and-bridges/framework/practices/{$practice->id}/complete")
        ->assertRedirect();

    $this->tick = fn (ChecklistItem $item) => $this
        ->actingAs($this->designer)
        ->post("/app/workspaces/studio/games/bears-and-bridges/framework/checklist-items/{$item->id}/complete")
        ->assertRedirect();

    $this->answer = fn (DesignPrompt $prompt) => $this
        ->actingAs($this->designer)
        ->post(
            "/app/workspaces/studio/games/bears-and-bridges/framework/prompts/{$prompt->id}/respond",
            ['response' => 'Something true about this game.'],
        )
        ->assertRedirect();
});

it('starts at nothing done', function () {
    DesignCriterion::factory()->count(2)->inPhase($this->phase)->create();

    $progress = ($this->progress)();

    expect($progress['percentage'])->toBe(0)
        ->and($progress['criteria']['completed'])->toBe(0)
        ->and($progress['criteria']['total'])->toBe(2)
        ->and($progress['is_complete'])->toBeFalse();
});

/**
 * Counts rather than averaged percentages, so a phase with one criterion and twenty checklist items
 * weights them by how much work each actually represents.
 */
it('counts criteria, practices and required checklist items together', function () {
    $criterion = DesignCriterion::factory()->inPhase($this->phase)->create();
    $practice = DesignPractice::factory()->inPhase($this->phase)->create();

    $checklist = Checklist::factory()->inPhase($this->phase)->create();
    $item = ChecklistItem::factory()->inChecklist($checklist)->create();

    expect(($this->progress)()['overall'])->toMatchArray(['completed' => 0, 'total' => 3]);

    ($this->evaluate)($criterion);
    ($this->complete)($practice);
    ($this->tick)($item);

    $progress = ($this->progress)();

    expect($progress['overall'])->toMatchArray(['completed' => 3, 'total' => 3])
        ->and($progress['percentage'])->toBe(100)
        ->and($progress['is_complete'])->toBeTrue();
});

/**
 * A criterion counts once it has been assessed, whatever the assessment was. Turning "strong" into
 * more points than "weak" would reward a flattering self-assessment, which is precisely backwards.
 */
it('counts an honest low grade exactly as much as a high one', function () {
    $weak = DesignCriterion::factory()->inPhase($this->phase)->create();
    $strong = DesignCriterion::factory()->inPhase($this->phase)->create();

    ($this->evaluate)($weak, 'weak');
    ($this->evaluate)($strong, 'strong');

    expect(($this->progress)()['criteria'])->toMatchArray(['completed' => 2, 'total' => 2]);
});

/**
 * There is nothing to do with a principle but hold it in mind, so it appears in no count at all —
 * not as outstanding work, and not as work done.
 */
it('does not count principles at all', function () {
    DesignPrinciple::factory()->count(3)->inPhase($this->phase)->create();

    expect(($this->progress)()['overall'])->toMatchArray(['completed' => 0, 'total' => 0]);
});

/**
 * Prompts are counted and reported and deliberately left out of the total. A prompt has no right
 * answer, so letting it move a percentage rewards typing over thinking — but a phase page genuinely
 * wants to say "3 of 5 answered".
 */
it('reports answered prompts beside the total rather than inside it', function () {
    $prompt = DesignPrompt::factory()->inPhase($this->phase)->create();
    DesignPrompt::factory()->inPhase($this->phase)->create();

    ($this->answer)($prompt);

    $progress = ($this->progress)();

    expect($progress['prompts'])->toMatchArray(['completed' => 1, 'total' => 2])
        ->and($progress['overall'])->toMatchArray(['completed' => 0, 'total' => 0])
        ->and($progress['percentage'])->toBe(0);
});

/**
 * Optional items are shown and tickable and not counted, which is what lets an author add a
 * nice-to-have without everybody's numbers moving.
 */
it('counts required checklist items and not optional ones', function () {
    $checklist = Checklist::factory()->inPhase($this->phase)->create();

    ChecklistItem::factory()->inChecklist($checklist)->create();
    $optional = ChecklistItem::factory()->inChecklist($checklist)->optional()->create();

    expect(($this->progress)()['checklist_items'])->toMatchArray(['completed' => 0, 'total' => 1]);

    ($this->tick)($optional);

    expect(($this->progress)()['checklist_items'])->toMatchArray(['completed' => 0, 'total' => 1]);
});

/**
 * Only published content counts. Draft content is not part of what the game adopted, and archived
 * content has been dropped from the methodology — counting either would make a game's numbers move
 * because somebody else was writing.
 */
it('counts only published content', function () {
    DesignCriterion::factory()->inPhase($this->phase)->create();
    DesignCriterion::factory()->inPhase($this->phase)->draft()->create();
    DesignCriterion::factory()->inPhase($this->phase)->archived()->create();

    expect(($this->progress)()['criteria'])->toMatchArray(['completed' => 0, 'total' => 1]);
});

/**
 * The two filters have to agree, or a phase's numbers would not add up to the edition's.
 */
it('ignores content filed under a phase that does not count', function () {
    $draftPhase = DesignPhaseDefinition::factory()->inVersion($this->version)->named('Unfinished')->draft()->create();

    DesignCriterion::factory()->inPhase($this->phase)->create();
    DesignCriterion::factory()->inPhase($draftPhase)->create();

    $progress = ($this->progress)();

    expect($progress['criteria'])->toMatchArray(['total' => 1])
        ->and($progress['phase_progress'])->toHaveCount(1);
});

/**
 * Content filed under no phase applies across the whole methodology. It belongs in the edition's
 * totals even though it appears on no phase page.
 */
it('counts content that is filed under no phase', function () {
    DesignCriterion::factory()->inPhase($this->phase)->create();
    DesignCriterion::factory()->inVersion($this->version)->create();

    $progress = ($this->progress)();

    expect($progress['criteria'])->toMatchArray(['total' => 2])
        ->and($progress['phase_progress'][0]['criteria'])->toMatchArray(['total' => 1]);
});

/**
 * Nothing to do is not the same as everything done. A phase that is entirely principles says so,
 * rather than claiming a hundred per cent.
 */
it('marks a phase with nothing countable in it as empty', function () {
    DesignPrinciple::factory()->inPhase($this->phase)->create();

    $phase = ($this->progress)()['phase_progress'][0];

    expect($phase['is_empty'])->toBeTrue()
        ->and($phase['percentage'])->toBe(0);
});

it('breaks progress down by phase', function () {
    $second = DesignPhaseDefinition::factory()->inVersion($this->version)->named('Prototype')->create();

    $here = DesignCriterion::factory()->inPhase($this->phase)->create();
    DesignCriterion::factory()->inPhase($second)->create();

    ($this->evaluate)($here);

    $phases = ($this->progress)()['phase_progress'];

    expect($phases)->toHaveCount(2)
        ->and($phases[0])->toMatchArray(['slug' => 'core-loop', 'percentage' => 100])
        ->and($phases[1])->toMatchArray(['slug' => 'prototype', 'percentage' => 0]);
});

/**
 * Nothing is stored, so taking work back moves the figure straight back down. A stored percentage
 * is a fourth fact that can disagree with the three it came from.
 */
it('follows the record back down when work is taken back', function () {
    $practice = DesignPractice::factory()->inPhase($this->phase)->create();

    ($this->complete)($practice);

    expect(($this->progress)()['percentage'])->toBe(100);

    $this->actingAs($this->designer)
        ->post("/app/workspaces/studio/games/bears-and-bridges/framework/practices/{$practice->id}/complete", [
            'completed' => false,
        ])
        ->assertRedirect();

    expect(($this->progress)()['percentage'])->toBe(0);
});

/**
 * Completing the framework is a declaration, not an arithmetic result, and the two are reported
 * separately: a paused or completed adoption keeps counting what it actually did.
 */
it('keeps counting after the studio declares itself finished', function () {
    $criterion = DesignCriterion::factory()->inPhase($this->phase)->create();
    DesignCriterion::factory()->inPhase($this->phase)->create();

    ($this->evaluate)($criterion);

    $this->actingAs($this->designer)
        ->post('/app/workspaces/studio/games/bears-and-bridges/framework/complete')
        ->assertRedirect();

    expect(($this->progress)()['criteria'])->toMatchArray(['completed' => 1, 'total' => 2]);
});
