<?php

use Illuminate\Database\QueryException;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\DecisionStatus;
use Modules\PrototypeIteration\Domain\Enums\IterationOutcome;
use Modules\PrototypeIteration\Domain\Enums\PrototypeStatus;
use Modules\PrototypeIteration\Domain\Models\DesignChange;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\Workspace\Domain\Models\Workspace;

/*
|--------------------------------------------------------------------------
| Historical integrity
|--------------------------------------------------------------------------
|
| Section 53. A completed iteration is a historical record: the next cycle was built on what it
| concluded, so nothing about it stays editable. Nobody may quietly change what the studio decided, why,
| or what was on the table when they decided it.
|
| The corollary matters as much: it all stays *readable*. A design history that vanished when a project
| was archived would be no history at all, so archival revokes writing and never reading.
|
*/

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();
    $this->game = Game::factory()->inWorkspace($this->workspace)->withSlug('bears')->active()->create();

    $this->completed = Iteration::factory()->forGame($this->game)->completed()->create([
        'objective' => 'Reduce the time players spend waiting between decisions.',
        'summary' => 'Downtime fell, but not enough.',
    ]);

    $this->base = "/api/v1/workspaces/studio/games/bears/iterations/{$this->completed->id}";
});

it('refuses to rewrite a completed cycle\'s objective', function () {
    $this->actingAs($this->designer)->patchJson($this->base, [
        'objective' => 'Actually we were trying to make combat more interesting all along.',
    ])->assertForbidden();

    expect($this->completed->refresh()->objective)
        ->toBe('Reduce the time players spend waiting between decisions.');
});

it('refuses to rewrite a completed cycle\'s hypothesis', function () {
    $this->actingAs($this->designer)->patchJson($this->base, [
        'hypothesis' => 'A prediction adjusted to match the outcome.',
    ])->assertForbidden();
});

it('refuses to repoint a completed cycle at a different build', function () {
    $prototype = Prototype::factory()->forGame($this->game)->create();
    $build = PrototypeVersion::factory()->nextFor($prototype)->create();

    $this->actingAs($this->designer)->patchJson($this->base, [
        'prototype_version_id' => $build->id,
    ])->assertForbidden();

    expect($this->completed->refresh()->prototype_version_id)->not->toBe($build->id);
});

it('refuses to change a completed cycle\'s outcome or summary', function () {
    $this->actingAs($this->designer)->postJson($this->base.'/complete', [
        'outcome' => 'success',
        'summary' => 'On reflection this went perfectly.',
    ])->assertForbidden();

    expect($this->completed->refresh()->outcome)->toBe(IterationOutcome::Partial)
        ->and($this->completed->summary)->toBe('Downtime fell, but not enough.');
});

it('refuses to add design work to a completed cycle', function (string $path, array $payload) {
    $this->actingAs($this->designer)->postJson($this->base.'/'.$path, $payload)
        ->assertForbidden();
})->with([
    ['changes', ['title' => 'Late change', 'reason' => 'Recorded after the cycle closed.']],
    ['experiments', ['title' => 'Late experiment', 'question' => 'Something asked too late?']],
    ['decisions', ['title' => 'Late', 'decision' => 'Decided late.', 'reason' => 'After the fact.']],
]);

it('refuses to reword a completed cycle\'s changes', function () {
    $change = DesignChange::factory()->forIteration($this->completed)->titled('Removed reactions')->create();

    $this->actingAs($this->designer)->patchJson($this->base.'/changes/'.$change->id, [
        'title' => 'Rewritten history',
        'reason' => 'Changing what the record says was done.',
    ])->assertForbidden();

    expect($change->refresh()->title)->toBe('Removed reactions');
});

it('refuses to remove a completed cycle\'s changes', function () {
    $change = DesignChange::factory()->forIteration($this->completed)->create();

    $this->actingAs($this->designer)->deleteJson($this->base.'/changes/'.$change->id)
        ->assertForbidden();

    expect(DesignChange::query()->count())->toBe(1);
});

it('refuses to reword a completed cycle\'s decisions', function () {
    $decision = DesignDecision::factory()->forIteration($this->completed)->accepted()->create([
        'decision' => 'Remove the reaction phase permanently.',
    ]);

    $this->actingAs($this->designer)->patchJson($this->base.'/decisions/'.$decision->id, [
        'title' => 'Reactions',
        'decision' => 'Keep the reaction phase after all.',
        'reason' => 'Rewriting an agreed decision.',
    ])->assertForbidden();

    expect($decision->refresh()->decision)->toBe('Remove the reaction phase permanently.');
});

