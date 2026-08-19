<?php

use Carbon\CarbonInterface;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\PrototypeIteration\Domain\Enums\DesignChangeCategory;
use Modules\PrototypeIteration\Domain\Enums\ExperimentStatus;
use Modules\PrototypeIteration\Domain\Enums\TimelineEntryKind;
use Modules\PrototypeIteration\Domain\Models\DesignChange;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\IterationPlaytest;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * The timeline is the module's primary interaction: one axis with everything on it, so a reader can see
 * that the decision came four days after the playtest and two hours before the cycle closed.
 */
/*
 * Time is frozen and every fixture is dated relative to the moment the cycle started.
 *
 * Ordering is the whole subject of these tests, so a fixture dated "now" against a start dated at a wall-clock
 * hour would pass or fail depending on what time of day the suite ran — which is a test that lies about the
 * code roughly half the time.
 */
beforeEach(function () {
    $this->freezeTime();

    $this->startedAt = now()->subHours(6);

    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();
    $this->game = Game::factory()->inWorkspace($this->workspace)->withSlug('bears')->active()->create();
    $this->iteration = Iteration::factory()->forGame($this->game)->inProgress()->create([
        'started_at' => $this->startedAt,
    ]);

    $this->url = "/api/v1/workspaces/studio/games/bears/iterations/{$this->iteration->id}/timeline";
});

/**
 * A moment a given number of minutes after the cycle started.
 */
function afterStart(int $minutes): CarbonInterface
{
    return test()->startedAt->copy()->addMinutes($minutes);
}

it('is empty for a cycle that has not started', function () {
    $planned = Iteration::factory()->forGame($this->game)->create();

    $this->actingAs($this->designer)
        ->getJson("/api/v1/workspaces/studio/games/bears/iterations/{$planned->id}/timeline")
        ->assertOk()
        ->assertJsonPath('data.is_empty', true)
        ->assertJsonCount(0, 'data.entries');
});

it('puts the start of the work on the line, not around it', function () {
    $this->actingAs($this->designer)->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.is_empty', false)
        ->assertJsonPath('data.entries.0.kind', TimelineEntryKind::Started->value)
        ->assertJsonPath('data.entries.0.is_lifecycle', true)
        ->assertJsonPath('data.entries.0.title', $this->iteration->objective);
});

it('interleaves changes, experiments, playtests and decisions in the order they happened', function () {
    $playtest = Playtest::factory()->forGame($this->game)->titled('Four-player combat')->create();

    DesignChange::factory()->forIteration($this->iteration)->titled('Removed reaction phase')->create([
        'created_at' => afterStart(20),
    ]);
    DesignExperiment::factory()->forIteration($this->iteration)->running()->create([
        'title' => 'Four-player combat test',
        'started_at' => afterStart(70),
    ]);
    IterationPlaytest::factory()->forIteration($this->iteration)->forPlaytest($playtest->id)->create([
        'created_at' => afterStart(210),
    ]);
    DesignDecision::factory()->forIteration($this->iteration)->accepted()->create([
        'decision' => 'Remove the reaction phase permanently.',
        'decided_at' => afterStart(300),
    ]);

    $response = $this->actingAs($this->designer)->getJson($this->url)->assertOk();

    $kinds = array_column($response->json('data.entries'), 'kind');

    expect($kinds)->toBe([
        TimelineEntryKind::Started->value,
        TimelineEntryKind::Change->value,
        TimelineEntryKind::Experiment->value,
        TimelineEntryKind::Playtest->value,
        TimelineEntryKind::Decision->value,
    ]);
});

it('words every entry on the server, from the enums that define the vocabulary', function () {
    DesignChange::factory()
        ->forIteration($this->iteration)
        ->inCategory(DesignChangeCategory::Pacing)
        ->titled('Removed reaction phase')
        ->create();

    $this->actingAs($this->designer)->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.entries.1.kind_label', 'Design change')
        ->assertJsonPath('data.entries.1.badge', 'Pacing');
});

it('shows a change\'s reason under its title, because that is what makes it worth reading', function () {
    DesignChange::factory()->forIteration($this->iteration)->titled('Removed reaction phase')->create([
        'reason' => 'Reaction windows created excessive downtime.',
        'created_at' => afterStart(20),
    ]);

    $this->actingAs($this->designer)->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.entries.1.title', 'Removed reaction phase')
        ->assertJsonPath('data.entries.1.body', 'Reaction windows created excessive downtime.');
});

