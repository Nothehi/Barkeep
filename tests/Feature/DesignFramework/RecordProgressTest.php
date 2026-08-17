<?php

use Illuminate\Support\Facades\Event;
use Modules\DesignFramework\Domain\Enums\CriterionRating;
use Modules\DesignFramework\Domain\Enums\GameFrameworkStatus;
use Modules\DesignFramework\Domain\Events\CriterionEvaluated;
use Modules\DesignFramework\Domain\Events\PracticeCompleted;
use Modules\DesignFramework\Domain\Events\PromptAnswered;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\ChecklistItemCompletion;
use Modules\DesignFramework\Domain\Models\CriterionEvaluation;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\DesignPractice;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Domain\Models\PracticeCompletion;
use Modules\DesignFramework\Domain\Models\PromptResponse;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * The four things a studio records while working a methodology: a grade, a tick, a tick, an answer.
 *
 * Every one of them is written against the game's *adoption* rather than against the framework
 * content it answers, which is the separation the whole module is built around — the criterion is
 * asked of everybody following the edition, and the grade belongs to exactly one project.
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

    $this->adoption = GameFramework::factory()
        ->forGame($this->game)
        ->following($this->version)
        ->adoptedBy($this->designer)
        ->create();

    $this->criterion = DesignCriterion::factory()->inVersion($this->version)->create();
    $this->practice = DesignPractice::factory()->inVersion($this->version)->create();
    $this->prompt = DesignPrompt::factory()->inVersion($this->version)->create();

    $this->checklist = Checklist::factory()->inVersion($this->version)->create();
    $this->item = ChecklistItem::factory()->inChecklist($this->checklist)->create();

    $this->at = fn (string $path): string => "/app/workspaces/studio/games/bears-and-bridges/framework/{$path}";
});

it('records how a game measures up against a criterion', function () {
    $this->actingAs($this->designer)
        ->post(($this->at)("criteria/{$this->criterion->id}/evaluate"), [
            'status' => 'needs_work',
            'notes' => 'The second player has nothing to do on turn one.',
        ])
        ->assertRedirect();

    $evaluation = CriterionEvaluation::query()->sole();

    expect($evaluation->game_framework_id)->toBe($this->adoption->id)
        ->and($evaluation->criterion_id)->toBe($this->criterion->id)
        ->and($evaluation->status)->toBe(CriterionRating::NeedsWork)
        ->and($evaluation->notes)->toBe('The second player has nothing to do on turn one.')
        ->and($evaluation->evaluated_by)->toBe($this->designer->id);
});

/**
 * A criterion asks how the design is now, so re-assessing replaces the standing answer rather than
 * stacking up beside it. The grade it moved from travels on the event, because movement is the
 * interesting fact — "weak became good" is what a progress narrative is built from.
 */
it('overwrites a standing assessment and reports what it moved from', function () {
    Event::fake([CriterionEvaluated::class]);

    $this->actingAs($this->designer)
        ->post(($this->at)("criteria/{$this->criterion->id}/evaluate"), ['status' => 'weak']);

    $this->actingAs($this->designer)
        ->post(($this->at)("criteria/{$this->criterion->id}/evaluate"), ['status' => 'good']);

    expect(CriterionEvaluation::query()->count())->toBe(1)
        ->and(CriterionEvaluation::query()->sole()->status)->toBe(CriterionRating::Good);

    Event::assertDispatched(
        fn (CriterionEvaluated $event): bool => $event->rating === CriterionRating::Good
            && $event->previousRating === CriterionRating::Weak,
    );
});

/**
 * "Not evaluated" is the state a criterion is in before anybody acts. Accepting it as a grade would
 * make clearing an assessment look like making one.
 */
it('refuses "not evaluated" as a grade', function () {
    $this->actingAs($this->designer)
        ->post(($this->at)("criteria/{$this->criterion->id}/evaluate"), ['status' => 'not_evaluated'])
        ->assertSessionHasErrors('status');

    expect(CriterionEvaluation::query()->count())->toBe(0);
});

