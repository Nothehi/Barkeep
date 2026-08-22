<?php

namespace Database\Seeders;

use Illuminate\Support\Arr;
use Modules\GameDesign\Domain\Enums\Complexity;
use Modules\GameDesign\Domain\Enums\DesignPhase;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Models\DesignRecord;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameDesign\Domain\Models\Mechanic;

/**
 * The studios' projects, their design versions and what each game actually is.
 *
 * Six games rather than one, chosen to cover the shelf a real studio has: a
 * flagship in playtesting, a young design still finding its loop, one parked,
 * one abandoned, and two belonging to the studio next door. Every combination
 * of `GameStatus` and `DesignPhase` here is one a designer would recognise —
 * on hold at concept, archived at prototyping — which is the point of the two
 * being independent.
 *
 * Version numbers are allocated in order and never reused. A version is the
 * answer to "what was on the table when this was played", so the playtests,
 * prototypes and balance profiles in the later seeders all point at one.
 */
class SampleGameSeeder extends SampleSeeder
{
    /**
     * Seed the games, their versions and their design records.
     */
    public function run(): void
    {
        $count = 0;

        foreach ($this->games() as $definition) {
            $game = $this->gameRecord($definition);

            foreach ($definition['versions'] as $index => $version) {
                $this->versionRecord($game, $index + 1, $version);
            }

            $this->designRecord($game, $definition['design'] ?? null);

            $count++;
        }

        $this->command->info("Seeded {$count} sample games with their versions and design records.");
    }

    /**
     * Write one game, keyed by its address within its workspace.
     *
     * @param  array<string, mixed>  $definition
     */
    private function gameRecord(array $definition): Game
    {
        $workspace = $this->workspace($definition['workspace']);

        $game = Game::query()->firstOrNew([
            'workspace_id' => $workspace->getKey(),
            'slug' => $definition['slug'],
        ]);

        $game->fill([
            'name' => $definition['name'],
            'description' => $definition['description'],
        ]);

        $game->workspace_id = $workspace->getKey();
        $game->slug = $definition['slug'];
        $game->status = $definition['status'];
        $game->design_phase = $definition['phase'];
        $game->created_by = $this->user($definition['created_by'])->id;

        $this->stamp(
            $game,
            $this->daysAgo($definition['started']),
            $this->daysAgo($definition['touched']),
        );

        $game->save();

        return $game;
    }

    /**
     * Write one design version.
     *
     * @param  array{name: string, description: string, by: string, cut: int}  $definition
     */
    private function versionRecord(Game $game, int $number, array $definition): GameVersion
    {
        $version = GameVersion::query()->firstOrNew([
            'game_id' => $game->getKey(),
            'version_number' => $number,
        ]);

        $version->fill([
            'name' => $definition['name'],
            'description' => $definition['description'],
        ]);

        $version->game_id = $game->getKey();
        $version->version_number = $number;
        $version->created_by = $this->user($definition['by'])->id;

        $this->stamp($version, $this->daysAgo($definition['cut'], 16));
        $version->save();

        return $version;
    }

    /**
     * Write a game's design record and the vocabulary it claims.
     *
     * A record is written even for the games that barely have one, because a
     * half-filled record is the normal state of a design and the screen that
     * shows it has to be honest about the gaps.
     *
     * @param  array<string, mixed>|null  $facts
     */
    private function designRecord(Game $game, ?array $facts): void
    {
        if ($facts === null) {
            return;
        }

        $record = DesignRecord::query()->firstOrNew(['game_id' => $game->getKey()]);

        $record->game_id = $game->getKey();
        $record->fill(Arr::only($facts, [
            'pitch',
            'player_count_min',
            'player_count_max',
            'play_time_min',
            'play_time_max',
            'target_age_min',
            'audience',
            'core_action',
            'core_cost',
            'core_reward',
            'win_condition',
            'failure_condition',
        ]));

        /** Held out of the fill because it is the one fact that is an enum. */
        $record->complexity = $facts['complexity'] ?? null;

        $this->stamp($record, $game->created_at, $game->updated_at);
        $record->save();

        $mechanics = Mechanic::query()
            ->whereIn('slug', $facts['mechanics'] ?? [])
            ->pluck('id');

        $record->mechanics()->sync($mechanics);
    }

