<?php

use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestParticipant;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Workspace\Domain\Models\Workspace;

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();

    $this->game = Game::factory()
        ->inWorkspace($this->workspace)
        ->withSlug('bears-and-bridges')
        ->active()
        ->create();

    $this->playtest = Playtest::factory()->forGame($this->game)->createdBy($this->designer)->create();

    $this->url = "/api/v1/workspaces/studio/games/bears-and-bridges/playtests/{$this->playtest->id}/summary";
});

/**
 * Zeroes for counts, nulls for averages. Reporting 0.0 as an average rating
 * would put an untested playtest at the bottom of any ordering it appeared in,
 * as though somebody had scored it badly.
 */
it('reports an untouched playtest honestly', function () {
    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.session_count', 0)
        ->assertJsonPath('data.participant_count', 0)
        ->assertJsonPath('data.observation_count', 0)
        ->assertJsonPath('data.feedback_count', 0)
        ->assertJsonPath('data.average_feedback_rating', null)
        ->assertJsonPath('data.average_session_duration_seconds', null)
        ->assertJsonPath('data.has_evidence', false);
});

it('counts sessions by how they ended', function () {
    PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->count(2)->create();
    PlaytestSession::factory()->forPlaytest($this->playtest)->cancelled()->create();
    PlaytestSession::factory()->forPlaytest($this->playtest)->create();

    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.session_count', 4)
        ->assertJsonPath('data.completed_session_count', 2)
        ->assertJsonPath('data.cancelled_session_count', 1);
});

it('counts everybody at the table, and the players separately', function () {
    $first = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();
    $second = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();

    PlaytestParticipant::factory()->forSession($first)->count(4)->create();
    PlaytestParticipant::factory()->forSession($first)->observer()->create();
    PlaytestParticipant::factory()->forSession($second)->count(3)->create();

    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.participant_count', 8)
        ->assertJsonPath('data.player_count', 7);
});

it('counts observations and feedback across every session', function () {
    $first = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();
    $second = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();

    PlaytestObservation::factory()->forSession($first)->count(5)->create();
    PlaytestObservation::factory()->forSession($second)->count(3)->create();
    PlaytestFeedback::factory()->forSession($first)->count(2)->create();

    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.observation_count', 8)
        ->assertJsonPath('data.feedback_count', 2)
        ->assertJsonPath('data.has_evidence', true);
});

it('averages the ratings that exist', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();

    PlaytestFeedback::factory()->forSession($session)->rated(5)->create();
    PlaytestFeedback::factory()->forSession($session)->rated(4)->create();
    PlaytestFeedback::factory()->forSession($session)->rated(2)->create();

    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.average_feedback_rating', 3.67)
        ->assertJsonPath('data.rated_feedback_count', 3);
});

/**
 * The rule that keeps the average meaningful: somebody who commented without
 * scoring did not score the game zero, so they are not in the denominator.
 */
it('leaves unrated feedback out of the average', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();

    PlaytestFeedback::factory()->forSession($session)->rated(4)->create();
    PlaytestFeedback::factory()->forSession($session)->create();
    PlaytestFeedback::factory()->forSession($session)->create();

    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.feedback_count', 3)
        ->assertJsonPath('data.rated_feedback_count', 1)
        ->assertJsonPath('data.average_feedback_rating', fn (int|float|null $average) => (float) $average === 4.0);
});

it('has no average rating when nobody scored anything', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();
    PlaytestFeedback::factory()->forSession($session)->count(3)->create();

    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.average_feedback_rating', null);
});

it('totals and averages the time actually spent playing', function () {
    PlaytestSession::factory()->forPlaytest($this->playtest)->completed(minutes: 60)->create();
    PlaytestSession::factory()->forPlaytest($this->playtest)->completed(minutes: 90)->create();

    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.total_duration_seconds', 150 * 60)
        ->assertJsonPath('data.total_duration_label', '2h 30m')
        ->assertJsonPath('data.average_session_duration_seconds', 75 * 60)
        ->assertJsonPath('data.average_session_duration_label', '1h 15m');
});

/**
 * A session that never ran has no duration, which is different from a duration
 * of zero — a zero would drag the average down as though somebody had played a
 * game that took no time.
 */
it('leaves sessions that never ran out of the duration average', function () {
    PlaytestSession::factory()->forPlaytest($this->playtest)->completed(minutes: 60)->create();
    PlaytestSession::factory()->forPlaytest($this->playtest)->cancelled()->create();
    PlaytestSession::factory()->forPlaytest($this->playtest)->create();

    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.session_count', 3)
        ->assertJsonPath('data.average_session_duration_seconds', 60 * 60);
});

it('has no duration figures while the only session is still running', function () {
    PlaytestSession::factory()->forPlaytest($this->playtest)->inProgress()->create();

    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.total_duration_seconds', null)
        ->assertJsonPath('data.average_session_duration_seconds', null);
});

it('counts only this playtest\'s evidence', function () {
    $ours = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();
    PlaytestObservation::factory()->forSession($ours)->count(2)->create();

    $otherPlaytest = Playtest::factory()->forGame($this->game)->create();
    $theirs = PlaytestSession::factory()->forPlaytest($otherPlaytest)->completed()->create();
    PlaytestObservation::factory()->forSession($theirs)->count(9)->create();

    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.observation_count', 2);
});

it('hides the summary of another studio\'s playtest', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->getJson($this->url)->assertNotFound();
});