it('shows an experiment\'s question until it has a result, and then the result', function () {
    $running = Iteration::factory()->forGame($this->game)->inProgress()->create([
        'started_at' => now()->subDay(),
    ]);
    $url = "/api/v1/workspaces/studio/games/bears/iterations/{$running->id}/timeline";

    $experiment = DesignExperiment::factory()->forIteration($running)->running()->create([
        'question' => 'Does removing reactions reduce downtime?',
        'started_at' => now()->subDay()->addHour(),
    ]);

    $this->actingAs($this->designer)->getJson($url)
        ->assertOk()
        ->assertJsonPath('data.entries.1.body', 'Does removing reactions reduce downtime?');

    $experiment->forceFill([
        'status' => ExperimentStatus::Completed,
        'actual_result' => 'Downtime fell by about eighteen per cent.',
        'completed_at' => now(),
    ])->save();

    $this->actingAs($this->designer)->getJson($url)
        ->assertOk()
        ->assertJsonPath('data.entries.1.body', 'Downtime fell by about eighteen per cent.');
});

/**
 * Counts rather than a sentence, because this application pluralises on the client against the shared
 * catalogue — no PHP in the platform builds ":count observations" itself.
 */
it('hands a playtest entry its numbers rather than a formatted string', function () {
    $playtest = Playtest::factory()->forGame($this->game)->create();
    $session = PlaytestSession::factory()->forPlaytest($playtest)->completed()->create();
    PlaytestObservation::factory()->forSession($session)->count(4)->create();

    IterationPlaytest::factory()
        ->forIteration($this->iteration)
        ->forPlaytest($playtest->id)
        ->create(['created_at' => afterStart(30)]);

    $this->actingAs($this->designer)->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.entries.1.kind', TimelineEntryKind::Playtest->value)
        ->assertJsonPath('data.entries.1.counts.observations', 4)
        ->assertJsonPath('data.entries.1.reference', $playtest->id);
});

it('reports no counts for a playtest that has produced nothing', function () {
    $playtest = Playtest::factory()->forGame($this->game)->create();
    IterationPlaytest::factory()
        ->forIteration($this->iteration)
        ->forPlaytest($playtest->id)
        ->create(['created_at' => afterStart(30)]);

    $this->actingAs($this->designer)->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.entries.1.counts', null);
});

it('ends a completed cycle with its outcome and summary', function () {
    $completed = Iteration::factory()->forGame($this->game)->completed()->create([
        'summary' => 'Downtime reduced by approximately eighteen per cent.',
    ]);

    $response = $this->actingAs($this->designer)
        ->getJson("/api/v1/workspaces/studio/games/bears/iterations/{$completed->id}/timeline")
        ->assertOk();

    $entries = $response->json('data.entries');
    $last = end($entries);

    expect($last['kind'])->toBe(TimelineEntryKind::Completed->value)
        ->and($last['is_lifecycle'])->toBeTrue()
        ->and($last['badge'])->toBe('Partial')
        ->and($last['body'])->toBe('Downtime reduced by approximately eighteen per cent.');
});

it('marks a cancelled cycle\'s ending without an outcome', function () {
    $cancelled = Iteration::factory()->forGame($this->game)->cancelled()->create([
        'started_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($this->designer)
        ->getJson("/api/v1/workspaces/studio/games/bears/iterations/{$cancelled->id}/timeline")
        ->assertOk();

    $entries = $response->json('data.entries');
    $last = end($entries);

    expect($last['kind'])->toBe(TimelineEntryKind::Cancelled->value)
        ->and($last['badge'])->toBeNull();
});

/**
 * An entry typed up after the fact belongs in the account, but putting it first would place the epilogue
 * before the game.
 */
it('sorts an undated entry to the end rather than to the front', function () {
    DesignChange::factory()->forIteration($this->iteration)->titled('Dated change')->create([
        'created_at' => afterStart(30),
    ]);

    $undated = DesignDecision::factory()->forIteration($this->iteration)->titled('Undated')->create();
    $undated->forceFill(['created_at' => null, 'decided_at' => null])->saveQuietly();

    $response = $this->actingAs($this->designer)->getJson($this->url)->assertOk();

    $kinds = array_column($response->json('data.entries'), 'kind');

    expect(end($kinds))->toBe(TimelineEntryKind::Decision->value);
});

it('tallies the entries by kind', function () {
    DesignChange::factory()
        ->forIteration($this->iteration)
        ->count(2)
        ->create(['created_at' => afterStart(30)]);

    $this->actingAs($this->designer)->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.tally.change', 2)
        ->assertJsonPath('data.tally.started', 1)
        ->assertJsonPath('data.tally.decision', 0);
});

it('hides the timeline of another studio\'s cycle', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->getJson($this->url)->assertNotFound();
});
