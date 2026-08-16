<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Events\FeedbackCreated;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
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
    $this->session = PlaytestSession::factory()->forPlaytest($this->playtest)->inProgress()->create();

    $this->url = "/api/v1/workspaces/studio/games/bears-and-bridges/playtests/{$this->playtest->id}"
        ."/sessions/{$this->session->id}/feedback";
});

it('records what a participant said', function () {
    $participant = PlaytestParticipant::factory()->forSession($this->session)->guest('Sam')->create();

    $this->actingAs($this->designer)
        ->postJson($this->url, [
            'content' => "I didn't understand why I couldn't buy another card.",
            'participant_id' => $participant->id,
            'rating' => 4,
        ])
        ->assertCreated()
        ->assertJsonPath('data.content', "I didn't understand why I couldn't buy another card.")
        ->assertJsonPath('data.participant.display_name', 'Sam')
        ->assertJsonPath('data.rating', 4)
        ->assertJsonPath('data.rating_label', '4/5')
        ->assertJsonPath('data.is_anonymous', false);
});

/**
 * Often the honest kind: somebody who did not enjoy a friend's game says so
 * more readily when their name is not on it.
 */
it('records anonymous feedback', function () {
    $this->actingAs($this->designer)
        ->postJson($this->url, ['content' => 'The game felt too long.'])
        ->assertCreated()
        ->assertJsonPath('data.participant_id', null)
        ->assertJsonPath('data.is_anonymous', true);
});

/**
 * A comment without a score is still feedback, and the most useful comments
 * usually arrive without one.
 */
it('records feedback with no rating at all', function () {
    $this->actingAs($this->designer)
        ->postJson($this->url, ['content' => 'I never knew what my best move was.'])
        ->assertCreated()
        ->assertJsonPath('data.rating', null)
        ->assertJsonPath('data.rating_label', null);
});

it('accepts every point on the scale', function (int $rating) {
    $this->actingAs($this->designer)
        ->postJson($this->url, ['content' => 'An opinion.', 'rating' => $rating])
        ->assertCreated()
        ->assertJsonPath('data.rating', $rating);
})->with([1, 2, 3, 4, 5]);

it('refuses a rating off the scale', function (mixed $rating) {
    $this->actingAs($this->designer)
        ->postJson($this->url, ['content' => 'An opinion.', 'rating' => $rating])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('rating');

    expect(PlaytestFeedback::query()->count())->toBe(0);
})->with([
    'zero' => 0,
    'six' => 6,
    'negative' => -1,
    'a fraction' => 4.5,
    'not a number' => 'great',
]);

it('publishes the scale so a screen does not have to know it', function () {
    $this->actingAs($this->designer)
        ->postJson($this->url, ['content' => 'An opinion.', 'rating' => 3])
        ->assertCreated()
        ->assertJsonPath('data.rating_max', 5);
});

/**
 * Two people are involved and they are usually different. Collapsing them
 * would turn "the facilitator wrote this down" into "the facilitator said
 * this".
 */
it('separates who said it from who typed it in', function () {
    $participant = PlaytestParticipant::factory()->forSession($this->session)->guest('Sam')->create();

    $this->actingAs($this->designer)
        ->postJson($this->url, ['content' => 'The combat was fun.', 'participant_id' => $participant->id])
        ->assertCreated()
        ->assertJsonPath('data.participant_id', $participant->id)
        ->assertJsonPath('data.created_by', $this->designer->id);
});

it('refuses to attribute feedback to another session\'s participant', function () {
    $other = PlaytestSession::factory()->forPlaytest($this->playtest)->inProgress()->create();
    $theirs = PlaytestParticipant::factory()->forSession($other)->guest('Elsewhere')->create();

    $this->actingAs($this->designer)
        ->postJson($this->url, ['content' => 'Something.', 'participant_id' => $theirs->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('participant_id');
});

it('requires something to have actually been said', function () {
    $this->actingAs($this->designer)
        ->postJson($this->url, ['content' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('content');
});

it('lists feedback oldest first', function () {
    PlaytestFeedback::factory()->forSession($this->session)->saying('First')->create(['created_at' => now()->subHour()]);
    PlaytestFeedback::factory()->forSession($this->session)->saying('Second')->create(['created_at' => now()]);

    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.content', 'First')
        ->assertJsonPath('data.1.content', 'Second');
});

it('corrects a mishearing while the session is still open', function () {
    $feedback = PlaytestFeedback::factory()->forSession($this->session)->saying('Too long')->rated(2)->create();

    $this->actingAs($this->designer)
        ->patchJson("{$this->url}/{$feedback->id}", [
            'content' => 'The middle game felt too long.',
            'rating' => 3,
        ])
        ->assertOk()
        ->assertJsonPath('data.content', 'The middle game felt too long.')
        ->assertJsonPath('data.rating', 3);
});

/**
 * A null rating means "did not put a number on it", which has to stay
 * different from a low score or every average is wrong.
 */
it('clears a rating that should never have been there', function () {
    $feedback = PlaytestFeedback::factory()->forSession($this->session)->saying('Fun.')->rated(5)->create();

    $this->actingAs($this->designer)
        ->patchJson("{$this->url}/{$feedback->id}", ['content' => 'Fun.', 'rating' => null])
        ->assertOk()
        ->assertJsonPath('data.rating', null);
});

it('withdraws feedback while the session is still open', function () {
    $feedback = PlaytestFeedback::factory()->forSession($this->session)->create();

    $this->actingAs($this->designer)
        ->deleteJson("{$this->url}/{$feedback->id}")
        ->assertNoContent();

    expect(PlaytestFeedback::query()->count())->toBe(0);
});

/**
 * Stronger than the equivalent rule for observations, because feedback is
 * somebody else's words: once the session ends, what a participant said about
 * a designer's game stops being something the designer can remove.
 */
it('will not add, change or remove feedback once the session has ended', function (string $method) {
    $ended = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();
    $feedback = PlaytestFeedback::factory()->forSession($ended)->create();

    $base = "/api/v1/workspaces/studio/games/bears-and-bridges/playtests/{$this->playtest->id}"
        ."/sessions/{$ended->id}/feedback";

    $response = match ($method) {
        'create' => $this->actingAs($this->designer)->postJson($base, ['content' => 'One more thought.']),
        'update' => $this->actingAs($this->designer)->patchJson("{$base}/{$feedback->id}", ['content' => 'Actually...']),
        'delete' => $this->actingAs($this->designer)->deleteJson("{$base}/{$feedback->id}"),
    };

    $response->assertForbidden();
})->with(['create', 'update', 'delete']);

it('announces feedback, with a null rating rather than a zero', function () {
    Event::fake([FeedbackCreated::class]);

    $this->actingAs($this->designer)
        ->postJson($this->url, ['content' => 'No number from me.'])
        ->assertCreated();

    Event::assertDispatched(
        FeedbackCreated::class,
        fn (FeedbackCreated $event) => $event->sessionId === $this->session->id
            && $event->gameId === $this->game->id
            && $event->rating === null,
    );
});
