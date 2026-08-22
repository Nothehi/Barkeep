<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Modules\Playtesting\Domain\Enums\ObservationCategory;
use Modules\Playtesting\Domain\Enums\PlaytestParticipantRole;
use Modules\Playtesting\Domain\Enums\PlaytestSessionStatus;
use Modules\Playtesting\Domain\Enums\PlaytestStatus;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestParticipant;
use Modules\Playtesting\Domain\Models\PlaytestSession;

/**
 * The sessions the designs were actually put in front of people at.
 *
 * Every playtest here has a question in its objective and, once it is finished,
 * an answer in its conclusion — which is the methodology's own first principle
 * about playtesting, and the reason a seeded playtest with a vague title would
 * be teaching the wrong thing.
 *
 * The sessions carry a mix of registered accounts and guests. A studio's
 * playtesters are mostly not users of the studio's tools, so a participant list
 * where everybody has a login is not what a real one looks like — and the
 * nullable `user_id` on the table exists precisely for the people who turned up
 * to play rather than to use Barkeep.
 */
class SamplePlaytestSeeder extends SampleSeeder
{
    /**
     * Seed the playtests, their sessions and everything recorded at them.
     */
    public function run(): void
    {
        $playtests = 0;
        $sessions = 0;

        foreach ($this->playtests() as $definition) {
            $playtest = $this->playtestRecord($definition);
            $playtests++;

            foreach ($definition['sessions'] as $index => $session) {
                $this->sessionRecord($playtest, $index + 1, $session);
                $sessions++;
            }
        }

        $this->command->info("Seeded {$playtests} sample playtests across {$sessions} sessions.");
    }

    /**
     * Write one playtest, keyed by its title within its game.
     *
     * @param  array<string, mixed>  $definition
     */
    private function playtestRecord(array $definition): Playtest
    {
        $game = $this->game($definition['workspace'], $definition['game']);

        $playtest = Playtest::query()->firstOrNew([
            'game_id' => $game->getKey(),
            'title' => $definition['title'],
        ]);

        $playtest->fill([
            'title' => $definition['title'],
            'objective' => $definition['objective'],
            'hypothesis' => $definition['hypothesis'] ?? null,
            'conclusion' => $definition['conclusion'] ?? null,
        ]);

        $playtest->game_id = $game->getKey();
        $playtest->game_version_id = $this->version($game, $definition['version'])->getKey();
        $playtest->status = $definition['status'];
        $playtest->created_by = $this->user($definition['by'])->id;

        $playtest->planned_at = $this->when($definition['planned']);
        $playtest->completed_at = isset($definition['completed'])
            ? $this->when($definition['completed'], 21)
            : null;

        $this->stamp(
            $playtest,
            $this->when($definition['planned'])->subDays(3),
            $playtest->completed_at ?? $this->when($definition['planned']),
        );

        $playtest->save();

        return $playtest;
    }

    /**
     * Write one session and everything recorded at it.
     *
     * @param  array<string, mixed>  $definition
     */
    private function sessionRecord(Playtest $playtest, int $ordinal, array $definition): void
    {
        /*
         * Sessions have no natural address of their own — two sittings of the
         * same playtest differ only by when they happened — so the ordinal
         * within the playtest is what re-seeding matches on.
         */
        $session = PlaytestSession::query()
            ->where('playtest_id', $playtest->getKey())
            ->orderBy('created_at')
            ->skip($ordinal - 1)
            ->take(1)
            ->first() ?? new PlaytestSession;

        $held = $this->when($definition['held']);

        $session->fill([
            'location' => $definition['location'],
            'notes' => $definition['notes'] ?? null,
            'outcome' => $definition['outcome'] ?? null,
        ]);

        $session->playtest_id = $playtest->getKey();
        $session->status = $definition['status'];
        $session->planned_at = $held;
        $session->started_at = isset($definition['ran']) ? $held : null;
        $session->ended_at = isset($definition['ran']) ? $held->addMinutes($definition['ran']) : null;
        $session->created_by = $this->user($definition['by'])->id;

        $this->stamp($session, $held->subDays(2), $session->ended_at ?? $held);
        $session->save();

        $participants = [];

        foreach ($definition['participants'] as $participant) {
            $participants[$participant['name']] = $this->participant($session, $participant, $held);
        }

        foreach ($definition['observations'] ?? [] as $offset => $observation) {
            $this->observation($session, $participants, $observation, $held, $offset);
        }

        foreach ($definition['feedback'] ?? [] as $offset => $feedback) {
            $this->feedback($session, $participants, $feedback, $held, $offset);
        }
    }

