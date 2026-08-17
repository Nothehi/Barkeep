<?php

use Database\Seeders\DesignFrameworkSeeder;
use Database\Seeders\MechanicSeeder;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\ChecklistItemCompletion;
use Modules\DesignFramework\Domain\Models\CriterionEvaluation;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\GameDesign\Domain\Models\DesignRecord;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * Framework content that the game's own design record answers.
 *
 * A methodology asks two kinds of question. "Is the core decision meaningful?" is a judgement and
 * keeps the four-point scale, because nothing but a designer can answer it. "Are the player count and
 * playing time decided?" is a question about whether a fact has been written down — and asking
 * somebody to grade themselves on that was always the wrong shape, because they ticked it on their own
 * word while the platform had no idea whether it was true.
 *
 * These hold the second kind: it answers itself from the record, it refuses a grade, and it moves the
 * percentage without anybody ticking anything.
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
    $this->phase = DesignPhaseDefinition::factory()->inVersion($this->version)->named('Concept')->create();

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
});

/**
 * The whole point. Nobody grades anything; the designer writes down two to four players and the
 * criterion is answered.
 */
it('answers a factual criterion from the design record', function () {
    $criterion = DesignCriterion::factory()
        ->inPhase($this->phase)
        ->titled('Are the player count and playing time decided?')
        ->create(['satisfied_by' => 'player_count']);

    expect(($this->progress)()['criteria'])->toMatchArray(['completed' => 0, 'total' => 1]);

    DesignRecord::factory()->forGame($this->game)->forPlayers(2, 4)->create();

    expect(($this->progress)()['criteria'])->toMatchArray(['completed' => 1, 'total' => 1])
        ->and(($this->progress)()['percentage'])->toBe(100)
        ->and(CriterionEvaluation::query()->count())->toBe(0)
        ->and($criterion->fresh()->isAnsweredByTheDesignRecord())->toBeTrue();
});

it('answers a factual checklist item from the design record', function () {
    $checklist = Checklist::factory()->inPhase($this->phase)->create();
    ChecklistItem::factory()->inChecklist($checklist)->titled('Player count decided')
        ->create(['satisfied_by' => 'player_count']);

    expect(($this->progress)()['checklist_items'])->toMatchArray(['completed' => 0, 'total' => 1]);

    DesignRecord::factory()->forGame($this->game)->forPlayers(2, 4)->create();

    expect(($this->progress)()['checklist_items'])->toMatchArray(['completed' => 1, 'total' => 1])
        ->and(ChecklistItemCompletion::query()->count())->toBe(0);
});

/**
 * Accepting a grade would put a second, disagreeing answer beside the record, and the interface would
 * then have to choose which to believe. The screens never offer the buttons, so reaching this means
 * arriving from the API or a stale page.
 */
it('refuses a grade for a criterion the record answers', function () {
    $criterion = DesignCriterion::factory()->inPhase($this->phase)->create(['satisfied_by' => 'player_count']);

    $this->actingAs($this->designer)
        ->postJson(
            "/api/v1/workspaces/studio/games/bears-and-bridges/framework/criteria/{$criterion->id}/evaluate",
            ['status' => 'good'],
        )
        ->assertStatus(409);

    expect(CriterionEvaluation::query()->count())->toBe(0);
});

it('refuses a tick for a checklist item the record answers', function () {
    $checklist = Checklist::factory()->inPhase($this->phase)->create();
    $item = ChecklistItem::factory()->inChecklist($checklist)->create(['satisfied_by' => 'pitch']);

    $this->actingAs($this->designer)
        ->postJson(
            "/api/v1/workspaces/studio/games/bears-and-bridges/framework/checklist-items/{$item->id}/complete",
        )
        ->assertStatus(409);

    expect(ChecklistItemCompletion::query()->count())->toBe(0);
});

/**
 * The ordinary judgement criteria are untouched. This is the guarantee that matters most: the change
 * takes grading away from the questions that were never a designer's to grade, and leaves it on the
 * ones that are.
 */
it('leaves a judgement criterion graded by hand', function () {
    $criterion = DesignCriterion::factory()
        ->inPhase($this->phase)
        ->titled('Is the core decision meaningful?')
        ->create();

    expect($criterion->isAnsweredByTheDesignRecord())->toBeFalse();

    $this->actingAs($this->designer)
        ->post("/app/workspaces/studio/games/bears-and-bridges/framework/criteria/{$criterion->id}/evaluate", [
            'status' => 'good',
        ])
        ->assertRedirect();

    expect(CriterionEvaluation::query()->count())->toBe(1)
        ->and(($this->progress)()['criteria'])->toMatchArray(['completed' => 1, 'total' => 1]);
});

