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
 * The JSON surface.
 *
 * The API and the screens hand off to the same commands, form requests and queries, so this does not
 * re-test the rules — those are held by the screen tests beside it. What it holds is the shape of
 * the JSON: the statuses, the envelopes and the couple of places the two delivery layers answer the
 * same situation differently on purpose.
 *
 * The clearest of those: a game that follows no framework answers 404 here, because "show me this
 * game's framework" has no honest answer — while the screen renders a page offering to adopt one.
 */
beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->admin = User::factory()->create(['email' => 'author@barkeep.test']);
    config()->set('design-framework.administrators', ['author@barkeep.test']);

    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();

    $this->game = Game::factory()
        ->inWorkspace($this->workspace)
        ->withSlug('bears-and-bridges')
        ->active()
        ->create();

    $this->framework = Framework::factory()->named('BGDF')->withSlug('bgdf')->published()->create();
    $this->version = FrameworkVersion::factory()->nextFor($this->framework)->published()->create();
    $this->phase = DesignPhaseDefinition::factory()->inVersion($this->version)->named('Core loop')->create();

    $this->game_url = '/api/v1/workspaces/studio/games/bears-and-bridges/framework';
});

it('lists the platform\'s methodologies', function () {
    $this->actingAs($this->designer)
        ->getJson('/api/v1/frameworks')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'bgdf');
});

it('shows one methodology and its editions', function () {
    $this->actingAs($this->designer)
        ->getJson('/api/v1/frameworks/bgdf')
        ->assertOk()
        ->assertJsonPath('data.slug', 'bgdf');

    $this->actingAs($this->designer)
        ->getJson('/api/v1/frameworks/bgdf/versions')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.version_number', 1);
});

it('serves an edition\'s content by kind', function () {
    DesignPrinciple::factory()->inPhase($this->phase)->create();
    DesignCriterion::factory()->inPhase($this->phase)->create();
    DesignPractice::factory()->inPhase($this->phase)->create();
    DesignPrompt::factory()->inPhase($this->phase)->create();

    $checklist = Checklist::factory()->inPhase($this->phase)->create();
    ChecklistItem::factory()->inChecklist($checklist)->create();

    foreach (['phases', 'principles', 'criteria', 'practices', 'prompts', 'checklists'] as $kind) {
        $this->actingAs($this->designer)
            ->getJson("/api/v1/frameworks/bgdf/versions/1/{$kind}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    $this->actingAs($this->designer)
        ->getJson('/api/v1/frameworks/bgdf/versions/1/phases/core-loop')
        ->assertOk()
        ->assertJsonPath('data.slug', 'core-loop');
});

it('refuses to write a methodology without the platform privilege', function () {
    $this->actingAs($this->designer)
        ->postJson('/api/v1/frameworks', ['name' => 'Mine'])
        ->assertForbidden();

    $this->actingAs($this->admin)
        ->postJson('/api/v1/frameworks', ['name' => 'Mine'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Mine');
});

/**
 * A game follows one methodology at a time, so this is a singleton sub-resource rather than a
 * collection — and 404 is the honest answer when it follows none.
 */
it('answers 404 for a game that follows no framework', function () {
    $this->actingAs($this->designer)
        ->getJson($this->game_url)
        ->assertNotFound();

    $this->actingAs($this->designer)
        ->getJson("{$this->game_url}/progress")
        ->assertNotFound();
});

it('adopts a framework and answers 201', function () {
    $this->actingAs($this->designer)
        ->postJson($this->game_url, ['framework_version_id' => $this->version->id])
        ->assertCreated()
        ->assertJsonPath('data.framework_version_id', $this->version->id)
        ->assertJsonPath('data.status', 'active');

    $this->actingAs($this->designer)
        ->getJson($this->game_url)
        ->assertOk()
        ->assertJsonPath('data.version.label', 'v1')
        ->assertJsonPath('data.framework.slug', 'bgdf');
});

it('refuses a second adoption with a conflict', function () {
    GameFramework::factory()->forGame($this->game)->following($this->version)->create();

    $other = FrameworkVersion::factory()
        ->nextFor(Framework::factory()->withSlug('other')->published()->create())
        ->published()
        ->create();

    $this->actingAs($this->designer)
        ->postJson($this->game_url, ['framework_version_id' => $other->id])
        ->assertStatus(409)
        ->assertJsonPath('message', 'This game is already following a design framework.');
});

it('records and lists every kind of work', function () {
    GameFramework::factory()->forGame($this->game)->following($this->version)->create();

    $criterion = DesignCriterion::factory()->inPhase($this->phase)->create();
    $practice = DesignPractice::factory()->inPhase($this->phase)->create();
    $prompt = DesignPrompt::factory()->inPhase($this->phase)->create();

    $checklist = Checklist::factory()->inPhase($this->phase)->create();
    $item = ChecklistItem::factory()->inChecklist($checklist)->create();

    $this->actingAs($this->designer)
        ->postJson("{$this->game_url}/criteria/{$criterion->id}/evaluate", ['status' => 'good'])
        ->assertSuccessful();

    $this->actingAs($this->designer)
        ->postJson("{$this->game_url}/practices/{$practice->id}/complete")
        ->assertSuccessful();

    $this->actingAs($this->designer)
        ->postJson("{$this->game_url}/checklist-items/{$item->id}/complete")
        ->assertSuccessful();

    $this->actingAs($this->designer)
        ->postJson("{$this->game_url}/prompts/{$prompt->id}/respond", ['response' => 'A thought.'])
        ->assertSuccessful();

    $this->actingAs($this->designer)
        ->getJson("{$this->game_url}/evaluations")
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($this->designer)
        ->getJson("{$this->game_url}/practice-completions")
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($this->designer)
        ->getJson("{$this->game_url}/prompt-responses")
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($this->designer)
        ->getJson("{$this->game_url}/checklists")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.items.0.is_complete', true);
});

it('moves the adoption through its lifecycle', function () {
    GameFramework::factory()->forGame($this->game)->following($this->version)->create();

    $this->actingAs($this->designer)
        ->postJson("{$this->game_url}/pause")
        ->assertSuccessful();

    $this->actingAs($this->designer)
        ->getJson($this->game_url)
        ->assertJsonPath('data.status', 'paused')
        ->assertJsonPath('data.accepts_progress', false);

    $this->actingAs($this->designer)
        ->postJson("{$this->game_url}/resume")
        ->assertSuccessful();

    $this->actingAs($this->designer)
        ->postJson("{$this->game_url}/complete")
        ->assertSuccessful();

    $this->actingAs($this->designer)
        ->getJson($this->game_url)
        ->assertJsonPath('data.status', 'completed');
});

/**
 * The two delivery layers report a rule violation differently, and deliberately.
 *
 * A form wants the refusal beside the field that caused it, so the screens turn a violation with a
 * field into a validation error. An API client wants the status and the sentence, so JSON gets the
 * message and the status the violation itself declares — 422 here, 409 for a game that already
 * follows something.
 */
it('reports an unadoptable edition as a plain refusal to a json caller', function () {
    $draft = FrameworkVersion::factory()->nextFor($this->framework)->create();

    $this->actingAs($this->designer)
        ->postJson($this->game_url, ['framework_version_id' => $draft->id])
        ->assertStatus(422)
        ->assertJsonPath(
            'message',
            'This framework version is still a draft and cannot be adopted yet.',
        );

    $this->actingAs($this->designer)
        ->post(route('games.framework.store', ['studio', 'bears-and-bridges']), [
            'framework_version_id' => $draft->id,
        ])
        ->assertSessionHasErrors('framework_version_id');
});
