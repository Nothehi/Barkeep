<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Enums\Complexity;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Enums\MechanicStatus;
use Modules\GameDesign\Domain\Events\DesignRecordUpdated;
use Modules\GameDesign\Domain\Models\DesignRecord;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * What has been decided about a game's design.
 *
 * The point of the record is that the platform can read it. A designer used to tick "player count
 * decided" on their own word; now they write down two to four, and anything that wants to know can
 * ask. Most of what is worth testing here is therefore about the difference between an answer and
 * the absence of one — a whitespace-only field must not count as decided, and a game that has
 * decided nothing must carry no row at all.
 */
beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();

    $this->game = Game::factory()
        ->inWorkspace($this->workspace)
        ->withSlug('bears-and-bridges')
        ->active()
        ->create();

    $this->url = '/app/workspaces/studio/games/bears-and-bridges/design';
    $this->api = '/api/v1/workspaces/studio/games/bears-and-bridges/design';
});

it('has no record until something is decided', function () {
    expect(DesignRecord::query()->count())->toBe(0);

    $this->actingAs($this->designer)
        ->getJson($this->api)
        ->assertNotFound();
});

it('records what has been decided', function () {
    $this->actingAs($this->designer)
        ->patch($this->url, [
            'pitch' => 'Build bridges before the river rises.',
            'player_count_min' => 2,
            'player_count_max' => 4,
            'play_time_min' => 45,
            'play_time_max' => 60,
            'target_age_min' => 10,
            'complexity' => Complexity::Gateway->value,
            'audience' => 'Families who already play a few games a year.',
            'core_action' => 'Place a worker.',
            'core_cost' => 'The worker is spent.',
            'core_reward' => 'The space pays out.',
            'win_condition' => 'Most points.',
            'failure_condition' => 'The river reaches the town.',
        ])
        ->assertRedirect();

    $record = DesignRecord::query()->sole();

    expect($record->game_id)->toBe($this->game->id)
        ->and($record->pitch)->toBe('Build bridges before the river rises.')
        ->and($record->player_count_min)->toBe(2)
        ->and($record->player_count_max)->toBe(4)
        ->and($record->complexity)->toBe(Complexity::Gateway)
        ->and($record->target_age_min)->toBe(10)
        ->and($record->hasCompleteCoreLoop())->toBeTrue();
});

/**
 * Somebody typing "2" into the first box and leaving the second alone means a two-player game.
 * Making them type it twice would be pedantry.
 */
it('reads a single player count as a fixed one', function () {
    $this->actingAs($this->designer)
        ->patch($this->url, ['player_count_min' => 2])
        ->assertRedirect();

    $record = DesignRecord::query()->sole();

    expect($record->player_count_min)->toBe(2)
        ->and($record->player_count_max)->toBe(2)
        ->and($record->playerCount()->isFixed())->toBeTrue();
});

/**
 * Almost always two fields filled in the wrong order, and the message says so rather than restating
 * the bounds. The rule lives in the value object, so a console command is held to it too.
 */
it('refuses a player count range that runs backwards', function () {
    $this->actingAs($this->designer)
        ->patch($this->url, ['player_count_min' => 4, 'player_count_max' => 2])
        ->assertSessionHasErrors('player_count_min');

    expect(DesignRecord::query()->count())->toBe(0);
});

it('refuses a playing time range that runs backwards', function () {
    $this->actingAs($this->designer)
        ->patch($this->url, ['play_time_min' => 90, 'play_time_max' => 30])
        ->assertSessionHasErrors('play_time_min');
});

it('refuses a player count outside the bounds', function (array $payload, string $field) {
    $this->actingAs($this->designer)
        ->patch($this->url, $payload)
        ->assertSessionHasErrors($field);
})->with([
    'nobody' => [['player_count_min' => 0], 'player_count_min'],
    'a crowd' => [['player_count_max' => 500], 'player_count_max'],
    'no time at all' => [['play_time_min' => 0], 'play_time_min'],
    'longer than a day' => [['play_time_max' => 5000], 'play_time_max'],
]);

it('refuses a weight that is not on the scale', function () {
    $this->actingAs($this->designer)
        ->patch($this->url, ['complexity' => 'crunchy'])
        ->assertSessionHasErrors('complexity');
});

/**
 * An update is a replacement, so a field left out is a field the designer has decided they no
 * longer know. That is the only reading of a form that always sends every field, and it is what
 * makes deleting anything possible.
 */