    /**
     * The shelf.
     *
     * @return list<array<string, mixed>>
     */
    protected function games(): array
    {
        return [
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'slug' => 'harbourmaster',
                'name' => 'Harbourmaster',
                'description' => 'A mid-weight worker placement game about running a tidal port. The studio\'s '
                    .'main project and the furthest along.',
                'status' => GameStatus::Active,
                'phase' => DesignPhase::Playtesting,
                'created_by' => 'mara@lanternandanvil.test',
                'started' => 240,
                'touched' => 2,
                'versions' => [
                    ['name' => 'First playable', 'by' => 'mara@lanternandanvil.test', 'cut' => 226, 'description' => 'Three crews, six berths and a tide that advances one step a round. Contracts are a shared row of four cards.'],
                    ['name' => 'Tide board', 'by' => 'devin@lanternandanvil.test', 'cut' => 128, 'description' => 'The tide moves on its own board where everybody can see it, and berths open and close with it rather than on a rules reminder.'],
                    ['name' => 'Contract rework', 'by' => 'mara@lanternandanvil.test', 'cut' => 41, 'description' => 'Contracts are drawn from three visible piles instead of one shared row, so taking the contract you want is also taking the one somebody else was building towards.'],
                ],
                'design' => [
                    'pitch' => 'A game about running a crowded tidal port, where players place harbour crews to '
                        .'unload ships before the water drops and strands them.',
                    'player_count_min' => 2,
                    'player_count_max' => 4,
                    'play_time_min' => 45,
                    'play_time_max' => 75,
                    'target_age_min' => 12,
                    'complexity' => Complexity::Hobby,
                    'audience' => 'Players who already own a worker placement game and want one that is tense '
                        .'about timing rather than about having enough workers.',
                    'core_action' => 'Send one of your three crews to a berth, a crane or the tide board, and '
                        .'take the action that space offers.',
                    'core_cost' => 'A crew that is placed is gone until the tide turns, so every action costs '
                        .'you a third of what you can do this cycle.',
                    'core_reward' => 'Cargo you unload becomes crates, and crates fill contracts — which are '
                        .'most of the score and the only way to buy more crews.',
                    'win_condition' => 'The highest score once the tide board finishes its fourth cycle.',
                    'failure_condition' => 'A ship still loaded when the tide drops leaves the port, and the '
                        .'contract it was carrying is discarded face up where everyone can see what you lost.',
                    'mechanics' => [
                        'worker-placement',
                        'contracts',
                        'set-collection',
                        'turn-order-variation',
                        'victory-points',
                        'end-game-bonuses',
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'slug' => 'kiln',
                'name' => 'Kiln',
                'description' => 'A two-player game about firing pottery, where the interesting decision is when '
                    .'to open the kiln rather than what to put in it.',
                'status' => GameStatus::Active,
                'phase' => DesignPhase::CoreDesign,
                'created_by' => 'devin@lanternandanvil.test',
                'started' => 78,
                'touched' => 6,
                'versions' => [
                    ['name' => 'Opening shape', 'by' => 'devin@lanternandanvil.test', 'cut' => 74, 'description' => 'One kiln, four slots, and a firing that resolves whenever a player says so.'],
                    ['name' => 'Two-kiln variant', 'by' => 'devin@lanternandanvil.test', 'cut' => 27, 'description' => 'Each player has their own kiln and can look into the other\'s, which turns the timing decision into a read on the opponent.'],
                ],
                'design' => [
                    'pitch' => 'A game about firing pottery where players load a shared kiln and decide together, '
                        .'without agreeing, when it is opened.',
                    'player_count_min' => 2,
                    'player_count_max' => 2,
                    'play_time_min' => 30,
                    'play_time_max' => 40,
                    'target_age_min' => 10,
                    'complexity' => Complexity::Gateway,
                    'audience' => 'Two people with half an hour who want a short game with one hard decision in it.',
                    'core_action' => 'Load a piece into a kiln slot, or call the firing.',
                    'core_cost' => 'Clay is limited and a piece loaded cannot be taken back out.',
                    'core_reward' => 'A piece that survives its firing scores; one fired too long cracks.',

                    /*
                     * Deliberately blank. Kiln is at core design and has not
                     * settled how the game ends, which is exactly what the
                     * framework's core loop checklist is about to tell Devin.
                     */
                    'win_condition' => null,
                    'failure_condition' => null,
                    'mechanics' => [
                        'push-your-luck',
                        'simultaneous-action',
                        'hidden-information',
                        'set-collection',
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'slug' => 'signal-fire',
                'name' => 'Signal Fire',
                'description' => 'A cooperative game about relaying a warning across a mountain range. Parked '
                    .'until Harbourmaster is finished.',
                'status' => GameStatus::OnHold,
                'phase' => DesignPhase::Concept,
                'created_by' => 'priya@lanternandanvil.test',
                'started' => 168,
                'touched' => 74,
                'versions' => [
                    ['name' => 'Concept sketch', 'by' => 'priya@lanternandanvil.test', 'cut' => 166, 'description' => 'The map, the beacons and the weather deck. No turn structure yet.'],
                ],
                'design' => [
                    'pitch' => 'A cooperative game about lighting beacons across a mountain range before the '
                        .'weather closes in.',
                    'player_count_min' => 1,
                    'player_count_max' => 4,
                    'play_time_min' => 40,
                    'play_time_max' => 60,
                    'target_age_min' => 10,
                    'complexity' => Complexity::Family,
                    'audience' => 'Families who play cooperative games together and want one that a single '
                        .'player can also set up alone.',
                    'core_action' => null,
                    'core_cost' => null,
                    'core_reward' => null,
                    'win_condition' => null,
                    'failure_condition' => null,
                    'mechanics' => [
                        'cooperative-play',
                        'point-to-point-movement',
                        'hand-management',
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'slug' => 'tidewrack',
                'name' => 'Tidewrack',
                'description' => 'A salvage game abandoned at prototyping. Kept because Harbourmaster\'s tide '
                    .'board came out of it.',
                'status' => GameStatus::Archived,
                'phase' => DesignPhase::Prototyping,
                'created_by' => 'mara@lanternandanvil.test',
                'started' => 398,
                'touched' => 292,
                'versions' => [
                    ['name' => 'First playable', 'by' => 'mara@lanternandanvil.test', 'cut' => 392, 'description' => 'Divers, a wreck grid and an air track that empties whether you use it or not.'],
                    ['name' => 'Salvage rework', 'by' => 'ilse@lanternandanvil.test', 'cut' => 318, 'description' => 'Salvage is scored by set rather than by weight, in the hope that it gives divers a reason to go back down.'],
                ],
                'design' => [
                    'pitch' => 'A game about salvaging a wreck before the tide covers it, where the air you '
                        .'breathe is the clock.',
                    'player_count_min' => 2,
                    'player_count_max' => 5,
                    'play_time_min' => 60,
                    'play_time_max' => 90,
                    'target_age_min' => 14,
                    'complexity' => Complexity::Hobby,
                    'audience' => 'Players who like push-your-luck games and do not mind losing a diver.',
                    'core_action' => 'Send a diver one space deeper, or bring them up with what they are carrying.',
                    'core_cost' => 'Every space deeper costs air from a track that never refills mid-dive.',
                    'core_reward' => 'Salvage brought to the surface scores; salvage still held when the air '
                        .'runs out is lost with the diver.',
                    'win_condition' => 'Most salvage points once the wreck is stripped or every diver is out.',
                    'failure_condition' => 'A diver whose air track empties before surfacing is removed along '
                        .'with everything they carried.',
                    'mechanics' => [
                        'push-your-luck',
                        'point-to-point-movement',
                        'set-collection',
                        'dice-rolling',
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::NIGHTSHIFT,
                'slug' => 'ember-court',
                'name' => 'Ember Court',
                'description' => 'A trick-taking game with a bidding round, close to finished. Rulebook is '
                    .'written and blind tests are running.',
                'status' => GameStatus::Active,
                'phase' => DesignPhase::Development,
                'created_by' => 'yusuf@nightshiftgames.test',
                'started' => 276,
                'touched' => 4,
                'versions' => [
                    ['name' => 'Staff room draft', 'by' => 'yusuf@nightshiftgames.test', 'cut' => 271, 'description' => 'Four suits, a bid before the hand, and the ember passing to whoever takes the last trick.'],
                    ['name' => 'Favour rework', 'by' => 'yusuf@nightshiftgames.test', 'cut' => 158, 'description' => 'Favour is spent to change a bid rather than earned by winning tricks, which stops the leader running away with it.'],
                    ['name' => 'Rulebook edition', 'by' => 'yusuf@nightshiftgames.test', 'cut' => 63, 'description' => 'The version the written rulebook describes. Nothing changes from here without a new rulebook.'],
                ],
                'design' => [
                    'pitch' => 'A trick-taking game about a court where the losing bidder chooses the next suit, '
                        .'so being wrong is worth something.',
                    'player_count_min' => 3,
                    'player_count_max' => 5,
                    'play_time_min' => 25,
                    'play_time_max' => 40,
                    'target_age_min' => 12,
                    'complexity' => Complexity::Family,
                    'audience' => 'People who already play trick-taking games and want one that is different '
                        .'each hand without needing a new deck.',
                    'core_action' => 'Bid how many tricks you will take, then play a card to the trick.',
                    'core_cost' => 'A bid is public and cannot be lowered; favour spent to change it is favour '
                        .'you do not score.',
                    'core_reward' => 'Meeting your bid exactly scores, and the ember passes to whoever missed '
                        .'theirs by most.',
                    'win_condition' => 'First player to fourteen points at the end of a hand.',
                    'failure_condition' => 'Holding the ember at the end of a hand costs a point, and it has to '
                        .'go to somebody.',
                    'mechanics' => [
                        'trick-taking',
                        'auction',
                        'hand-management',
                        'catch-up-mechanism',
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::NIGHTSHIFT,
                'slug' => 'paper-lanterns',
                'name' => 'Paper Lanterns',
                'description' => 'An idea from last week. One sentence and nothing else.',
                'status' => GameStatus::Draft,
                'phase' => DesignPhase::Idea,
                'created_by' => 'yusuf@nightshiftgames.test',
                'started' => 17,
                'touched' => 17,
                'versions' => [
                    ['name' => null, 'by' => 'yusuf@nightshiftgames.test', 'cut' => 17, 'description' => null],
                ],
                'design' => [
                    'pitch' => 'A game about a lantern festival where every lantern a player releases is a '
                        .'promise about what they will do next round.',
                    'mechanics' => [],
                ],
            ],
        ];
    }
}