/**
 * A field holding a space cannot satisfy anything while looking empty on the settings screen. This is
 * the one thing that must not be possible, because it would be a way to claim a criterion without
 * answering it.
 */
it('does not accept whitespace as a recorded fact', function () {
    DesignCriterion::factory()->inPhase($this->phase)->create(['satisfied_by' => 'pitch']);

    $record = DesignRecord::factory()->forGame($this->game)->create();
    $record->forceFill(['pitch' => '   '])->save();

    expect(($this->progress)()['criteria'])->toMatchArray(['completed' => 0, 'total' => 1]);
});

it('reads each fact from the part of the record that holds it', function (string $fact, callable $decide) {
    DesignCriterion::factory()->inPhase($this->phase)->create(['satisfied_by' => $fact]);

    expect(($this->progress)()['criteria'])->toMatchArray(['completed' => 0]);

    $decide($this->game);

    expect(($this->progress)()['criteria'])->toMatchArray(['completed' => 1]);
})->with([
    'pitch' => ['pitch', fn (Game $game) => DesignRecord::factory()->forGame($game)->pitched('A game about bridges.')->create()],
    'player count' => ['player_count', fn (Game $game) => DesignRecord::factory()->forGame($game)->forPlayers(2, 4)->create()],
    'playing time' => ['play_time', fn (Game $game) => DesignRecord::factory()->forGame($game)->lasting(45, 60)->create()],
    'audience' => ['audience', fn (Game $game) => DesignRecord::factory()->forGame($game)->forAudience('Families.')->create()],
    'core action' => ['core_action', fn (Game $game) => DesignRecord::factory()->forGame($game)->withCoreLoop()->create()],
    'whole core loop' => ['core_loop', fn (Game $game) => DesignRecord::factory()->forGame($game)->withCoreLoop()->create()],
]);

/**
 * The composite fact is all five parts, because a loop missing its cost is not a loop somebody has
 * finished thinking about — which is exactly what the framework's core loop checklist is asking.
 */
it('does not answer the whole core loop from part of it', function () {
    DesignCriterion::factory()->inPhase($this->phase)->create(['satisfied_by' => 'core_loop']);

    $record = DesignRecord::factory()->forGame($this->game)->create();
    $record->forceFill(['core_action' => 'Place a worker.', 'core_cost' => 'It is spent.'])->save();

    expect(($this->progress)()['criteria'])->toMatchArray(['completed' => 0, 'total' => 1]);
});

it('answers a mechanics criterion once a term is claimed', function () {
    DesignCriterion::factory()->inPhase($this->phase)->create(['satisfied_by' => 'mechanics']);

    $record = DesignRecord::factory()->forGame($this->game)->create();

    expect(($this->progress)()['criteria'])->toMatchArray(['completed' => 0, 'total' => 1]);

    $record->mechanics()->attach(Mechanic::factory()->named('Worker placement')->create());

    expect(($this->progress)()['criteria'])->toMatchArray(['completed' => 1, 'total' => 1]);
});

/**
 * A fact nobody wrote a reader for cannot be answered. Reported as unrecorded rather than raising,
 * because a mistyped key in seeded content must not take down every phase page that shows it.
 */
it('treats an unknown fact as unanswerable rather than raising', function () {
    DesignCriterion::factory()->inPhase($this->phase)->create(['satisfied_by' => 'vibes']);

    DesignRecord::factory()->forGame($this->game)->decided()->create();

    expect(($this->progress)()['criteria'])->toMatchArray(['completed' => 0, 'total' => 1]);
});

/**
 * The checklist panel reads the tick from the same place the percentage does, or the box beside
 * "Player count decided" would sit empty while the phase total counted it.
 */
it('shows a satisfied item as ticked on the checklist', function () {
    $checklist = Checklist::factory()->inPhase($this->phase)->create();
    $item = ChecklistItem::factory()->inChecklist($checklist)->titled('Player count decided')
        ->create(['satisfied_by' => 'player_count']);

    DesignRecord::factory()->forGame($this->game)->forPlayers(2, 4)->create();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games/bears-and-bridges/framework/checklists')
        ->assertOk()
        ->assertJsonPath('data.0.items.0.checklist_item_id', $item->id)
        ->assertJsonPath('data.0.items.0.is_complete', true)
        ->assertJsonPath('data.0.required.completed', 1);
});

/**
 * The content is the methodology's and the record is the game's, and they stay in separate
 * collections all the way to the client — the same separation the evaluations follow.
 */