it('clears a decision that was left out', function () {
    DesignRecord::factory()->forGame($this->game)->pitched('An old idea.')->forPlayers(2, 4)->create();

    $this->actingAs($this->designer)
        ->patch($this->url, ['player_count_min' => 3, 'player_count_max' => 5])
        ->assertRedirect();

    $record = DesignRecord::query()->sole();

    expect($record->pitch)->toBeNull()
        ->and($record->player_count_min)->toBe(3);
});

/**
 * A field containing a space would otherwise satisfy a framework criterion asking whether the
 * question had been answered, which is the one thing that must not be possible.
 */
it('does not treat whitespace as an answer', function () {
    $this->actingAs($this->designer)
        ->patch($this->url, ['pitch' => '   ', 'core_action' => "\n\t"])
        ->assertRedirect();

    expect(DesignRecord::query()->count())->toBe(0);
});

it('says nothing happened when a save changes nothing', function () {
    DesignRecord::factory()->forGame($this->game)->forPlayers(2, 4)->create();

    Event::fake([DesignRecordUpdated::class]);

    $this->actingAs($this->designer)
        ->patch($this->url, ['player_count_min' => 2, 'player_count_max' => 4])
        ->assertRedirect();

    Event::assertNotDispatched(DesignRecordUpdated::class);
});

/**
 * The event is what lets a methodology's progress move without anybody ticking anything, so what it
 * names matters. It carries the fields rather than their values: a consumer wanting to know what the
 * design says should read the record, and a payload would push design thinking into every log.
 */
it('announces which decisions changed', function () {
    Event::fake([DesignRecordUpdated::class]);

    $this->actingAs($this->designer)
        ->patch($this->url, ['player_count_min' => 2, 'player_count_max' => 4]);

    Event::assertDispatched(function (DesignRecordUpdated $event): bool {
        return $event->gameId === $this->game->id
            && $event->updatedBy === $this->designer->id
            && in_array('player_count_min', $event->changed, strict: true)
            && ! in_array('pitch', $event->changed, strict: true);
    });
});

it('claims mechanics from the shared vocabulary', function () {
    $placement = Mechanic::factory()->named('Worker placement')->create();
    $sets = Mechanic::factory()->named('Set collection')->create();

    $this->actingAs($this->designer)
        ->patch($this->url, ['mechanics' => [$placement->id, $sets->id]])
        ->assertRedirect();

    expect(DesignRecord::query()->sole()->mechanics->pluck('name')->all())
        ->toBe(['Set collection', 'Worker placement']);
});

it('announces a change of mechanics like any other decision', function () {
    $placement = Mechanic::factory()->named('Worker placement')->create();

    Event::fake([DesignRecordUpdated::class]);

    $this->actingAs($this->designer)
        ->patch($this->url, ['mechanics' => [$placement->id]]);

    Event::assertDispatched(
        fn (DesignRecordUpdated $event): bool => in_array('mechanics', $event->changed, strict: true),
    );
});

/**
 * A picker can be stale. The whole submission is refused rather than the unclaimable ids being
 * silently dropped — a designer who saves five mechanics and gets four back has been told nothing.
 */
it('refuses a mechanic id that names nothing', function () {
    $real = Mechanic::factory()->named('Worker placement')->create();

    $this->actingAs($this->designer)
        ->patch($this->url, [
            'mechanics' => [$real->id, '00000000-0000-4000-8000-000000000000'],
        ])
        ->assertSessionHasErrors('mechanics');

    expect(DesignRecord::query()->count())->toBe(0);
});

it('refuses to newly claim a retired mechanic', function () {
    $retired = Mechanic::factory()->named('Roll and move')->archived()->create();

    $this->actingAs($this->designer)
        ->patch($this->url, ['mechanics' => [$retired->id]])
        ->assertSessionHasErrors('mechanics');
});

/**
 * Retiring a word must not rewrite the games that had used it to describe themselves. The pivot row
 * survives, and only newly claiming the term is refused.
 */
it('keeps a mechanic a game had already claimed after it is retired', function () {
    $mechanic = Mechanic::factory()->named('Roll and move')->create();

    $record = DesignRecord::factory()->forGame($this->game)->create();
    $record->mechanics()->attach($mechanic);

    $mechanic->status = MechanicStatus::Archived;
    $mechanic->save();

    expect($record->fresh()->mechanics)->toHaveCount(1);

    $this->actingAs($this->designer)
        ->getJson($this->api)
        ->assertOk()
        ->assertJsonPath('data.mechanics.0.name', 'Roll and move');
});