it('refuses to reword a completed cycle\'s experiment predictions', function () {
    $experiment = DesignExperiment::factory()->forIteration($this->completed)->completed()->create([
        'hypothesis' => 'Downtime will fall.',
    ]);

    $this->actingAs($this->designer)->patchJson($this->base.'/experiments/'.$experiment->id, [
        'title' => $experiment->title,
        'question' => $experiment->question,
        'hypothesis' => 'Downtime will rise, which is exactly what we predicted.',
    ])->assertForbidden();

    expect($experiment->refresh()->hypothesis)->toBe('Downtime will fall.');
});

it('keeps a completed cycle and all its work readable', function () {
    DesignChange::factory()->forIteration($this->completed)->count(2)->create();
    DesignDecision::factory()->forIteration($this->completed)->accepted()->create();

    $this->actingAs($this->designer)->getJson($this->base)->assertOk();
    $this->actingAs($this->designer)->getJson($this->base.'/changes')->assertOk()->assertJsonCount(2, 'data');
    $this->actingAs($this->designer)->getJson($this->base.'/decisions')->assertOk()->assertJsonCount(1, 'data');
    $this->actingAs($this->designer)->getJson($this->base.'/timeline')->assertOk();
    $this->actingAs($this->designer)->getJson($this->base.'/summary')->assertOk();
});

/**
 * The corollary that makes the whole module worth building: archiving the project freezes the record
 * rather than hiding it.
 */
it('keeps a design history readable after the game is archived', function () {
    DesignChange::factory()->forIteration($this->completed)->create();

    $this->game->forceFill(['status' => GameStatus::Archived])->save();

    $this->actingAs($this->designer)->getJson($this->base)->assertOk();
    $this->actingAs($this->designer)->getJson($this->base.'/timeline')->assertOk();
});

it('refuses new design work once the game is archived', function () {
    $open = Iteration::factory()->forGame($this->game)->inProgress()->create();

    $this->game->forceFill(['status' => GameStatus::Archived])->save();

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears/iterations/{$open->id}/changes", [
            'title' => 'Change after archival',
            'reason' => 'Trying to record work on a closed project.',
        ])
        ->assertForbidden();
});

it('keeps an archived prototype\'s versions and iterations readable', function () {
    $prototype = Prototype::factory()->forGame($this->game)->create();
    $build = PrototypeVersion::factory()->nextFor($prototype)->create();
    Iteration::factory()->forPrototypeVersion($build)->completed()->create();

    $prototype->forceFill([
        'status' => PrototypeStatus::Archived,
    ])->save();

    $prototypeBase = "/api/v1/workspaces/studio/games/bears/prototypes/{$prototype->id}";

    $this->actingAs($this->designer)->getJson($prototypeBase)->assertOk();
    $this->actingAs($this->designer)->getJson($prototypeBase.'/versions')->assertOk()->assertJsonCount(1, 'data');
});

/**
 * Section 53's escape hatch. History is not rewritten — a later change of mind is a new decision, and it
 * can be made in a new cycle while the old one stays exactly as it was.
 */
it('records a change of mind as a new decision in a new cycle', function () {
    $original = DesignDecision::factory()->forIteration($this->completed)->accepted()->create([
        'decision' => 'Remove the reaction phase permanently.',
    ]);

    $next = Iteration::factory()->forGame($this->game)->inProgress()->create();

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears/iterations/{$next->id}/decisions", [
            'title' => 'Reaction phase, revisited',
            'decision' => 'Reinstate a shortened reaction window.',
            'reason' => 'Expert players missed the interaction and downtime is now under control.',
        ])
        ->assertCreated();

    expect($original->refresh()->status)->toBe(DecisionStatus::Accepted)
        ->and($original->decision)->toBe('Remove the reaction phase permanently.')
        ->and(DesignDecision::query()->count())->toBe(2);
});

/**
 * The database refuses what the application refuses. A prototype version an iteration was run against
 * cannot be removed out from under it, so the record can never point at nothing.
 */
it('refuses at the database to remove a build an iteration was run against', function () {
    $prototype = Prototype::factory()->forGame($this->game)->create();
    $build = PrototypeVersion::factory()->nextFor($prototype)->create();
    Iteration::factory()->forPrototypeVersion($build)->create();

    expect(fn () => $build->delete())->toThrow(QueryException::class);
});

it('refuses at the database to remove a design version an iteration was run against', function () {
    $version = GameVersion::query()
        ->findOrFail($this->completed->game_version_id);

    expect(fn () => $version->delete())->toThrow(QueryException::class);
});