    /**
     * Seat somebody at a session.
     *
     * @param  array{name: string, role: PlaytestParticipantRole, account?: string}  $definition
     */
    private function participant(PlaytestSession $session, array $definition, CarbonImmutable $held): PlaytestParticipant
    {
        $account = isset($definition['account']) ? $this->user($definition['account']) : null;

        $participant = PlaytestParticipant::query()
            ->where('session_id', $session->getKey())
            ->where('display_name', $definition['name'])
            ->first() ?? new PlaytestParticipant;

        $participant->fill(['display_name' => $definition['name']]);

        $participant->session_id = $session->getKey();
        $participant->user_id = $account?->id;
        $participant->role = $definition['role'];
        $participant->joined_at = $held;
        $participant->left_at = $session->ended_at;

        $this->stamp($participant, $held);
        $participant->save();

        return $participant;
    }

    /**
     * Record something somebody saw.
     *
     * @param  array<string, PlaytestParticipant>  $participants
     * @param  array{category: ObservationCategory, content: string, by: string, about?: string}  $definition
     */
    private function observation(
        PlaytestSession $session,
        array $participants,
        array $definition,
        CarbonImmutable $held,
        int $offset,
    ): void {
        $observation = PlaytestObservation::query()
            ->where('session_id', $session->getKey())
            ->where('content', $definition['content'])
            ->first() ?? new PlaytestObservation;

        $observation->fill(['content' => $definition['content']]);

        $observation->session_id = $session->getKey();
        $observation->participant_id = ($participants[$definition['about'] ?? ''] ?? null)?->getKey();
        $observation->category = $definition['category'];
        $observation->observed_at = $held->addMinutes(9 * ($offset + 1));
        $observation->created_by = $this->user($definition['by'])->id;

        $this->stamp($observation, $observation->observed_at);
        $observation->save();
    }

    /**
     * Record something somebody said afterwards.
     *
     * @param  array<string, PlaytestParticipant>  $participants
     * @param  array{content: string, rating?: int, by: string, from?: string}  $definition
     */
    private function feedback(
        PlaytestSession $session,
        array $participants,
        array $definition,
        CarbonImmutable $held,
        int $offset,
    ): void {
        $feedback = PlaytestFeedback::query()
            ->where('session_id', $session->getKey())
            ->where('content', $definition['content'])
            ->first() ?? new PlaytestFeedback;

        $feedback->fill(['content' => $definition['content']]);

        $feedback->session_id = $session->getKey();
        $feedback->participant_id = ($participants[$definition['from'] ?? ''] ?? null)?->getKey();
        $feedback->rating = $definition['rating'] ?? null;
        $feedback->created_by = $this->user($definition['by'])->id;

        $written = ($session->ended_at ?? $held)->addMinutes(6 * ($offset + 1));

        $this->stamp($feedback, $written);
        $feedback->save();
    }