it('refuses a grade that is not on the scale', function () {
    $this->actingAs($this->designer)
        ->post(($this->at)("criteria/{$this->criterion->id}/evaluate"), ['status' => 'excellent'])
        ->assertSessionHasErrors('status');
});

it('records that a practice was carried out', function () {
    Event::fake([PracticeCompleted::class]);

    $this->actingAs($this->designer)
        ->post(($this->at)("practices/{$this->practice->id}/complete"), ['notes' => 'Ran it twice.'])
        ->assertRedirect();

    $completion = PracticeCompletion::query()->sole();

    expect($completion->game_framework_id)->toBe($this->adoption->id)
        ->and($completion->practice_id)->toBe($this->practice->id)
        ->and($completion->notes)->toBe('Ran it twice.')
        ->and($completion->completed_by)->toBe($this->designer->id);

    Event::assertDispatched(PracticeCompleted::class);
});

/**
 * The endpoint is safe to retry: ticking something already ticked updates its note rather than
 * failing. The announcement is not repeated, because nothing downstream should have to work out
 * whether a second "completed" means the work happened twice.
 */
it('does not announce a practice completed twice', function () {
    $this->actingAs($this->designer)
        ->post(($this->at)("practices/{$this->practice->id}/complete"));

    Event::fake([PracticeCompleted::class]);

    $this->actingAs($this->designer)
        ->post(($this->at)("practices/{$this->practice->id}/complete"), ['notes' => 'And again.'])
        ->assertRedirect();

    expect(PracticeCompletion::query()->sole()->notes)->toBe('And again.');

    Event::assertNotDispatched(PracticeCompleted::class);
});

/**
 * Unticking deletes the row rather than storing a false flag. The row's existence is the fact, so a
 * completion record that records no completion would be something every count in the module then
 * has to remember to filter out.
 */
it('takes a practice tick back by removing the record', function () {
    $this->actingAs($this->designer)
        ->post(($this->at)("practices/{$this->practice->id}/complete"));

    expect(PracticeCompletion::query()->count())->toBe(1);

    $this->actingAs($this->designer)
        ->post(($this->at)("practices/{$this->practice->id}/complete"), ['completed' => false])
        ->assertRedirect();

    expect(PracticeCompletion::query()->count())->toBe(0);
});

it('shrugs at unticking a practice that was never ticked', function () {
    $this->actingAs($this->designer)
        ->post(($this->at)("practices/{$this->practice->id}/complete"), ['completed' => false])
        ->assertRedirect();

    expect(PracticeCompletion::query()->count())->toBe(0);
});

it('ticks and unticks a checklist item', function () {
    $this->actingAs($this->designer)
        ->post(($this->at)("checklist-items/{$this->item->id}/complete"))
        ->assertRedirect();

    expect(ChecklistItemCompletion::query()->sole()->checklist_item_id)->toBe($this->item->id);

    $this->actingAs($this->designer)
        ->post(($this->at)("checklist-items/{$this->item->id}/complete"), ['completed' => false])
        ->assertRedirect();

    expect(ChecklistItemCompletion::query()->count())->toBe(0);
});

it('writes a game\'s answer to a prompt', function () {
    $this->actingAs($this->designer)
        ->post(($this->at)("prompts/{$this->prompt->id}/respond"), [
            'response' => 'Two friends building something together while the river rises.',
        ])
        ->assertRedirect();

    $response = PromptResponse::query()->sole();

    expect($response->game_framework_id)->toBe($this->adoption->id)
        ->and($response->prompt_id)->toBe($this->prompt->id)
        ->and($response->response)->toBe('Two friends building something together while the river rises.')
        ->and($response->answered_by)->toBe($this->designer->id);
});

/**
 * A prompt asks what the design is now, not what it used to be, so answering again overwrites. The
 * event says it was a rewrite rather than a first answer, which is the part a consumer could not
 * work out for itself.
 */
