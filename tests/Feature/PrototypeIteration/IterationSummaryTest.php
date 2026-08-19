<?php

use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\PrototypeIteration\Domain\Models\DecisionEvidence;
use Modules\PrototypeIteration\Domain\Models\DesignChange;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\IterationPlaytest;
use Modules\Workspace\Domain\Models\Workspace;

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();
    $this->game = Game::factory()->inWorkspace($this->workspace)->withSlug('bears')->active()->create();
    $this->iteration = Iteration::factory()->forGame($this->game)->inProgress()->create();

    $this->url = "/api/v1/workspaces/studio/games/bears/iterations/{$this->iteration->id}/summary";
});

it('reports zeroes for a cycle nobody has done anything in', function () {
    $this->actingAs($this->designer)->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.changes', 0)
        ->assertJsonPath('data.experiments', 0)
        ->assertJsonPath('data.decisions', 0)
        ->assertJsonPath('data.playtests', 0)
        ->assertJsonPath('data.observations', 0)
        ->assertJsonPath('data.has_work', false)
        ->assertJsonPath('data.has_evidence', false)
        ->assertJsonPath('data.experiments_settled', true);
});

it('counts everything the cycle produced from its own tables', function () {
    DesignChange::factory()->forIteration($this->iteration)->count(3)->create();
    DesignExperiment::factory()->forIteration($this->iteration)->count(2)->create();
    DesignExperiment::factory()->forIteration($this->iteration)->completed()->create();
    $decision = DesignDecision::factory()->forIteration($this->iteration)->accepted()->create();
    DesignDecision::factory()->forIteration($this->iteration)->count(2)->create();
    DecisionEvidence::factory()->forDecision($decision)->count(4)->create();

    $this->actingAs($this->designer)->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.changes', 3)
        ->assertJsonPath('data.experiments', 3)
        ->assertJsonPath('data.completed_experiments', 1)
        ->assertJsonPath('data.decisions', 3)
        ->assertJsonPath('data.accepted_decisions', 1)
        ->assertJsonPath('data.evidence', 4)
        ->assertJsonPath('data.has_work', true);
});

/**
 * The gap that matters. A cycle that closed with two of its three experiments still running tested less
 * than its headline count suggests, and this module refuses to auto-complete them — so the gap is real.
 */
it('shows an unfinished experiment as an unsettled one', function () {
    DesignExperiment::factory()->forIteration($this->iteration)->completed()->create();
    DesignExperiment::factory()->forIteration($this->iteration)->running()->create();

    $this->actingAs($this->designer)->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.experiments', 2)
        ->assertJsonPath('data.completed_experiments', 1)
        ->assertJsonPath('data.experiments_settled', false);
});

/**
 * The playtesting figures come from Playtesting through the adapter rather than from a copy, which is
 * what keeps them equal to what the playtest's own screen shows.
 */
it('reads the related evidence counts from Playtesting', function () {
    $playtest = Playtest::factory()->forGame($this->game)->create();
    $session = PlaytestSession::factory()->forPlaytest($playtest)->completed()->create();
    PlaytestObservation::factory()->forSession($session)->count(8)->create();
    PlaytestFeedback::factory()->forSession($session)->count(6)->create();

    IterationPlaytest::factory()->forIteration($this->iteration)->forPlaytest($playtest->id)->create();

    $this->actingAs($this->designer)->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.playtests', 1)
        ->assertJsonPath('data.sessions', 1)
        ->assertJsonPath('data.observations', 8)
        ->assertJsonPath('data.feedback', 6)
        ->assertJsonPath('data.has_evidence', true);
});

it('sums the evidence across every attached playtest', function () {
    foreach ([2, 3] as $observations) {
        $playtest = Playtest::factory()->forGame($this->game)->create();
        $session = PlaytestSession::factory()->forPlaytest($playtest)->completed()->create();
        PlaytestObservation::factory()->forSession($session)->count($observations)->create();

        IterationPlaytest::factory()->forIteration($this->iteration)->forPlaytest($playtest->id)->create();
    }

    $this->actingAs($this->designer)->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.playtests', 2)
        ->assertJsonPath('data.observations', 5);
});

it('persists none of the figures', function () {
    DesignChange::factory()->forIteration($this->iteration)->count(2)->create();

    $this->actingAs($this->designer)->getJson($this->url)->assertOk();

    expect(array_keys($this->iteration->refresh()->getAttributes()))
        ->not->toContain('changes_count')
        ->not->toContain('summary_cache');
});

it('echoes the cycle\'s own outcome and summary alongside the figures', function () {
    $completed = Iteration::factory()->forGame($this->game)->completed()->create([
        'summary' => 'Combat became more interesting, but downtime remains too high.',
    ]);

    $this->actingAs($this->designer)
        ->getJson("/api/v1/workspaces/studio/games/bears/iterations/{$completed->id}/summary")
        ->assertOk()
        ->assertJsonPath('data.outcome', 'partial')
        ->assertJsonPath('data.summary', 'Combat became more interesting, but downtime remains too high.');
});

it('hides the summary of another studio\'s cycle', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->getJson($this->url)->assertNotFound();
});