    /**
     * The sessions themselves.
     *
     * `planned`, `held` and `completed` are day offsets, positive into the past
     * and negative into the future, so a seeded database always has something
     * already run and something still to come. `ran` is a session's length in
     * minutes — and where it is missing, the session did not happen.
     *
     * @return list<array<string, mixed>>
     */
    protected function playtests(): array
    {
        return [
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'harbourmaster',
                'version' => 2,
                'title' => 'Does the tide board read at a glance?',
                'objective' => 'Find out whether players can tell which berths close next without asking, '
                    .'now that the tide has its own board instead of a line on the rules card.',
                'hypothesis' => 'Putting the tide where everybody can see it will stop players asking us when '
                    .'a berth closes.',
                'conclusion' => 'They stopped asking by the second round. They also started treating the tide '
                    .'as a threat rather than a schedule — leaning over to look at it before choosing — which '
                    .'is more than we were testing for and is now the thing the game is about.',
                'status' => PlaytestStatus::Completed,
                'by' => 'mara@lanternandanvil.test',
                'planned' => 118,
                'completed' => 104,
                'sessions' => [
                    [
                        'status' => PlaytestSessionStatus::Completed,
                        'location' => 'Studio, Tuesday group',
                        'by' => 'mara@lanternandanvil.test',
                        'held' => 118,
                        'ran' => 95,
                        'notes' => 'First outing for the tide board. Berths were still index cards; the board '
                            .'was a printed strip weighted down with a mug.',
                        'outcome' => 'Nobody asked when a berth closed after the first round. Two players '
                            .'misread which direction the tide moved, which is a layout problem rather than a '
                            .'rules one.',
                        'participants' => [
                            ['name' => 'Mara Okonkwo', 'role' => PlaytestParticipantRole::Designer, 'account' => 'mara@lanternandanvil.test'],
                            ['name' => 'Devin Halloran', 'role' => PlaytestParticipantRole::Player, 'account' => 'devin@lanternandanvil.test'],
                            ['name' => 'Hannah Wu', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'Oscar Reyes', 'role' => PlaytestParticipantRole::Player],
                        ],
                        'observations' => [
                            ['category' => ObservationCategory::Ux, 'content' => 'Both new players read the tide as moving left to right. It moves right to left. They corrected themselves within a round but the arrow needs to be on the board, not in the rules.', 'by' => 'mara@lanternandanvil.test', 'about' => 'Hannah Wu'],
                            ['category' => ObservationCategory::PlayerBehavior, 'content' => 'Oscar physically leaned over the table to look at the tide board before every placement from round three onwards.', 'by' => 'mara@lanternandanvil.test', 'about' => 'Oscar Reyes'],
                            ['category' => ObservationCategory::Rules, 'content' => 'Asked what happens to a ship that is partly unloaded when the tide drops. We had not decided. Ruled on the spot that it keeps its crates and leaves.', 'by' => 'mara@lanternandanvil.test', 'about' => 'Hannah Wu'],
                            ['category' => ObservationCategory::Pacing, 'content' => 'Fourth cycle took twenty-six minutes of a ninety-five minute game and produced two scoring decisions.', 'by' => 'mara@lanternandanvil.test'],
                        ],
                        'feedback' => [
                            ['content' => 'I liked that I could see the whole shift coming. The last stretch dragged — by then I knew what I was doing and was just doing it.', 'rating' => 4, 'by' => 'mara@lanternandanvil.test', 'from' => 'Hannah Wu'],
                            ['content' => 'Losing a ship felt awful in the right way. I would play again to not do that.', 'rating' => 5, 'by' => 'mara@lanternandanvil.test', 'from' => 'Oscar Reyes'],
                            ['content' => 'The tide board works. Put an arrow on it.', 'rating' => 4, 'by' => 'devin@lanternandanvil.test', 'from' => 'Devin Halloran'],
                        ],
                    ],
                    [
                        'status' => PlaytestSessionStatus::Completed,
                        'location' => 'Bristol Games Club, back room',
                        'by' => 'priya@lanternandanvil.test',
                        'held' => 104,
                        'ran' => 88,
                        'notes' => 'Second sitting with an arrow printed on the tide board and nobody from the '
                            .'studio playing.',
                        'outcome' => 'No direction confusion at all. Length is the surviving problem: eighty-eight '
                            .'minutes at four players against a stated maximum of seventy-five.',
                        'participants' => [
                            ['name' => 'Priya Raman', 'role' => PlaytestParticipantRole::Facilitator, 'account' => 'priya@lanternandanvil.test'],
                            ['name' => 'Ade Balogun', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'Sofia Marchetti', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'Ken Tanaka', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'Jo Whitfield', 'role' => PlaytestParticipantRole::Observer],
                        ],
                        'observations' => [
                            ['category' => ObservationCategory::Ux, 'content' => 'Nobody misread the tide direction. The arrow was enough.', 'by' => 'priya@lanternandanvil.test'],
                            ['category' => ObservationCategory::Pacing, 'content' => 'Eighty-eight minutes with no rules explanation time counted. The first two cycles took thirty minutes; the last two took fifty-eight.', 'by' => 'priya@lanternandanvil.test'],
                            ['category' => ObservationCategory::Balance, 'content' => 'Sofia never used the dockhand market and finished second. Ken used it four times and finished last.', 'by' => 'priya@lanternandanvil.test', 'about' => 'Sofia Marchetti'],
                            ['category' => ObservationCategory::PlayerBehavior, 'content' => 'Two and a half minutes between Ade\'s turns in the fourth cycle. He checked his phone twice.', 'by' => 'priya@lanternandanvil.test', 'about' => 'Ade Balogun'],
                        ],
                        'feedback' => [
                            ['content' => 'Good game, twenty minutes too long. I would have been happier if it ended after the third tide.', 'rating' => 3, 'by' => 'priya@lanternandanvil.test', 'from' => 'Ade Balogun'],
                            ['content' => 'I never worked out what the dockhands were for and it did not seem to matter.', 'rating' => 4, 'by' => 'priya@lanternandanvil.test', 'from' => 'Sofia Marchetti'],
                            ['content' => 'Watching it, the fourth cycle is where the table goes quiet. Not thinking-quiet.', 'rating' => 3, 'by' => 'priya@lanternandanvil.test', 'from' => 'Jo Whitfield'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'harbourmaster',
                'version' => 3,
                'title' => 'Are three contract piles enough of a denial decision?',
                'objective' => 'Find out whether taking a contract off a pile registers as taking it away from '
                    .'somebody, now that the shared row has been replaced by three visible piles.',
                'hypothesis' => 'Players will start watching which pile their neighbours are building towards '
                    .'and take from it deliberately.',
                'conclusion' => 'They did, in both sessions, and unprompted. The cost is that the fourth cycle '
                    .'slowed down further, because a contract you want may be two piles deep.',
                'status' => PlaytestStatus::Completed,
                'by' => 'mara@lanternandanvil.test',
                'planned' => 32,
                'completed' => 26,
                'sessions' => [
                    [
                        'status' => PlaytestSessionStatus::Completed,
                        'location' => 'Studio, Tuesday group',
                        'by' => 'devin@lanternandanvil.test',
                        'held' => 32,
                        'ran' => 92,
                        'notes' => 'First session on v3. Three piles, faces up, refilled from the top of the deck.',
                        'outcome' => 'Denial happened four times in one game and twice it decided the winner. '
                            .'Length unchanged and still over.',
                        'participants' => [
                            ['name' => 'Devin Halloran', 'role' => PlaytestParticipantRole::Facilitator, 'account' => 'devin@lanternandanvil.test'],
                            ['name' => 'Tomas Lindqvist', 'role' => PlaytestParticipantRole::Player, 'account' => 'tomas@lanternandanvil.test'],
                            ['name' => 'Hannah Wu', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'Ken Tanaka', 'role' => PlaytestParticipantRole::Player],
                        ],
                        'observations' => [
                            ['category' => ObservationCategory::Gameplay, 'content' => 'Hannah took a contract she had no crates for, purely to keep it away from Ken. She said so out loud while doing it.', 'by' => 'devin@lanternandanvil.test', 'about' => 'Hannah Wu'],
                            ['category' => ObservationCategory::Pacing, 'content' => 'Turn length in the fourth cycle went up, not down. Players now read three piles instead of one row.', 'by' => 'devin@lanternandanvil.test'],
                            ['category' => ObservationCategory::Components, 'content' => 'Three piles plus the tide board no longer fit on a normal dining table alongside four player areas.', 'by' => 'tomas@lanternandanvil.test'],
                        ],
                        'feedback' => [
                            ['content' => 'The piles are much better than the row. I actually cared what the person next to me was collecting.', 'rating' => 5, 'by' => 'devin@lanternandanvil.test', 'from' => 'Hannah Wu'],
                            ['content' => 'It is still a ninety minute game that says it is a seventy-five minute game.', 'rating' => 3, 'by' => 'devin@lanternandanvil.test', 'from' => 'Ken Tanaka'],
                        ],
                    ],
                    [
                        'status' => PlaytestSessionStatus::Completed,
                        'location' => 'Online, Tabletop Simulator',
                        'by' => 'mara@lanternandanvil.test',
                        'held' => 26,
                        'ran' => 78,
                        'notes' => 'Two-player game on the digital build, to isolate the piles from the table-space '
                            .'problem.',
                        'outcome' => 'Denial reads just as clearly at two players. Seventy-eight minutes, which '
                            .'is inside the stated range but at the top of it for the shortest player count.',
                        'participants' => [
                            ['name' => 'Mara Okonkwo', 'role' => PlaytestParticipantRole::Designer, 'account' => 'mara@lanternandanvil.test'],
                            ['name' => 'Ade Balogun', 'role' => PlaytestParticipantRole::Player],
                        ],
                        'observations' => [
                            ['category' => ObservationCategory::Gameplay, 'content' => 'At two players every pile is contested every round, which makes denial constant rather than occasional. That may be too much of it.', 'by' => 'mara@lanternandanvil.test'],
                            ['category' => ObservationCategory::Balance, 'content' => 'The fourth-cycle bonus closed a nine point gap to three. The catch-up is doing its job and may be doing too much of it.', 'by' => 'mara@lanternandanvil.test'],
                        ],
                        'feedback' => [
                            ['content' => 'Two-player is tighter than I expected. I never had a turn where the choice was obvious.', 'rating' => 5, 'by' => 'mara@lanternandanvil.test', 'from' => 'Ade Balogun'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'harbourmaster',
                'version' => 3,
                'title' => 'Does the four-player game finish inside seventy-five minutes?',
                'objective' => 'Time four-player games against the stated maximum, with the fourth tide cycle '
                    .'shortened to three rounds.',
                'hypothesis' => 'Cutting the fourth cycle from five rounds to three will bring the game inside '
                    .'seventy-five minutes without touching the end-game bonuses.',
                'status' => PlaytestStatus::InProgress,
                'by' => 'priya@lanternandanvil.test',
                'planned' => 6,
                'sessions' => [
                    [
                        'status' => PlaytestSessionStatus::Completed,
                        'location' => 'Bristol Games Club, back room',
                        'by' => 'priya@lanternandanvil.test',
                        'held' => 6,
                        'ran' => 81,
                        'notes' => 'Shortened fourth cycle, four players, nobody from the studio at the table.',
                        'outcome' => 'Eighty-one minutes. Six better than last time and still over. The saved '
                            .'time came out of the part of the game people liked.',
                        'participants' => [
                            ['name' => 'Priya Raman', 'role' => PlaytestParticipantRole::Facilitator, 'account' => 'priya@lanternandanvil.test'],
                            ['name' => 'Sofia Marchetti', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'Ken Tanaka', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'Jo Whitfield', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'Oscar Reyes', 'role' => PlaytestParticipantRole::Player],
                        ],
                        'observations' => [
                            ['category' => ObservationCategory::Pacing, 'content' => 'Eighty-one minutes. The first three cycles are unchanged at fifty-four; the shortened fourth still took twenty-seven.', 'by' => 'priya@lanternandanvil.test'],
                            ['category' => ObservationCategory::Gameplay, 'content' => 'Two players reached the last round unable to complete a contract they had spent the cycle building. Both said it felt like being cut off rather than being beaten.', 'by' => 'priya@lanternandanvil.test'],
                            ['category' => ObservationCategory::Balance, 'content' => 'End-game bonuses were worth proportionally more in a shorter fourth cycle, because there were fewer rounds to earn crates in.', 'by' => 'priya@lanternandanvil.test'],
                        ],
                        'feedback' => [
                            ['content' => 'It ended one round before it should have. I had it all set up and then it was over.', 'rating' => 3, 'by' => 'priya@lanternandanvil.test', 'from' => 'Jo Whitfield'],
                            ['content' => 'Faster, but I liked the long version better and I have been complaining about the length for months.', 'rating' => 4, 'by' => 'priya@lanternandanvil.test', 'from' => 'Ken Tanaka'],
                        ],
                    ],
                    [
                        'status' => PlaytestSessionStatus::Planned,
                        'location' => 'Studio, Tuesday group',
                        'by' => 'priya@lanternandanvil.test',
                        'held' => -4,
                        'notes' => 'Repeat at four players with the fourth cycle back to five rounds and the '
                            .'third cut to three instead, to find out whether it is the length or the ending '
                            .'that people mind.',
                        'participants' => [
                            ['name' => 'Priya Raman', 'role' => PlaytestParticipantRole::Facilitator, 'account' => 'priya@lanternandanvil.test'],
                            ['name' => 'Devin Halloran', 'role' => PlaytestParticipantRole::Player, 'account' => 'devin@lanternandanvil.test'],
                            ['name' => 'Hannah Wu', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'Ade Balogun', 'role' => PlaytestParticipantRole::Player],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'harbourmaster',
                'version' => 3,
                'title' => 'Does a fifth seat work with a harbourmaster role?',
                'objective' => 'Find out whether a fifth player can be added as an asymmetric harbourmaster who '
                    .'sets the turn order instead of placing crews.',
                'hypothesis' => 'A player with no crews but control of the order will have enough to do, and '
                    .'the seat will absorb the person who would otherwise be waiting longest.',
                'status' => PlaytestStatus::Planned,
                'by' => 'mara@lanternandanvil.test',
                'planned' => -9,
                'sessions' => [
                    [
                        'status' => PlaytestSessionStatus::Planned,
                        'location' => 'Studio, Tuesday group',
                        'by' => 'mara@lanternandanvil.test',
                        'held' => -9,
                        'notes' => 'Needs a fifth player area and a harbourmaster reference card before it can '
                            .'run. Tomas is printing both.',
                        'participants' => [
                            ['name' => 'Mara Okonkwo', 'role' => PlaytestParticipantRole::Designer, 'account' => 'mara@lanternandanvil.test'],
                            ['name' => 'Tomas Lindqvist', 'role' => PlaytestParticipantRole::Player, 'account' => 'tomas@lanternandanvil.test'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'harbourmaster',
                'version' => 1,
                'title' => 'Do the three crews feel different from one another?',
                'objective' => 'Find out whether giving each crew a small special ability makes the placement '
                    .'decision richer or merely longer.',
                'hypothesis' => 'Distinct crews will make the order you spend them matter as much as where.',
                'conclusion' => 'Cancelled before it ran. The tide board work made the question moot: crews '
                    .'became distinguishable by when they are free rather than by what they do.',
                'status' => PlaytestStatus::Cancelled,
                'by' => 'mara@lanternandanvil.test',
                'planned' => 206,
                'sessions' => [
                    [
                        'status' => PlaytestSessionStatus::Cancelled,
                        'location' => 'Studio, Tuesday group',
                        'by' => 'mara@lanternandanvil.test',
                        'held' => 206,
                        'notes' => 'Called off two days before. The special abilities were cut from the design '
                            .'in the meantime, so there was nothing left to test.',
                        'participants' => [
                            ['name' => 'Mara Okonkwo', 'role' => PlaytestParticipantRole::Designer, 'account' => 'mara@lanternandanvil.test'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'kiln',
                'version' => 2,
                'title' => 'Is the firing call still interesting when both kilns are visible?',
                'objective' => 'Find out whether being able to see the opponent\'s kiln turns the timing '
                    .'decision into a read, or into arithmetic.',
                'hypothesis' => 'Visible kilns will make the decision a read on the opponent rather than a '
                    .'calculation about the deck.',
                'status' => PlaytestStatus::Planned,
                'by' => 'devin@lanternandanvil.test',
                'planned' => -3,
                'sessions' => [
                    [
                        'status' => PlaytestSessionStatus::Planned,
                        'location' => 'Studio, lunchtime',
                        'by' => 'devin@lanternandanvil.test',
                        'held' => -3,
                        'notes' => 'Two-player, four games back to back, alternating who opens.',
                        'participants' => [
                            ['name' => 'Devin Halloran', 'role' => PlaytestParticipantRole::Designer, 'account' => 'devin@lanternandanvil.test'],
                            ['name' => 'Mara Okonkwo', 'role' => PlaytestParticipantRole::Player, 'account' => 'mara@lanternandanvil.test'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'tidewrack',
                'version' => 2,
                'title' => 'Can a second dive ever be worth taking?',
                'objective' => 'Find out whether scoring salvage by set gives players a reason to go back down '
                    .'after a successful first dive.',
                'hypothesis' => 'Set scoring will make an incomplete set worth the risk of a second dive.',
                'conclusion' => 'It did not. Every player who surfaced with salvage stopped, and every player '
                    .'who dived again lost the diver. The set rules added a rule and changed no decisions, '
                    .'which is the answer that shelved the game.',
                'status' => PlaytestStatus::Completed,
                'by' => 'mara@lanternandanvil.test',
                'planned' => 312,
                'completed' => 300,
                'sessions' => [
                    [
                        'status' => PlaytestSessionStatus::Completed,
                        'location' => 'Studio, Tuesday group',
                        'by' => 'ilse@lanternandanvil.test',
                        'held' => 312,
                        'ran' => 104,
                        'notes' => 'Five players, set scoring in for the first time.',
                        'outcome' => 'Three of five players never took a second dive. The two who did both '
                            .'drowned and then watched for thirty minutes.',
                        'participants' => [
                            ['name' => 'Ilse Vermeer', 'role' => PlaytestParticipantRole::Designer, 'account' => 'ilse@lanternandanvil.test'],
                            ['name' => 'Mara Okonkwo', 'role' => PlaytestParticipantRole::Player, 'account' => 'mara@lanternandanvil.test'],
                            ['name' => 'Oscar Reyes', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'Ken Tanaka', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'Sofia Marchetti', 'role' => PlaytestParticipantRole::Player],
                        ],
                        'observations' => [
                            ['category' => ObservationCategory::Gameplay, 'content' => 'Nobody described the second dive as a decision. Three players said outright that surfacing with anything was obviously correct.', 'by' => 'ilse@lanternandanvil.test'],
                            ['category' => ObservationCategory::Pacing, 'content' => 'Oscar lost his diver twenty-two minutes in and had nothing to do for the remaining eighty-two.', 'by' => 'ilse@lanternandanvil.test', 'about' => 'Oscar Reyes'],
                            ['category' => ObservationCategory::PlayerBehavior, 'content' => 'Five players, five separate games. Nobody looked at anybody else\'s board all evening.', 'by' => 'mara@lanternandanvil.test'],
                        ],
                        'feedback' => [
                            ['content' => 'The first dive is genuinely tense. Then it is over and there are two more of them.', 'rating' => 2, 'by' => 'ilse@lanternandanvil.test', 'from' => 'Ken Tanaka'],
                            ['content' => 'I was out after twenty minutes. I would rather have been eliminated properly than sit there.', 'rating' => 1, 'by' => 'ilse@lanternandanvil.test', 'from' => 'Oscar Reyes'],
                        ],
                    ],
                    [
                        'status' => PlaytestSessionStatus::Completed,
                        'location' => 'Studio, three players',
                        'by' => 'mara@lanternandanvil.test',
                        'held' => 300,
                        'ran' => 71,
                        'notes' => 'Repeated at three players to see whether the downtime was the whole problem.',
                        'outcome' => 'Downtime was better and the second dive was still never worth taking. '
                            .'That settled it.',
                        'participants' => [
                            ['name' => 'Mara Okonkwo', 'role' => PlaytestParticipantRole::Designer, 'account' => 'mara@lanternandanvil.test'],
                            ['name' => 'Ilse Vermeer', 'role' => PlaytestParticipantRole::Player, 'account' => 'ilse@lanternandanvil.test'],
                            ['name' => 'Ade Balogun', 'role' => PlaytestParticipantRole::Player],
                        ],
                        'observations' => [
                            ['category' => ObservationCategory::Gameplay, 'content' => 'Same result with less waiting. The problem is the loop, not the player count.', 'by' => 'mara@lanternandanvil.test'],
                            ['category' => ObservationCategory::Balance, 'content' => 'The air track is the only real system. Everything else is scoring attached to it.', 'by' => 'mara@lanternandanvil.test'],
                        ],
                        'feedback' => [
                            ['content' => 'I would play the first ten minutes of this again happily. I am not sure what the rest is for.', 'rating' => 2, 'by' => 'mara@lanternandanvil.test', 'from' => 'Ade Balogun'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::NIGHTSHIFT,
                'game' => 'ember-court',
                'version' => 3,
                'title' => 'Blind test: can four strangers learn it from the rulebook alone?',
                'objective' => 'Hand the rulebook and the deck to a group with no explanation and record every '
                    .'question they ask.',
                'hypothesis' => 'The rulebook is complete enough that the only questions will be about edge '
                    .'cases rather than about the basic turn.',
                'conclusion' => 'One question, asked twice: whether favour carries between hands. It is in the '
                    .'rulebook, three sections after the point where players need it. The fix is an edit, not '
                    .'a rule.',
                'status' => PlaytestStatus::Completed,
                'by' => 'yusuf@nightshiftgames.test',
                'planned' => 21,
                'completed' => 19,
                'sessions' => [
                    [
                        'status' => PlaytestSessionStatus::Completed,
                        'location' => 'Ada\'s flat, designer not present',
                        'by' => 'yusuf@nightshiftgames.test',
                        'held' => 21,
                        'ran' => 64,
                        'notes' => 'Two full games back to back. Written up from the group\'s notes and a '
                            .'twenty-minute conversation afterwards.',
                        'outcome' => 'They taught themselves, played twice, and asked to keep the deck. Both '
                            .'games inside thirty-six minutes.',
                        'participants' => [
                            ['name' => 'Ada Nwosu', 'role' => PlaytestParticipantRole::Facilitator],
                            ['name' => 'Ruth Kelleher', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'Marek Nowak', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'Sofia Marchetti', 'role' => PlaytestParticipantRole::Player],
                        ],
                        'observations' => [
                            ['category' => ObservationCategory::Rules, 'content' => 'Asked whether favour carries between hands, in both games. The answer is in the rulebook under scoring, which is three sections after where they needed it.', 'by' => 'yusuf@nightshiftgames.test', 'about' => 'Ruth Kelleher'],
                            ['category' => ObservationCategory::Gameplay, 'content' => 'By the fourth hand of the first game somebody bid deliberately low to hand the ember on. Nobody explained that play to them.', 'by' => 'yusuf@nightshiftgames.test', 'about' => 'Marek Nowak'],
                            ['category' => ObservationCategory::Pacing, 'content' => 'Thirty-four and thirty-six minutes. Setup from opening the box was under three.', 'by' => 'yusuf@nightshiftgames.test'],
                        ],
                        'feedback' => [
                            ['content' => 'We played it twice without meaning to. Can we keep this?', 'rating' => 5, 'by' => 'yusuf@nightshiftgames.test', 'from' => 'Ada Nwosu'],
                            ['content' => 'Being the one who missed the bid is somehow the best seat. I did not expect that.', 'rating' => 5, 'by' => 'yusuf@nightshiftgames.test', 'from' => 'Marek Nowak'],
                            ['content' => 'The favour rules are in the wrong order in the book. Everything else was clear.', 'rating' => 4, 'by' => 'yusuf@nightshiftgames.test', 'from' => 'Ruth Kelleher'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Read a signed day offset: negative is still ahead of us.
     */
    private function when(int $days, int $hour = 19): CarbonImmutable
    {
        return $days >= 0 ? $this->daysAgo($days, $hour) : $this->daysAhead(-$days, $hour);
    }
}
