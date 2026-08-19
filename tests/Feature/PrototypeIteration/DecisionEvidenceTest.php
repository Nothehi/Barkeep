<?php

use Illuminate\Support\Str;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\PrototypeIteration\Domain\Enums\EvidenceType;
use Modules\PrototypeIteration\Domain\Models\DecisionEvidence;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * A decision may cite evidence, and the citation is deliberately weak — a type and a bare id, with no
 * foreign key. These tests are what makes that safe: every reference is resolved through the
 * decision's own game on the way in, and read live from the owning context on the way out.
 */
beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();
    $this->game = Game::factory()->inWorkspace($this->workspace)->withSlug('bears')->active()->create();
    $this->iteration = Iteration::factory()->forGame($this->game)->inProgress()->create();
    $this->decision = DesignDecision::factory()->forIteration($this->iteration)->create();

    $this->playtest = Playtest::factory()->forGame($this->game)->titled('Four-player combat')->create();
    $this->session = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();

    $this->base = "/api/v1/workspaces/studio/games/bears/iterations/{$this->iteration->id}"
        ."/decisions/{$this->decision->id}/evidence";
});

it('cites a note, which is the evidence rather than a pointer to it', function () {
    $this->actingAs($this->designer)->postJson($this->base, [
        'type' => 'note',
        'description' => 'Marco\'s group told us the same thing at the fair.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.0.type', EvidenceType::Note->value)
        ->assertJsonPath('data.0.reference_id', null)
        ->assertJsonPath('data.0.description', 'Marco\'s group told us the same thing at the fair.')
        ->assertJsonPath('data.0.is_resolved', true);
});

it('refuses a note that also carries a reference', function () {
    $this->actingAs($this->designer)->postJson($this->base, [
        'type' => 'note',
        'reference_id' => $this->playtest->id,
        'description' => 'A note pretending to be a pointer.',
    ])->assertUnprocessable();

    expect(DecisionEvidence::query()->count())->toBe(0);
});

it('cites a playtest of the same game and reads its title back', function () {
    $this->actingAs($this->designer)->postJson($this->base, [
        'type' => 'playtest',
        'reference_id' => $this->playtest->id,
        'description' => 'Downtime was measured across all three tables.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.0.type', EvidenceType::Playtest->value)
        ->assertJsonPath('data.0.reference_id', $this->playtest->id)
        ->assertJsonPath('data.0.excerpt', 'Four-player combat')
        ->assertJsonPath('data.0.playtest_id', $this->playtest->id)
        ->assertJsonPath('data.0.is_linkable', true);
});

/**
 * Section 45: the cited words are shown, and they are read from Playtesting rather than copied here.
 */
it('cites an observation and shows what it actually said', function () {
    $observation = PlaytestObservation::factory()->forSession($this->session)->create([
        'content' => 'Players spent less time waiting.',
    ]);

    $this->actingAs($this->designer)->postJson($this->base, [
        'type' => 'observation',
        'reference_id' => $observation->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.0.excerpt', 'Players spent less time waiting.')
        ->assertJsonPath('data.0.playtest_id', $this->playtest->id);
});

it('cites a piece of feedback and shows what was said', function () {
    $feedback = PlaytestFeedback::factory()->forSession($this->session)->create([
        'content' => 'Combat feels faster.',
    ]);

    $this->actingAs($this->designer)->postJson($this->base, [
        'type' => 'feedback',
        'reference_id' => $feedback->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.0.excerpt', 'Combat feels faster.');
});

it('cites an experiment of the same game and shows its question', function () {
    $experiment = DesignExperiment::factory()->forIteration($this->iteration)->completed()->create([
        'question' => 'Does removing reactions reduce downtime?',
    ]);

    $this->actingAs($this->designer)->postJson($this->base, [
        'type' => 'experiment',
        'reference_id' => $experiment->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.0.excerpt', 'Does removing reactions reduce downtime?');
});

/**
 * Reading the excerpt live rather than copying it is what keeps a decision from quoting words the
 * observation no longer contains.
 */
it('shows a corrected observation\'s new wording in every decision that cited it', function () {
    $observation = PlaytestObservation::factory()->forSession($this->session)->create([
        'content' => 'Players seemed bored.',
    ]);

    DecisionEvidence::factory()
        ->forDecision($this->decision)
        ->citing(EvidenceType::Observation, $observation->id)
        ->create();

    $observation->forceFill(['content' => 'Players disengaged during the reaction window.'])->save();

    $this->actingAs($this->designer)->getJson($this->base)
        ->assertOk()
        ->assertJsonPath('data.0.excerpt', 'Players disengaged during the reaction window.');
});

it('stores the reason it was cited, never a copy of the cited words', function () {
    $observation = PlaytestObservation::factory()->forSession($this->session)->create([
        'content' => 'Players spent less time waiting.',
    ]);

    $this->actingAs($this->designer)->postJson($this->base, [
        'type' => 'observation',
        'reference_id' => $observation->id,
        'description' => 'This is the clearest measurement we have of the effect.',
    ])->assertCreated();

    $stored = DecisionEvidence::query()->sole();

    expect($stored->description)->toBe('This is the clearest measurement we have of the effect.')
        ->and($stored->getAttributes())->not->toHaveKey('content');
});

/**
 * The security property. Without game scoping, a bare uuid in this body would let one studio's decision
 * cite another's observation, and it would render as genuine supporting evidence.
 */
it('refuses to cite a playtest from another game', function () {
    $otherGame = Game::factory()->inWorkspace($this->workspace)->withSlug('other')->active()->create();
    $theirPlaytest = Playtest::factory()->forGame($otherGame)->create();

    $this->actingAs($this->designer)->postJson($this->base, [
        'type' => 'playtest',
        'reference_id' => $theirPlaytest->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('reference_id');

    expect(DecisionEvidence::query()->count())->toBe(0);
});

it('refuses to cite an observation from another studio\'s playtest', function () {
    $outsider = User::factory()->create();
    $elsewhere = Workspace::factory()->ownedBy($outsider)->withSlug('rivals')->create();
    $theirGame = Game::factory()->inWorkspace($elsewhere)->active()->create();
    $theirPlaytest = Playtest::factory()->forGame($theirGame)->create();
    $theirSession = PlaytestSession::factory()->forPlaytest($theirPlaytest)->completed()->create();
    $theirObservation = PlaytestObservation::factory()->forSession($theirSession)->create();

    $this->actingAs($this->designer)->postJson($this->base, [
        'type' => 'observation',
        'reference_id' => $theirObservation->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('reference_id');
});

it('refuses to cite an experiment from another game', function () {
    $otherGame = Game::factory()->inWorkspace($this->workspace)->withSlug('other')->active()->create();
    $theirIteration = Iteration::factory()->forGame($otherGame)->inProgress()->create();
    $theirExperiment = DesignExperiment::factory()->forIteration($theirIteration)->create();

    $this->actingAs($this->designer)->postJson($this->base, [
        'type' => 'experiment',
        'reference_id' => $theirExperiment->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('reference_id');
});

it('requires a reference for every type that points at something', function (string $type) {
    $this->actingAs($this->designer)->postJson($this->base, ['type' => $type])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('reference_id');
})->with(['playtest', 'observation', 'feedback', 'experiment']);

it('refuses a reference id that names nothing', function () {
    $this->actingAs($this->designer)->postJson($this->base, [
        'type' => 'playtest',
        'reference_id' => 'not-a-uuid',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('reference_id');
});

it('reports one error for an unknown type rather than two', function () {
    $this->actingAs($this->designer)->postJson($this->base, ['type' => 'vibes'])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('type')
        ->assertJsonMissingValidationErrors('reference_id');
});

/**
 * The reference is loose by design, so a citation can dangle. It renders as a citation that has gone
 * missing rather than being dropped, because a shorter list would read as "nothing supported this".
 */
it('shows an unresolvable citation as missing rather than hiding it', function () {
    DecisionEvidence::factory()
        ->forDecision($this->decision)
        ->citing(EvidenceType::Observation, (string) Str::uuid())
        ->create(['description' => 'Cited before the session notes were reorganised.']);

    $this->actingAs($this->designer)->getJson($this->base)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.is_resolved', false)
        ->assertJsonPath('data.0.excerpt', null)
        ->assertJsonPath('data.0.description', 'Cited before the session notes were reorganised.');
});

it('refuses new citations once the decision is settled', function () {
    $settled = DesignDecision::factory()->forIteration($this->iteration)->accepted()->create();

    $this->actingAs($this->designer)
        ->postJson(
            "/api/v1/workspaces/studio/games/bears/iterations/{$this->iteration->id}/decisions/{$settled->id}/evidence",
            ['type' => 'note', 'description' => 'Added after the fact.'],
        )
        ->assertConflict();

    expect(DecisionEvidence::query()->count())->toBe(0);
});

it('404s on a decision belonging to another cycle', function () {
    $other = Iteration::factory()->forGame($this->game)->inProgress()->create();
    $theirDecision = DesignDecision::factory()->forIteration($other)->create();

    $this->actingAs($this->designer)
        ->postJson(
            "/api/v1/workspaces/studio/games/bears/iterations/{$this->iteration->id}/decisions/{$theirDecision->id}/evidence",
            ['type' => 'note', 'description' => 'Citing on somebody else\'s decision.'],
        )
        ->assertNotFound();
});