it('sends the content\'s fact and the game\'s answer separately', function () {
    DesignCriterion::factory()
        ->inPhase($this->phase)
        ->titled('Are the player count and playing time decided?')
        ->create(['satisfied_by' => 'player_count']);

    DesignRecord::factory()->forGame($this->game)->forPlayers(2, 4)->create();

    $this->actingAs($this->designer)
        ->get(route('games.framework.phases.show', ['studio', 'bears-and-bridges', 'concept']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('criteria.data.0.satisfied_by', 'player_count')
                ->where('criteria.data.0.satisfied_by_label', 'the player count')
                ->where('criteria.data.0.is_answered_by_the_design_record', true)
                ->where('design.facts.player_count', true)
                ->where('design.facts.pitch', false)
                ->has('design.settings_url'),
        );
});

/**
 * Two studios on the same edition answer the same factual criterion from their own records. The
 * separation the module is built around holds for derived answers exactly as it does for graded ones.
 */
it('keeps two studios\' factual answers apart', function () {
    DesignCriterion::factory()->inPhase($this->phase)->create(['satisfied_by' => 'player_count']);

    $theirs = User::factory()->create();
    $theirWorkspace = Workspace::factory()->ownedBy($theirs)->withSlug('rivals')->create();
    $theirGame = Game::factory()->inWorkspace($theirWorkspace)->withSlug('theirs')->active()->create();

    GameFramework::factory()->forGame($theirGame)->following($this->version)->adoptedBy($theirs)->create();

    DesignRecord::factory()->forGame($this->game)->forPlayers(2, 4)->create();

    expect(($this->progress)()['criteria'])->toMatchArray(['completed' => 1]);

    $this->actingAs($theirs)
        ->getJson('/api/v1/workspaces/rivals/games/theirs/framework/progress')
        ->assertOk()
        ->assertJsonPath('data.criteria.completed', 0);
});

/**
 * The seeded methodology is where this becomes visible: on a fresh install, filling in the design
 * settings answers most of Concept and Core loop without a single tick.
 */
it('answers the seeded framework\'s factual content', function () {
    $this->seed(MechanicSeeder::class);
    $this->seed(DesignFrameworkSeeder::class);

    $seeded = FrameworkVersion::query()
        ->whereRelation('framework', 'slug', 'board-game-design')
        ->sole();

    $game = Game::factory()->inWorkspace($this->workspace)->withSlug('seeded')->active()->create();
    GameFramework::factory()->forGame($game)->following($seeded)->adoptedBy($this->designer)->create();

    $before = $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games/seeded/framework/progress')
        ->assertOk()
        ->json('data.overall.completed');

    DesignRecord::factory()->forGame($game)->decided()->create();

    $after = $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games/seeded/framework/progress')
        ->assertOk()
        ->json('data.overall.completed');

    expect($before)->toBe(0)
        ->and($after)->toBeGreaterThan(8);
});

/**
 * An author attaches a fact through the builder rather than only through a seeder, and a key nothing
 * can read is refused at the form — content naming one would sit unanswerable forever, counting
 * against every game's progress.
 */
it('lets an author attach and detach a fact', function () {
    $admin = User::factory()->create(['email' => 'author@barkeep.test']);
    config()->set('design-framework.administrators', ['author@barkeep.test']);

    $draft = FrameworkVersion::factory()->nextFor($this->framework)->create();
    $builder = "/app/frameworks/bgdf/versions/{$draft->version_number}";

    $this->actingAs($admin)
        ->post($builder.'/criteria', [
            'title' => 'Are the player count and playing time decided?',
            'satisfied_by' => 'player_count',
        ])
        ->assertRedirect();

    $criterion = DesignCriterion::query()->where('framework_version_id', $draft->id)->sole();

    expect($criterion->satisfied_by)->toBe('player_count');

    $this->actingAs($admin)
        ->patch($builder."/criteria/{$criterion->id}", [
            'title' => $criterion->title,
            'satisfied_by' => null,
        ])
        ->assertRedirect();

    expect($criterion->fresh()->satisfied_by)->toBeNull();
});

it('refuses a fact nothing knows how to read', function () {
    $admin = User::factory()->create(['email' => 'author@barkeep.test']);
    config()->set('design-framework.administrators', ['author@barkeep.test']);

    $draft = FrameworkVersion::factory()->nextFor($this->framework)->create();

    $this->actingAs($admin)
        ->post("/app/frameworks/bgdf/versions/{$draft->version_number}/criteria", [
            'title' => 'Is it good?',
            'satisfied_by' => 'vibes',
        ])
        ->assertSessionHasErrors('satisfied_by');

    expect(DesignCriterion::query()->where('framework_version_id', $draft->id)->count())->toBe(0);
});