it('overwrites an answer and says that it was a revision', function () {
    $this->actingAs($this->designer)
        ->post(($this->at)("prompts/{$this->prompt->id}/respond"), ['response' => 'First thoughts.']);

    Event::fake([PromptAnswered::class]);

    $this->actingAs($this->designer)
        ->post(($this->at)("prompts/{$this->prompt->id}/respond"), ['response' => 'Second thoughts.']);

    expect(PromptResponse::query()->count())->toBe(1)
        ->and(PromptResponse::query()->sole()->response)->toBe('Second thoughts.');

    Event::assertDispatched(fn (PromptAnswered $event): bool => $event->wasRevised === true);
});

/**
 * Answers to prompts are a studio's design thinking. An event carrying the text would push it into
 * every log, queue payload and consumer that ever subscribes.
 */
it('keeps the written answer off the event', function () {
    Event::fake([PromptAnswered::class]);

    $this->actingAs($this->designer)
        ->post(($this->at)("prompts/{$this->prompt->id}/respond"), ['response' => 'A private idea.']);

    Event::assertDispatched(
        fn (PromptAnswered $event): bool => ! str_contains(json_encode(get_object_vars($event)), 'A private idea'),
    );
});

/**
 * Content is resolved through the adoption, so an id from an edition this game does not follow is
 * never found. A 404 rather than a 422: the id came from a URL, and refusing it in place would
 * confirm that it names content somewhere.
 */
it('cannot reach content from an edition the game does not follow', function () {
    $other = FrameworkVersion::factory()->nextFor($this->framework)->published()->create();
    $theirCriterion = DesignCriterion::factory()->inVersion($other)->create();

    $this->actingAs($this->designer)
        ->post(($this->at)("criteria/{$theirCriterion->id}/evaluate"), ['status' => 'good'])
        ->assertNotFound();

    expect(CriterionEvaluation::query()->count())->toBe(0);
});

it('does not choke on a content id that is not a uuid', function () {
    $this->actingAs($this->designer)
        ->post(($this->at)('criteria/the-good-one/evaluate'), ['status' => 'good'])
        ->assertNotFound();
});

/**
 * Pausing is what makes stepping away honest, and it would not be worth much if work kept landing
 * in the gap. All four writes are refused by one ability, because the rule they share is the whole
 * rule.
 */
it('refuses every kind of work while the adoption is paused', function () {
    $this->adoption->status = GameFrameworkStatus::Paused;
    $this->adoption->save();

    $this->actingAs($this->designer)
        ->post(($this->at)("criteria/{$this->criterion->id}/evaluate"), ['status' => 'good'])
        ->assertForbidden();

    $this->actingAs($this->designer)
        ->post(($this->at)("practices/{$this->practice->id}/complete"))
        ->assertForbidden();

    $this->actingAs($this->designer)
        ->post(($this->at)("checklist-items/{$this->item->id}/complete"))
        ->assertForbidden();

    $this->actingAs($this->designer)
        ->post(($this->at)("prompts/{$this->prompt->id}/respond"), ['response' => 'Anything.'])
        ->assertForbidden();

    expect(CriterionEvaluation::query()->count())->toBe(0)
        ->and(PracticeCompletion::query()->count())->toBe(0)
        ->and(ChecklistItemCompletion::query()->count())->toBe(0)
        ->and(PromptResponse::query()->count())->toBe(0);
});

it('refuses work once the studio has declared itself finished', function () {
    $this->adoption->status = GameFrameworkStatus::Completed;
    $this->adoption->save();

    $this->actingAs($this->designer)
        ->post(($this->at)("criteria/{$this->criterion->id}/evaluate"), ['status' => 'good'])
        ->assertForbidden();
});

/**
 * An archived game stays readable — the assessment done on it a year ago is the point of keeping it
 * — and refuses anything new.
 */
it('refuses work on an archived game', function () {
    $this->game->status = GameStatus::Archived;
    $this->game->save();

    $this->actingAs($this->designer)
        ->post(($this->at)("practices/{$this->practice->id}/complete"))
        ->assertForbidden();

    expect(PracticeCompletion::query()->count())->toBe(0);
});

it('hides the whole working chain from an outsider', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->post(($this->at)("criteria/{$this->criterion->id}/evaluate"), ['status' => 'good'])
        ->assertNotFound();
});