it('refuses a submission with more mechanics than a design could mean', function () {
    $mechanics = Mechanic::factory()->count(31)->create();

    $this->actingAs($this->designer)
        ->patch($this->url, ['mechanics' => $mechanics->pluck('id')->all()])
        ->assertSessionHasErrors('mechanics');
});

/**
 * An archived game keeps its design readable — the record of a shelved project is the reason to
 * have kept it — and refuses anything new.
 */
it('refuses to record against an archived game', function () {
    DesignRecord::factory()->forGame($this->game)->forPlayers(2, 4)->create();

    $this->game->status = GameStatus::Archived;
    $this->game->save();

    $this->actingAs($this->designer)
        ->patch($this->url, ['player_count_min' => 3])
        ->assertForbidden();

    $this->actingAs($this->designer)
        ->getJson($this->api)
        ->assertOk();

    expect(DesignRecord::query()->sole()->player_count_min)->toBe(2);
});

it('hides another studio\'s design entirely', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->getJson($this->api)
        ->assertNotFound();

    $this->actingAs($outsider)
        ->patch($this->url, ['player_count_min' => 2])
        ->assertNotFound();
});

it('turns away a caller with no session at all', function () {
    $this->getJson($this->api)->assertUnauthorized();
});

/**
 * The raw numbers are for form boxes and the labels are for headings. The label is the server's, so
 * two screens cannot disagree about whether ninety minutes is "90 min" or "1 h 30 min".
 */
it('renders a label for each range', function () {
    DesignRecord::factory()->forGame($this->game)->forPlayers(2, 4)->lasting(90)->create();

    $this->actingAs($this->designer)
        ->getJson($this->api)
        ->assertOk()
        ->assertJsonPath('data.player_count_label', '2 to 4 players')
        ->assertJsonPath('data.play_time_label', '1 h 30 min')
        ->assertJsonPath('data.is_empty', false);
});

/**
 * Minutes are stored and hours are rendered, in one place, so a screen showing a playing time and a
 * screen showing a range of them cannot disagree.
 */
it('renders a playing time the way people say it', function (int $minutes, string $expected) {
    DesignRecord::factory()->forGame($this->game)->lasting($minutes)->create();

    $this->actingAs($this->designer)
        ->getJson($this->api)
        ->assertOk()
        ->assertJsonPath('data.play_time_label', $expected);
})->with([
    'under an hour' => [45, '45 min'],
    'exactly an hour' => [60, '1 h'],
    'and a half' => [90, '1 h 30 min'],
    'a long evening' => [180, '3 h'],
]);

it('sends the design and the vocabulary to the settings screen', function () {
    Mechanic::factory()->named('Worker placement')->create();
    Mechanic::factory()->named('Roll and move')->archived()->create();

    DesignRecord::factory()->forGame($this->game)->decided()->create();

    $this->actingAs($this->designer)
        ->get(route('games.settings.edit', ['studio', 'bears-and-bridges']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('games/settings')
                ->where('design_record.data.player_count_label', '2 to 4 players')
                /*
                 * The picker is offered published terms only. A retired word must
                 * not be offered, even though a game that already claimed it keeps it.
                 */
                ->has('mechanics.data', 1)
                ->has('options.complexities', 5),
        );
});

it('sends no design record to the settings screen when nothing is decided', function () {
    $this->actingAs($this->designer)
        ->get(route('games.settings.edit', ['studio', 'bears-and-bridges']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('design_record', null));
});

/**
 * There is no POST for the design record — the first PATCH creates it, because the design exists as
 * soon as anything about it is known. The status says which of the two happened, so a client can
 * tell "you started this" from "you changed it" without asking first.
 */
it('records the design over json, answering 201 the first time', function () {
    $this->actingAs($this->designer)
        ->patchJson($this->api, ['player_count_min' => 2, 'player_count_max' => 4])
        ->assertCreated()
        ->assertJsonPath('data.player_count_label', '2 to 4 players');

    $this->actingAs($this->designer)
        ->patchJson($this->api, ['player_count_min' => 3, 'player_count_max' => 5])
        ->assertOk()
        ->assertJsonPath('data.player_count_label', '3 to 5 players');
});
