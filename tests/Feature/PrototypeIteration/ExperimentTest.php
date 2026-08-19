<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\ExperimentStatus;
use Modules\PrototypeIteration\Domain\Events\ExperimentCancelled;
use Modules\PrototypeIteration\Domain\Events\ExperimentCompleted;
use Modules\PrototypeIteration\Domain\Events\ExperimentCreated;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\Workspace\Domain\Models\Workspace;

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();
    $this->game = Game::factory()->inWorkspace($this->workspace)->withSlug('bears')->active()->create();
    $this->iteration = Iteration::factory()->forGame($this->game)->inProgress()->create();

    $this->base = "/api/v1/workspaces/studio/games/bears/iterations/{$this->iteration->id}/experiments";
});

function designExperiment(array $payload = [])
{
    return test()->actingAs(test()->designer)->postJson(test()->base, array_merge([
        'title' => 'Combat pacing test',
        'question' => 'Does removing reactions reduce downtime?',
    ], $payload));
}

it('writes down the question, the prediction, the method and the expectation', function () {
    designExperiment([
        'hypothesis' => 'Simultaneous choices will cut downtime by about a fifth.',
        'method' => 'Run three four-player sessions with the reaction phase removed.',
        'expected_result' => 'Players will spend less time waiting.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.question', 'Does removing reactions reduce downtime?')
        ->assertJsonPath('data.hypothesis', 'Simultaneous choices will cut downtime by about a fifth.')
        ->assertJsonPath('data.method', 'Run three four-player sessions with the reaction phase removed.')
        ->assertJsonPath('data.expected_result', 'Players will spend less time waiting.')
        ->assertJsonPath('data.status', ExperimentStatus::Planned->value)
        ->assertJsonPath('data.actual_result', null)
        ->assertJsonPath('data.conclusion', null);
});

it('requires a question, because an experiment without one is a session', function () {
    designExperiment(['question' => null])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('question');
});

it('accepts an exploratory experiment with no prediction', function () {
    designExperiment(['hypothesis' => null, 'expected_result' => null])
        ->assertCreated()
        ->assertJsonPath('data.hypothesis', null)
        ->assertJsonPath('data.expected_result', null);
});

/**
 * The arrangement the whole experiment record depends on: a result cannot arrive through the same
 * request as the prediction, so a prediction is always at risk when it is written.
 */
it('ignores a result the create request tries to smuggle in', function () {
    designExperiment([
        'actual_result' => 'Downtime fell by a fifth.',
        'conclusion' => 'Removing reactions works.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.actual_result', null)
        ->assertJsonPath('data.conclusion', null);
});

it('announces that a question was framed before anything was run', function () {
    Event::fake([ExperimentCreated::class]);

    designExperiment()->assertCreated();

    Event::assertDispatched(fn (ExperimentCreated $event): bool => $event->iterationId === $this->iteration->id
        && $event->gameId === $this->game->id);
});

it('starts a planned experiment and records when', function () {
    $experiment = DesignExperiment::factory()->forIteration($this->iteration)->create();

    $this->actingAs($this->designer)->postJson($this->base.'/'.$experiment->id.'/start')
        ->assertOk()
        ->assertJsonPath('data.status', ExperimentStatus::Running->value);

    expect($experiment->refresh()->started_at)->not->toBeNull();
});

it('records what a running experiment actually produced', function () {
    $experiment = DesignExperiment::factory()->forIteration($this->iteration)->running()->create();

    $this->actingAs($this->designer)->postJson($this->base.'/'.$experiment->id.'/complete', [
        'actual_result' => 'Players explored more strategies but sessions ran twenty minutes longer.',
        'conclusion' => 'Unlimited actions improve strategy but harm pacing.',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', ExperimentStatus::Completed->value)
        ->assertJsonPath('data.actual_result', 'Players explored more strategies but sessions ran twenty minutes longer.')
        ->assertJsonPath('data.conclusion', 'Unlimited actions improve strategy but harm pacing.');

    expect($experiment->refresh()->completed_at)->not->toBeNull();
});

it('refuses to complete an experiment with nothing observed', function () {
    $experiment = DesignExperiment::factory()->forIteration($this->iteration)->running()->create();

    $this->actingAs($this->designer)->postJson($this->base.'/'.$experiment->id.'/complete', [
        'actual_result' => null,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('actual_result');

    expect($experiment->refresh()->status)->toBe(ExperimentStatus::Running);
});

/**
 * What happened is a fact the person at the table already has; what it means often arrives days later.
 * Requiring both at once would produce conclusions written to fill a field.
 */
it('accepts a result with no conclusion yet', function () {
    $experiment = DesignExperiment::factory()->forIteration($this->iteration)->running()->create();

    $this->actingAs($this->designer)->postJson($this->base.'/'.$experiment->id.'/complete', [
        'actual_result' => 'Sessions ran twenty minutes longer.',
    ])
        ->assertOk()
        ->assertJsonPath('data.actual_result', 'Sessions ran twenty minutes longer.')
        ->assertJsonPath('data.conclusion', null);
});

it('announces whether a conclusion was drawn', function () {
    Event::fake([ExperimentCompleted::class]);

    $experiment = DesignExperiment::factory()->forIteration($this->iteration)->running()->create();

    $this->actingAs($this->designer)->postJson($this->base.'/'.$experiment->id.'/complete', [
        'actual_result' => 'Sessions ran twenty minutes longer.',
    ])->assertOk();

    Event::assertDispatched(fn (ExperimentCompleted $event): bool => $event->hasConclusion === false);
});

it('refuses to complete an experiment that was never run', function () {
    $experiment = DesignExperiment::factory()->forIteration($this->iteration)->create();

    $this->actingAs($this->designer)->postJson($this->base.'/'.$experiment->id.'/complete', [
        'actual_result' => 'Something we never watched happen.',
    ])->assertConflict();

    expect($experiment->refresh()->status)->toBe(ExperimentStatus::Planned);
});

it('cancels an abandoned question from either open state', function (string $state) {
    $experiment = $state === 'running'
        ? DesignExperiment::factory()->forIteration($this->iteration)->running()->create()
        : DesignExperiment::factory()->forIteration($this->iteration)->create();

    $this->actingAs($this->designer)->postJson($this->base.'/'.$experiment->id.'/cancel')
        ->assertOk()
        ->assertJsonPath('data.status', ExperimentStatus::Cancelled->value)
        ->assertJsonPath('data.actual_result', null);
})->with(['planned', 'running']);

it('announces cancellation as an ending distinct from completion', function () {
    Event::fake([ExperimentCancelled::class, ExperimentCompleted::class]);

    $experiment = DesignExperiment::factory()->forIteration($this->iteration)->running()->create();

    $this->actingAs($this->designer)->postJson($this->base.'/'.$experiment->id.'/cancel')->assertOk();

    Event::assertDispatched(ExperimentCancelled::class);
    Event::assertNotDispatched(ExperimentCompleted::class);
});

/**
 * The module's subtlest invariant. Editing a prediction after the result is known is how it becomes
 * retroactively correct — almost always honestly, by somebody tidying up sloppy wording.
 */
it('refuses to rewrite a completed experiment\'s prediction', function () {
    $experiment = DesignExperiment::factory()->forIteration($this->iteration)->completed()->create([
        'hypothesis' => 'Downtime will fall.',
    ]);

    $this->actingAs($this->designer)->patchJson($this->base.'/'.$experiment->id, [
        'title' => $experiment->title,
        'question' => $experiment->question,
        'hypothesis' => 'Downtime will rise, actually, which is what we said all along.',
    ])->assertConflict();

    expect($experiment->refresh()->hypothesis)->toBe('Downtime will fall.');
});

it('lets a running experiment\'s method be corrected', function () {
    $experiment = DesignExperiment::factory()->forIteration($this->iteration)->running()->create();

    $this->actingAs($this->designer)->patchJson($this->base.'/'.$experiment->id, [
        'title' => $experiment->title,
        'question' => $experiment->question,
        'method' => 'We ended up running four players, not three.',
    ])
        ->assertOk()
        ->assertJsonPath('data.method', 'We ended up running four players, not three.');
});

it('refuses every experiment move once the cycle around it has closed', function (string $action) {
    $closed = Iteration::factory()->forGame($this->game)->completed()->create();
    $experiment = DesignExperiment::factory()->forIteration($closed)->running()->create();

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears/iterations/{$closed->id}/experiments/{$experiment->id}/{$action}", [
            'actual_result' => 'Something recorded after the cycle closed.',
        ])
        ->assertForbidden();
})->with(['start', 'complete', 'cancel']);

it('publishes no route for deleting an experiment', function () {
    $experiment = DesignExperiment::factory()->forIteration($this->iteration)->completed()->create();

    $this->actingAs($this->designer)->deleteJson($this->base.'/'.$experiment->id)
        ->assertMethodNotAllowed();

    expect(DesignExperiment::query()->count())->toBe(1);
});

it('offers only the moves the experiment can actually make', function () {
    $experiment = DesignExperiment::factory()->forIteration($this->iteration)->running()->create();

    $this->actingAs($this->designer)->getJson($this->base)
        ->assertOk()
        ->assertJsonPath('data.0.available_transitions.0.status', ExperimentStatus::Completed->value)
        ->assertJsonPath('data.0.available_transitions.1.status', ExperimentStatus::Cancelled->value)
        ->assertJsonCount(2, 'data.0.available_transitions');
});
