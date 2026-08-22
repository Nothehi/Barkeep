<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\PrototypeIteration\Domain\Enums\DecisionStatus;
use Modules\PrototypeIteration\Domain\Enums\DesignChangeCategory;
use Modules\PrototypeIteration\Domain\Enums\EvidenceType;
use Modules\PrototypeIteration\Domain\Enums\ExperimentStatus;
use Modules\PrototypeIteration\Domain\Enums\IterationOutcome;
use Modules\PrototypeIteration\Domain\Enums\IterationStatus;
use Modules\PrototypeIteration\Domain\Enums\PrototypeArtifactType;
use Modules\PrototypeIteration\Domain\Enums\PrototypeStatus;
use Modules\PrototypeIteration\Domain\Enums\PrototypeType;
use Modules\PrototypeIteration\Domain\Models\DecisionEvidence;
use Modules\PrototypeIteration\Domain\Models\DesignChange;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\IterationPlaytest;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeArtifact;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;

/**
 * What was actually on the table, and what changed between one sitting and the
 * next.
 *
 * The two halves of this module are seeded together because they only mean
 * anything together: a prototype version is the answer to "what was played",
 * and an iteration is the record of somebody deciding to change it and finding
 * out what that did. Every iteration here therefore points at both a design
 * version and a prototype version, cites the playtest that produced its
 * evidence, and ends with a decision somebody can disagree with.
 *
 * Outcomes are mixed on purpose. Harbourmaster's tide work succeeded, its
 * contract rework partly succeeded, its length work is still running, and
 * Tidewrack's last iteration failed — which is what shelved the game. An
 * iteration log where everything succeeded is not a log of design work.
 *
 * Evidence is stored as a type and a bare identifier, never as a copy: the
 * counts and excerpts shown against an attached playtest are read live, so they
 * always agree with the playtest's own screen.
 */
class SamplePrototypeSeeder extends SampleSeeder
{
    /**
     * Seed the prototypes, their versions and the iterations built on them.
     */
    public function run(): void
    {
        $prototypes = 0;
        $iterations = 0;

        foreach ($this->prototypes() as $definition) {
            $this->prototypeRecord($definition);
            $prototypes++;
        }

        foreach ($this->iterations() as $definition) {
            $this->iterationRecord($definition);
            $iterations++;
        }

        $this->command->info("Seeded {$prototypes} sample prototypes and {$iterations} iterations.");
    }

    /**
     * What each game was played on.
     *
     * A prototype belongs to the design version it was built from, and its own
     * versions are numbered separately — the paper port went through two
     * versions while the design was still v1, which is the normal relationship
     * between the two and the reason they are counted apart.
     *
     * @return list<array<string, mixed>>
     */
    protected function prototypes(): array
    {
        return [
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'harbourmaster',
                'version' => 1,
                'name' => 'Paper port',
                'description' => 'Index cards for berths, wooden cubes for crates, and the tide written on a '
                    .'strip of card that somebody had to remember to move.',
                'type' => PrototypeType::Paper,
                'status' => PrototypeStatus::Archived,
                'by' => 'mara@lanternandanvil.test',
                'built' => 224,
                'versions' => [
                    [
                        'name' => 'First table',
                        'description' => 'Six berths, three crews, tide tracked on a card by whoever remembered.',
                        'by' => 'mara@lanternandanvil.test',
                        'cut' => 224,
                        'artifacts' => [
                            ['name' => 'Berth cards, first cut', 'type' => PrototypeArtifactType::Pdf, 'reference' => 'samples/harbourmaster/paper-port/berth-cards-v1.pdf', 'filed' => 224, 'metadata' => ['pages' => 2, 'cards' => 6]],
                        ],
                    ],
                    [
                        'name' => 'Wider berths',
                        'description' => 'Berth cards doubled in width so four crates fit without stacking, '
                            .'after two sessions of players stacking crates and losing count.',
                        'by' => 'tomas@lanternandanvil.test',
                        'cut' => 181,
                        'artifacts' => [
                            ['name' => 'Berth cards, wide', 'type' => PrototypeArtifactType::Pdf, 'reference' => 'samples/harbourmaster/paper-port/berth-cards-v2.pdf', 'filed' => 181, 'metadata' => ['pages' => 2, 'cards' => 6]],
                            ['name' => 'Table photo, four players', 'type' => PrototypeArtifactType::Image, 'reference' => 'samples/harbourmaster/paper-port/table-four-players.jpg', 'filed' => 176, 'metadata' => ['width' => 3024, 'height' => 4032]],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'harbourmaster',
                'version' => 2,
                'name' => 'Tide board mock-up',
                'description' => 'The tide on its own printed strip in the middle of the table, with berths '
                    .'that open and close against it.',
                'type' => PrototypeType::Paper,
                'status' => PrototypeStatus::Active,
                'by' => 'devin@lanternandanvil.test',
                'built' => 133,
                'versions' => [
                    [
                        'name' => 'Printed strip',
                        'description' => 'Tide board with no direction marked, because we did not think anybody '
                            .'would need it marked.',
                        'by' => 'devin@lanternandanvil.test',
                        'cut' => 133,
                        'artifacts' => [
                            ['name' => 'Tide board, first print', 'type' => PrototypeArtifactType::Pdf, 'reference' => 'samples/harbourmaster/tide-board/tide-board-v1.pdf', 'filed' => 133, 'metadata' => ['pages' => 1, 'size' => 'A3']],
                        ],
                    ],
                    [
                        'name' => 'With direction arrow',
                        'description' => 'Same board with an arrow on it. Two players had read the tide '
                            .'backwards without one.',
                        'by' => 'tomas@lanternandanvil.test',
                        'cut' => 110,
                        'artifacts' => [
                            ['name' => 'Tide board, arrowed', 'type' => PrototypeArtifactType::Pdf, 'reference' => 'samples/harbourmaster/tide-board/tide-board-v2.pdf', 'filed' => 110, 'metadata' => ['pages' => 1, 'size' => 'A3']],
                            ['name' => 'Rules reference, one page', 'type' => PrototypeArtifactType::Document, 'reference' => 'samples/harbourmaster/tide-board/rules-reference-v2.docx', 'filed' => 109, 'metadata' => ['words' => 610]],
                        ],
                    ],
                    [
                        'name' => 'Three contract piles',
                        'description' => 'The shared contract row replaced by three piles, printed on the same '
                            .'board so the table footprint does not grow again.',
                        'by' => 'mara@lanternandanvil.test',
                        'cut' => 44,
                        'artifacts' => [
                            ['name' => 'Tide and contract board', 'type' => PrototypeArtifactType::Pdf, 'reference' => 'samples/harbourmaster/tide-board/tide-and-contracts-v3.pdf', 'filed' => 44, 'metadata' => ['pages' => 1, 'size' => 'A3']],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'harbourmaster',
                'version' => 3,
                'name' => 'Tabletop Simulator build',
                'description' => 'A digital build for testing with people who are not in Bristol, and for '
                    .'timing games without a stopwatch on the table.',
                'type' => PrototypeType::Digital,
                'status' => PrototypeStatus::Active,
                'by' => 'devin@lanternandanvil.test',
                'built' => 40,
                'versions' => [
                    [
                        'name' => 'First build',
                        'description' => 'Everything from the paper version, plus an automatic turn timer.',
                        'by' => 'devin@lanternandanvil.test',
                        'cut' => 40,
                        'artifacts' => [
                            ['name' => 'Workshop build', 'type' => PrototypeArtifactType::Build, 'reference' => 'samples/harbourmaster/tts/harbourmaster-build-1.json', 'filed' => 40, 'metadata' => ['engine' => 'Tabletop Simulator', 'build' => 1]],
                            ['name' => 'Turn timings, sessions 1 to 6', 'type' => PrototypeArtifactType::Spreadsheet, 'reference' => 'samples/harbourmaster/tts/turn-timings.xlsx', 'filed' => 27, 'metadata' => ['rows' => 412, 'sessions' => 6]],
                        ],
                    ],
                    [
                        'name' => 'Short fourth cycle',
                        'description' => 'Fourth tide cycle cut from five rounds to three, to time it against '
                            .'the stated maximum.',
                        'by' => 'devin@lanternandanvil.test',
                        'cut' => 13,
                        'artifacts' => [
                            ['name' => 'Workshop build, short cycle', 'type' => PrototypeArtifactType::Build, 'reference' => 'samples/harbourmaster/tts/harbourmaster-build-2.json', 'filed' => 13, 'metadata' => ['engine' => 'Tabletop Simulator', 'build' => 2]],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'kiln',
                'version' => 1,
                'name' => 'Index-card kiln',
                'description' => 'Four slots drawn on a card, clay pieces cut from a cereal box.',
                'type' => PrototypeType::Paper,
                'status' => PrototypeStatus::Active,
                'by' => 'devin@lanternandanvil.test',
                'built' => 71,
                'versions' => [
                    [
                        'name' => 'One kiln',
                        'description' => 'A single shared kiln that either player may fire.',
                        'by' => 'devin@lanternandanvil.test',
                        'cut' => 71,
                    ],
                    [
                        'name' => 'Two kilns',
                        'description' => 'One kiln each, both face up, so the timing decision is a read on the '
                            .'other player rather than on the deck.',
                        'by' => 'devin@lanternandanvil.test',
                        'cut' => 26,
                        'artifacts' => [
                            ['name' => 'Kiln boards, two-player', 'type' => PrototypeArtifactType::Pdf, 'reference' => 'samples/kiln/kiln-boards-v2.pdf', 'filed' => 26, 'metadata' => ['pages' => 1, 'boards' => 2]],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'tidewrack',
                'version' => 1,
                'name' => 'Wreck grid',
                'description' => 'A five-by-five grid drawn on card, divers as glass beads, air tracked with '
                    .'a clip on a printed strip.',
                'type' => PrototypeType::Paper,
                'status' => PrototypeStatus::Archived,
                'by' => 'mara@lanternandanvil.test',
                'built' => 390,
                'versions' => [
                    [
                        'name' => 'First grid',
                        'description' => 'Salvage scored by weight, air spent per space moved.',
                        'by' => 'mara@lanternandanvil.test',
                        'cut' => 390,
                    ],
                    [
                        'name' => 'Set scoring',
                        'description' => 'Salvage scored by set instead of weight, in the hope of giving a '
                            .'second dive a reason to exist.',
                        'by' => 'ilse@lanternandanvil.test',
                        'cut' => 316,
                        'artifacts' => [
                            ['name' => 'Salvage set cards', 'type' => PrototypeArtifactType::Pdf, 'reference' => 'samples/tidewrack/salvage-sets-v2.pdf', 'filed' => 316, 'by' => 'ilse@lanternandanvil.test', 'metadata' => ['pages' => 3, 'cards' => 24]],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::NIGHTSHIFT,
                'game' => 'ember-court',
                'version' => 1,
                'name' => 'Print and play deck',
                'description' => 'Sixty cards printed four to a page and cut with a guillotine, in a sandwich '
                    .'bag.',
                'type' => PrototypeType::Paper,
                'status' => PrototypeStatus::Active,
                'by' => 'yusuf@nightshiftgames.test',
                'built' => 269,
                'versions' => [
                    [
                        'name' => 'Staff room deck',
                        'description' => 'Four suits, favour earned by winning tricks.',
                        'by' => 'yusuf@nightshiftgames.test',
                        'cut' => 269,
                    ],
                    [
                        'name' => 'Favour on the bid',
                        'description' => 'Favour spent to change a bid rather than earned by taking tricks.',
                        'by' => 'yusuf@nightshiftgames.test',
                        'cut' => 156,
                        'artifacts' => [
                            ['name' => 'Deck, print and play', 'type' => PrototypeArtifactType::Pdf, 'reference' => 'samples/ember-court/deck-v2.pdf', 'filed' => 156, 'metadata' => ['pages' => 15, 'cards' => 60]],
                        ],
                    ],
                    [
                        'name' => 'Rulebook edition deck',
                        'description' => 'The deck the written rulebook describes, with the ember on its own '
                            .'token instead of a card.',
                        'by' => 'yusuf@nightshiftgames.test',
                        'cut' => 61,
                        'artifacts' => [
                            ['name' => 'Deck, rulebook edition', 'type' => PrototypeArtifactType::Pdf, 'reference' => 'samples/ember-court/deck-v3.pdf', 'filed' => 61, 'metadata' => ['pages' => 15, 'cards' => 60]],
                            ['name' => 'Rulebook, first full draft', 'type' => PrototypeArtifactType::Document, 'reference' => 'samples/ember-court/rulebook-draft-1.docx', 'filed' => 58, 'metadata' => ['words' => 2140, 'sections' => 9]],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::NIGHTSHIFT,
                'game' => 'ember-court',
                'version' => 3,
                'name' => 'Boxed proof copy',
                'description' => 'A single printed proof from the manufacturer, used for blind tests and for '
                    .'checking that the box closes.',
                'type' => PrototypeType::Physical,
                'status' => PrototypeStatus::Active,
                'by' => 'yusuf@nightshiftgames.test',
                'built' => 31,
                'versions' => [
                    [
                        'name' => 'Proof one',
                        'description' => 'Linen finish, five favour tokens, one wooden ember.',
                        'by' => 'yusuf@nightshiftgames.test',
                        'cut' => 31,
                        'artifacts' => [
                            ['name' => 'Component specification', 'type' => PrototypeArtifactType::Spreadsheet, 'reference' => 'samples/ember-court/component-spec.xlsx', 'filed' => 31, 'metadata' => ['rows' => 12, 'currency' => 'GBP']],
                            ['name' => 'Proof copy photographs', 'type' => PrototypeArtifactType::Image, 'reference' => 'samples/ember-court/proof-copy.jpg', 'filed' => 30, 'metadata' => ['width' => 4032, 'height' => 3024]],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * The change log, one entry per question somebody set out to answer.
     *
     * Each iteration names the design version and the prototype version it ran
     * against, which is what makes a later reader able to say what was actually
     * on the table when the evidence was gathered.
     *
     * @return list<array<string, mixed>>
     */
    protected function iterations(): array
    {
        return [
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'harbourmaster',
                'version' => 2,
                'prototype' => 'Tide board mock-up',
                'prototype_version' => 1,
                'title' => 'Move the tide onto its own board',
                'objective' => 'Stop players having to ask when a berth closes, by putting the tide where they '
                    .'can see it.',
                'hypothesis' => 'A visible tide will be read as information rather than as a rules detail, and '
                    .'the questions will stop.',
                'status' => IterationStatus::Completed,
                'outcome' => IterationOutcome::Success,
                'summary' => 'The questions stopped after one round. The unplanned result was better: players '
                    .'started treating the tide as a threat and physically leaned over to check it before '
                    .'placing. The game is now about the tide rather than merely timed by it.',
                'by' => 'mara@lanternandanvil.test',
                'started' => 133,
                'finished' => 101,
                'playtests' => ['Does the tide board read at a glance?'],
                'changes' => [
                    [
                        'category' => DesignChangeCategory::Components,
                        'title' => 'Tide moved from the rules card to a printed board',
                        'description' => 'An A3 strip in the middle of the table with a marker that advances one '
                            .'step each round.',
                        'reason' => 'Players were asking when berths closed two or three times a round, which '
                            .'meant the information existed and was not reachable.',
                        'by' => 'devin@lanternandanvil.test',
                    ],
                    [
                        'category' => DesignChangeCategory::Ux,
                        'title' => 'Direction arrow added to the tide board',
                        'description' => 'A single arrow showing which way the marker travels.',
                        'reason' => 'Two of four players in the first session read the tide backwards. Neither '
                            .'of them made the same mistake once the arrow was on the board.',
                        'by' => 'tomas@lanternandanvil.test',
                    ],
                ],
                'experiments' => [
                    [
                        'title' => 'Count the questions',
                        'question' => 'How many times a round do players ask when a berth closes?',
                        'hypothesis' => 'A visible tide board takes it to nearly zero after the first round.',
                        'method' => 'Tally every question about berth timing, by round, across two sessions '
                            .'with different groups.',
                        'expected' => 'Two or three questions in round one, then almost none.',
                        'actual' => 'Four questions in round one of the first session, one in round two, none '
                            .'after that. Second session: two in round one and none thereafter.',
                        'conclusion' => 'Answered. The questions were an access problem, not a comprehension '
                            .'problem.',
                        'status' => ExperimentStatus::Completed,
                        'by' => 'mara@lanternandanvil.test',
                    ],
                ],
                'decisions' => [
                    [
                        'title' => 'Keep the tide board',
                        'decision' => 'The tide board becomes part of the design rather than a prototype aid, '
                            .'and the rules card loses its tide section entirely.',
                        'reason' => 'It answered the question it was built for and changed how players behave '
                            .'at the table, which is a stronger result than the one we were testing for.',
                        'status' => DecisionStatus::Accepted,
                        'by' => 'mara@lanternandanvil.test',
                        'decided_by' => 'mara@lanternandanvil.test',
                        'evidence' => [
                            ['type' => EvidenceType::Playtest, 'reference' => 'Does the tide board read at a glance?', 'description' => 'Both sessions of the tide board playtest, across two different groups.'],
                            ['type' => EvidenceType::Observation, 'reference' => 'Oscar physically leaned over the table', 'description' => 'The clearest single sign that the board is being read rather than merely present.'],
                            ['type' => EvidenceType::Experiment, 'reference' => 'Count the questions', 'description' => 'Question counts by round, both sessions.'],
                        ],
                    ],
                    [
                        'title' => 'Print the direction arrow on every future board',
                        'decision' => 'Every tide board from here on carries a direction arrow, and the '
                            .'component specification says so.',
                        'reason' => 'Half the new players in the first session read the tide backwards without '
                            .'one, and nobody has since.',
                        'status' => DecisionStatus::Accepted,
                        'by' => 'tomas@lanternandanvil.test',
                        'decided_by' => 'mara@lanternandanvil.test',
                        'evidence' => [
                            ['type' => EvidenceType::Observation, 'reference' => 'Both new players read the tide as moving left to right', 'description' => 'The misreading, recorded at the session it happened in.'],
                            ['type' => EvidenceType::Note, 'description' => 'Costs nothing: it is ink on a board we are already printing.'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'harbourmaster',
                'version' => 3,
                'prototype' => 'Tabletop Simulator build',
                'prototype_version' => 1,
                'title' => 'Replace the contract row with three piles',
                'objective' => 'Make taking a contract register as taking it away from somebody.',
                'hypothesis' => 'Three visible piles will make players watch what their neighbours are '
                    .'building towards, and take from those piles deliberately.',
                'status' => IterationStatus::Completed,
                'outcome' => IterationOutcome::Partial,
                'summary' => 'Denial arrived exactly as hoped and was named out loud by players in both '
                    .'sessions. It also slowed the fourth cycle down and pushed the four-player game further '
                    .'over its stated length, which is now the next iteration.',
                'by' => 'mara@lanternandanvil.test',
                'started' => 46,
                'finished' => 24,
                'playtests' => ['Are three contract piles enough of a denial decision?'],
                'changes' => [
                    [
                        'category' => DesignChangeCategory::Mechanics,
                        'title' => 'Shared contract row replaced by three face-up piles',
                        'description' => 'Contracts are drawn from the top of one of three piles, each refilled '
                            .'from a separate deck.',
                        'reason' => 'A single shared row meant every contract was equally available to '
                            .'everybody, so taking one was never taking it from anyone in particular.',
                        'by' => 'mara@lanternandanvil.test',
                    ],
                    [
                        'category' => DesignChangeCategory::PlayerInteraction,
                        'title' => 'Contract piles are not reshuffled between cycles',
                        'description' => 'What is under the top card stays under it, so a pile somebody is '
                            .'building towards can be attacked.',
                        'reason' => 'Reshuffling would return the game to a shared pool with extra steps.',
                        'by' => 'devin@lanternandanvil.test',
                    ],
                ],
                'experiments' => [
                    [
                        'title' => 'Count deliberate denials',
                        'question' => 'How often does a player take a contract they cannot use, to keep it from '
                            .'somebody else?',
                        'hypothesis' => 'At least twice a game at four players.',
                        'method' => 'Record every contract taken that the taker had no crates towards, and ask '
                            .'the player afterwards why they took it.',
                        'expected' => 'Two or three a game, mostly in the third and fourth cycles.',
                        'actual' => 'Four in the four-player game and three in the two-player game. At two '
                            .'players it happened in every cycle.',
                        'conclusion' => 'Answered, and the two-player figure is a new question: constant denial '
                            .'may be too much of it.',
                        'status' => ExperimentStatus::Completed,
                        'by' => 'devin@lanternandanvil.test',
                    ],
                    [
                        'title' => 'Time the fourth cycle against v2',
                        'question' => 'Do three piles make the fourth cycle longer than the shared row did?',
                        'hypothesis' => 'No measurable difference: it is the same number of cards.',
                        'method' => 'Compare fourth-cycle wall-clock time on the digital build against the '
                            .'recorded v2 sessions.',
                        'expected' => 'Within two minutes either way.',
                        'actual' => 'Fourth cycle went up by four minutes at four players. Players read three '
                            .'piles where they used to read one row.',
                        'conclusion' => 'Hypothesis wrong. The cost of the change is turn length, and it lands '
                            .'in the cycle that was already too long.',
                        'status' => ExperimentStatus::Completed,
                        'by' => 'devin@lanternandanvil.test',
                    ],
                ],
                'decisions' => [
                    [
                        'title' => 'Keep the three piles',
                        'decision' => 'The three-pile contract market stays, and the length problem is treated '
                            .'as a separate iteration rather than as a reason to revert.',
                        'reason' => 'Denial is the interaction the game was missing and no cheaper way of '
                            .'getting it has been proposed. Length was already over before this change.',
                        'status' => DecisionStatus::Accepted,
                        'by' => 'mara@lanternandanvil.test',
                        'decided_by' => 'mara@lanternandanvil.test',
                        'evidence' => [
                            ['type' => EvidenceType::Playtest, 'reference' => 'Are three contract piles enough of a denial decision?', 'description' => 'Both sessions, four-player and two-player.'],
                            ['type' => EvidenceType::Experiment, 'reference' => 'Count deliberate denials', 'description' => 'Seven deliberate denials across two games.'],
                            ['type' => EvidenceType::Observation, 'reference' => 'Hannah took a contract she had no crates for', 'description' => 'A player naming the denial out loud while doing it.'],
                        ],
                    ],
                    [
                        'title' => 'Look at two-player separately',
                        'decision' => 'Two-player contract rules are treated as an open question rather than '
                            .'assumed to be the four-player rules with fewer people.',
                        'reason' => 'At two players every pile is contested every round, which makes denial '
                            .'constant rather than occasional. That may be a different game.',
                        'status' => DecisionStatus::Deferred,
                        'by' => 'mara@lanternandanvil.test',
                        'decided_by' => 'mara@lanternandanvil.test',
                        'evidence' => [
                            ['type' => EvidenceType::Observation, 'reference' => 'At two players every pile is contested', 'description' => 'Noted during the digital two-player session.'],
                            ['type' => EvidenceType::Note, 'description' => 'Deferred until the four-player length is settled, because a change there may change this.'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'harbourmaster',
                'version' => 3,
                'prototype' => 'Tabletop Simulator build',
                'prototype_version' => 2,
                'title' => 'Bring the four-player game inside seventy-five minutes',
                'objective' => 'Get the four-player game to its stated maximum length without losing the '
                    .'end-game bonuses that keep a trailing player in it.',
                'hypothesis' => 'Cutting the fourth tide cycle from five rounds to three will take fifteen '
                    .'minutes out of the game and change nothing else.',
                'status' => IterationStatus::InProgress,
                'by' => 'priya@lanternandanvil.test',
                'started' => 14,
                'playtests' => ['Does the four-player game finish inside seventy-five minutes?'],
                'changes' => [
                    [
                        'category' => DesignChangeCategory::Pacing,
                        'title' => 'Fourth tide cycle shortened from five rounds to three',
                        'description' => 'The cycle still scores the same way; there are simply two fewer '
                            .'rounds to act in.',
                        'reason' => 'The fourth cycle has been the longest and least eventful part of the game '
                            .'in every timed session since v2.',
                        'by' => 'devin@lanternandanvil.test',
                    ],
                ],
                'experiments' => [
                    [
                        'title' => 'Time four-player games with the short cycle',
                        'question' => 'Does a three-round fourth cycle bring a four-player game inside '
                            .'seventy-five minutes?',
                        'hypothesis' => 'Yes, by about fifteen minutes.',
                        'method' => 'Three four-player sessions with the digital build\'s turn timer running, '
                            .'no rules explanation counted.',
                        'expected' => 'Sixty-six to seventy minutes.',
                        'actual' => 'Eighty-one minutes in the one session run so far.',
                        'status' => ExperimentStatus::Running,
                        'by' => 'priya@lanternandanvil.test',
                    ],
                    [
                        'title' => 'Shorten the third cycle instead',
                        'question' => 'Does taking the rounds out of the third cycle rather than the fourth '
                            .'cost less of what players like?',
                        'hypothesis' => 'Players mind losing the ending more than they mind losing the middle.',
                        'method' => 'Same four-player setup with the fourth cycle restored to five rounds and '
                            .'the third cut to three.',
                        'expected' => 'Similar total length, fewer complaints about the ending.',
                        'status' => ExperimentStatus::Planned,
                        'by' => 'priya@lanternandanvil.test',
                    ],
                ],
                'decisions' => [
                    [
                        'title' => 'Cut a cycle rather than trim rounds',
                        'decision' => 'Drop the game from four tide cycles to three, and move the end-game '
                            .'bonuses onto the third.',
                        'reason' => 'Two rounds off the fourth cycle bought six minutes and cost the ending. '
                            .'Fifteen minutes is a cycle, and pretending otherwise has already taken two '
                            .'iterations.',
                        'status' => DecisionStatus::Proposed,
                        'by' => 'priya@lanternandanvil.test',
                        'evidence' => [
                            ['type' => EvidenceType::Playtest, 'reference' => 'Does the four-player game finish inside seventy-five minutes?', 'description' => 'The one session run so far: eighty-one minutes.'],
                            ['type' => EvidenceType::Observation, 'reference' => 'Two players reached the last round unable to complete a contract', 'description' => 'What shortening the ending actually cost.'],
                            ['type' => EvidenceType::Feedback, 'reference' => 'It ended one round before it should have', 'description' => 'A player describing the shortened ending as being cut off rather than beaten.'],
                            ['type' => EvidenceType::Note, 'description' => 'Nobody has yet argued for keeping four cycles on design grounds rather than on habit.'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'kiln',
                'version' => 2,
                'prototype' => 'Index-card kiln',
                'prototype_version' => 2,
                'title' => 'Give each player their own kiln',
                'objective' => 'Find out whether two visible kilns turn the firing call into a read on the '
                    .'opponent instead of a calculation about the deck.',
                'hypothesis' => 'Seeing what the other player has loaded is more interesting information than '
                    .'knowing what is left in the bag.',
                'status' => IterationStatus::InProgress,
                'by' => 'devin@lanternandanvil.test',
                'started' => 26,
                'playtests' => ['Is the firing call still interesting when both kilns are visible?'],
                'changes' => [
                    [
                        'category' => DesignChangeCategory::Mechanics,
                        'title' => 'One kiln each, both face up',
                        'description' => 'Each player loads their own kiln and may fire either one.',
                        'reason' => 'A single shared kiln made the decision about the deck. Two visible kilns '
                            .'make it about the other player.',
                        'by' => 'devin@lanternandanvil.test',
                    ],
                    [
                        'category' => DesignChangeCategory::Rules,
                        'title' => 'Firing an opponent\'s kiln scores you nothing',
                        'description' => 'You may fire their kiln, but only they score what comes out of it.',
                        'reason' => 'Without this the correct play is always to fire theirs, and there is no '
                            .'decision left.',
                        'by' => 'devin@lanternandanvil.test',
                    ],
                ],
                'experiments' => [
                    [
                        'title' => 'Does anybody fire the opponent\'s kiln?',
                        'question' => 'Is firing an opponent\'s kiln for nothing ever the right play?',
                        'hypothesis' => 'Yes, when their kiln is about to overtake your score.',
                        'method' => 'Four games back to back, recording every firing and whose kiln it was.',
                        'expected' => 'Once or twice across four games.',
                        'status' => ExperimentStatus::Planned,
                        'by' => 'devin@lanternandanvil.test',
                    ],
                ],
                'decisions' => [
                    [
                        'title' => 'Settle how the game ends',
                        'decision' => 'The game ends when the clay runs out, and the player with the most '
                            .'surviving pieces wins.',
                        'reason' => 'Kiln has had no ending since it started, which is the one thing the core '
                            .'loop checklist will not let us past — and every session so far has ended by '
                            .'somebody saying "shall we stop?".',
                        'status' => DecisionStatus::Proposed,
                        'by' => 'devin@lanternandanvil.test',
                        'evidence' => [
                            ['type' => EvidenceType::Note, 'description' => 'The design record still has no win condition and no failure condition. That is the whole of the evidence and it is enough.'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'tidewrack',
                'version' => 2,
                'prototype' => 'Wreck grid',
                'prototype_version' => 2,
                'title' => 'Make the second dive worth taking',
                'objective' => 'Give a player who has surfaced with salvage a reason to go back down.',
                'hypothesis' => 'Scoring salvage by set rather than by weight will make an incomplete set '
                    .'worth the risk of a second dive.',
                'status' => IterationStatus::Completed,
                'outcome' => IterationOutcome::Failed,
                'summary' => 'Set scoring added a rule and changed no decisions. Every player who surfaced '
                    .'with salvage stopped, at both player counts. The loop has one decision in it and it '
                    .'happens once per dive, which is not a design problem this iteration could reach. '
                    .'Tidewrack was archived after this.',
                'by' => 'mara@lanternandanvil.test',
                'started' => 318,
                'finished' => 296,
                'playtests' => ['Can a second dive ever be worth taking?'],
                'changes' => [
                    [
                        'category' => DesignChangeCategory::Economy,
                        'title' => 'Salvage scored by set instead of by weight',
                        'description' => 'Three matching pieces are worth more than five unmatched ones.',
                        'reason' => 'Weight scoring made every piece equally worth surfacing with, so there '
                            .'was never a reason to want a particular one.',
                        'by' => 'ilse@lanternandanvil.test',
                    ],
                ],
                'experiments' => [
                    [
                        'title' => 'Count second dives',
                        'question' => 'How many players take a second dive after surfacing with salvage?',
                        'hypothesis' => 'Set scoring takes it from nearly none to about half.',
                        'method' => 'Record every dive at a five-player and a three-player session.',
                        'expected' => 'Three or four second dives across the two sessions.',
                        'actual' => 'Two, both by the same player, both ending in a drowned diver.',
                        'conclusion' => 'Hypothesis wrong, and the reason is not the scoring: surfacing is '
                            .'safe and diving is not, and no scoring rule reaches that.',
                        'status' => ExperimentStatus::Completed,
                        'by' => 'ilse@lanternandanvil.test',
                    ],
                ],
                'decisions' => [
                    [
                        'title' => 'Shelve Tidewrack',
                        'decision' => 'Archive the game. Keep the air track and the tide idea; the salvage '
                            .'game around them does not work.',
                        'reason' => 'Three iterations have all failed at the same place, and the last one '
                            .'showed the problem is the loop rather than what is attached to it. Continuing '
                            .'would be sunk cost.',
                        'status' => DecisionStatus::Accepted,
                        'by' => 'mara@lanternandanvil.test',
                        'decided_by' => 'mara@lanternandanvil.test',
                        'evidence' => [
                            ['type' => EvidenceType::Playtest, 'reference' => 'Can a second dive ever be worth taking?', 'description' => 'Both sessions, five players and three.'],
                            ['type' => EvidenceType::Experiment, 'reference' => 'Count second dives', 'description' => 'Two second dives across two sessions, both fatal.'],
                            ['type' => EvidenceType::Feedback, 'reference' => 'The first dive is genuinely tense', 'description' => 'A player naming the problem more precisely than we had.'],
                            ['type' => EvidenceType::Note, 'description' => 'The tide idea survives into Harbourmaster, which is the return on this project.'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::NIGHTSHIFT,
                'game' => 'ember-court',
                'version' => 2,
                'prototype' => 'Print and play deck',
                'prototype_version' => 2,
                'title' => 'Move favour from tricks to bids',
                'objective' => 'Stop the player who is already winning from earning the resource that helps '
                    .'them keep winning.',
                'hypothesis' => 'Favour spent on bids rather than earned by tricks will give the trailing '
                    .'player the most flexible turn.',
                'status' => IterationStatus::Completed,
                'outcome' => IterationOutcome::Success,
                'summary' => 'The runaway leader is gone. Games now finish within two or three points and the '
                    .'favour decision is the most talked-about moment in every session.',
                'by' => 'yusuf@nightshiftgames.test',
                'started' => 162,
                'finished' => 149,
                'changes' => [
                    [
                        'category' => DesignChangeCategory::Economy,
                        'title' => 'Favour is spent to change a bid, not earned by taking tricks',
                        'description' => 'Each player starts a hand with one favour, and the ember holder '
                            .'starts with two.',
                        'reason' => 'Earning favour by winning tricks handed the leader a second advantage '
                            .'for the same play.',
                        'by' => 'yusuf@nightshiftgames.test',
                    ],
                    [
                        'category' => DesignChangeCategory::Balance,
                        'title' => 'Whole scoring track removed',
                        'description' => 'The separate favour score is gone; favour is only ever spent.',
                        'reason' => 'With favour no longer earned, a track to record it on was recording '
                            .'nothing.',
                        'by' => 'yusuf@nightshiftgames.test',
                    ],
                ],
                'experiments' => [
                    [
                        'title' => 'Winning margin across ten games',
                        'question' => 'Does the new favour rule close the gap between first and last?',
                        'hypothesis' => 'Final margins drop from six or more points to about three.',
                        'method' => 'Record final scores for ten games at three, four and five players.',
                        'expected' => 'Average margin around three points.',
                        'actual' => 'Average margin 2.4 points across ten games, and four games decided on '
                            .'the last hand.',
                        'conclusion' => 'Answered. The rework did what it was for.',
                        'status' => ExperimentStatus::Completed,
                        'by' => 'yusuf@nightshiftgames.test',
                    ],
                ],
                'decisions' => [
                    [
                        'title' => 'Favour on the bid is the design',
                        'decision' => 'Keep the new favour rule and write the rulebook around it.',
                        'reason' => 'It fixed the runaway leader, removed a scoring track, and produced the '
                            .'most interesting decision in the game.',
                        'status' => DecisionStatus::Accepted,
                        'by' => 'yusuf@nightshiftgames.test',
                        'decided_by' => 'yusuf@nightshiftgames.test',
                        'evidence' => [
                            ['type' => EvidenceType::Experiment, 'reference' => 'Winning margin across ten games', 'description' => 'Ten games, average margin 2.4 points.'],
                            ['type' => EvidenceType::Note, 'description' => 'It also made the rules shorter, which is the second time this project has improved by losing something.'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::NIGHTSHIFT,
                'game' => 'ember-court',
                'version' => 3,
                'prototype' => 'Boxed proof copy',
                'prototype_version' => 1,
                'title' => 'Blind-test the rulebook',
                'objective' => 'Find out whether a group with no explanation can learn and play the game from '
                    .'the rulebook alone.',
                'hypothesis' => 'The rulebook is complete enough that the only questions will be edge cases.',
                'status' => IterationStatus::Completed,
                'outcome' => IterationOutcome::Partial,
                'summary' => 'They taught themselves and played twice, which is the result that mattered. One '
                    .'question came up in both games — whether favour carries between hands — and it is '
                    .'answered three sections later than players need it.',
                'by' => 'yusuf@nightshiftgames.test',
                'started' => 24,
                'finished' => 18,
                'playtests' => ['Blind test: can four strangers learn it from the rulebook alone?'],
                'changes' => [
                    [
                        'category' => DesignChangeCategory::Rules,
                        'title' => 'Favour section moved ahead of bidding in the rulebook',
                        'description' => 'No rule changes; the section order changes.',
                        'reason' => 'Both blind games asked whether favour carries between hands, at the point '
                            .'where they were learning to bid. The answer was three sections away.',
                        'by' => 'yusuf@nightshiftgames.test',
                    ],
                ],
                'experiments' => [
                    [
                        'title' => 'Count questions in a blind teach',
                        'question' => 'How many questions does a group with no explanation have to leave '
                            .'unanswered?',
                        'hypothesis' => 'None that stop play.',
                        'method' => 'Ask the group to write down every question they could not answer from '
                            .'the book, and to guess rather than call.',
                        'expected' => 'One or two, all edge cases.',
                        'actual' => 'One, asked in both games, about a rule that is in the book.',
                        'conclusion' => 'The rulebook is complete and badly ordered. That is an edit.',
                        'status' => ExperimentStatus::Completed,
                        'by' => 'yusuf@nightshiftgames.test',
                    ],
                ],
                'decisions' => [
                    [
                        'title' => 'Reorder rather than rewrite',
                        'decision' => 'Move the favour section and re-run a blind test before anything else '
                            .'in the rulebook is touched.',
                        'reason' => 'Only one question came up and it is an ordering problem. Rewriting a '
                            .'rulebook that four strangers already taught themselves from would be a '
                            .'regression risk for no gain.',
                        'status' => DecisionStatus::Accepted,
                        'by' => 'yusuf@nightshiftgames.test',
                        'decided_by' => 'yusuf@nightshiftgames.test',
                        'evidence' => [
                            ['type' => EvidenceType::Playtest, 'reference' => 'Blind test: can four strangers learn it from the rulebook alone?', 'description' => 'The session itself, run without the designer present.'],
                            ['type' => EvidenceType::Observation, 'reference' => 'Asked whether favour carries between hands', 'description' => 'The one question, in both games.'],
                            ['type' => EvidenceType::Feedback, 'reference' => 'The favour rules are in the wrong order', 'description' => 'A player diagnosing it themselves.'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Write one prototype, its versions and the files filed against them.
     *
     * @param  array<string, mixed>  $definition
     */
    private function prototypeRecord(array $definition): void
    {
        $game = $this->game($definition['workspace'], $definition['game']);

        $prototype = Prototype::query()->firstOrNew([
            'game_id' => $game->getKey(),
            'name' => $definition['name'],
        ]);

        $prototype->fill([
            'name' => $definition['name'],
            'description' => $definition['description'],
        ]);

        $prototype->game_id = $game->getKey();
        $prototype->game_version_id = $this->version($game, $definition['version'])->getKey();
        $prototype->type = $definition['type'];
        $prototype->status = $definition['status'];
        $prototype->created_by = $this->user($definition['by'])->id;

        $this->stamp($prototype, $this->daysAgo($definition['built'], 14));
        $prototype->save();

        foreach ($definition['versions'] as $index => $version) {
            $cut = $this->prototypeVersion($prototype, $index + 1, $version);

            foreach ($version['artifacts'] ?? [] as $artifact) {
                $this->artifact($cut, $artifact, $definition['by']);
            }
        }
    }

    /**
     * Write one prototype version.
     *
     * @param  array<string, mixed>  $definition
     */
    private function prototypeVersion(Prototype $prototype, int $number, array $definition): PrototypeVersion
    {
        $version = PrototypeVersion::query()->firstOrNew([
            'prototype_id' => $prototype->getKey(),
            'version_number' => $number,
        ]);

        $version->fill([
            'name' => $definition['name'] ?? null,
            'description' => $definition['description'] ?? null,
        ]);

        $version->prototype_id = $prototype->getKey();
        $version->version_number = $number;
        $version->created_by = $this->user($definition['by'])->id;

        $this->stamp($version, $this->daysAgo($definition['cut'], 14));
        $version->save();

        return $version;
    }

    /**
     * File something against a prototype version.
     *
     * @param  array<string, mixed>  $definition
     */
    private function artifact(PrototypeVersion $version, array $definition, string $fallbackAuthor): void
    {
        $artifact = PrototypeArtifact::query()->firstOrNew([
            'prototype_version_id' => $version->getKey(),
            'name' => $definition['name'],
        ]);

        $artifact->fill(['name' => $definition['name']]);

        $artifact->prototype_version_id = $version->getKey();
        $artifact->type = $definition['type'];
        $artifact->storage_reference = $definition['reference'];
        $artifact->metadata = $definition['metadata'] ?? null;
        $artifact->created_by = $this->user($definition['by'] ?? $fallbackAuthor)->id;

        $this->stamp($artifact, $this->daysAgo($definition['filed'], 15));
        $artifact->save();
    }

    /**
     * Write one iteration and everything it produced.
     *
     * @param  array<string, mixed>  $definition
     */
    private function iterationRecord(array $definition): void
    {
        $game = $this->game($definition['workspace'], $definition['game']);

        $iteration = Iteration::query()->firstOrNew([
            'game_id' => $game->getKey(),
            'title' => $definition['title'],
        ]);

        $iteration->fill([
            'title' => $definition['title'],
            'objective' => $definition['objective'],
            'hypothesis' => $definition['hypothesis'] ?? null,
        ]);

        $iteration->game_id = $game->getKey();
        $iteration->game_version_id = $this->version($game, $definition['version'])->getKey();
        $iteration->prototype_version_id = $this->prototypeVersionOf(
            $game,
            $definition['prototype'],
            $definition['prototype_version'],
        )->getKey();

        $iteration->status = $definition['status'];
        $iteration->outcome = $definition['outcome'] ?? null;
        $iteration->summary = $definition['summary'] ?? null;
        $iteration->started_at = isset($definition['started']) ? $this->daysAgo($definition['started'], 10) : null;
        $iteration->completed_at = isset($definition['finished']) ? $this->daysAgo($definition['finished'], 17) : null;
        $iteration->created_by = $this->user($definition['by'])->id;

        $opened = $iteration->started_at ?? $this->daysAgo($definition['opened'] ?? 30, 10);

        $this->stamp($iteration, $opened, $iteration->completed_at ?? $opened);
        $iteration->save();

        foreach ($definition['playtests'] ?? [] as $title) {
            $this->attachPlaytest($iteration, $game, $title, $opened);
        }

        foreach ($definition['changes'] ?? [] as $offset => $change) {
            $this->change($iteration, $change, $opened->addDays($offset + 1));
        }

        $experiments = [];

        foreach ($definition['experiments'] ?? [] as $offset => $experiment) {
            $experiments[$experiment['title']] = $this->experiment($iteration, $experiment, $opened->addDays($offset + 2));
        }

        foreach ($definition['decisions'] ?? [] as $offset => $decision) {
            $this->decision($iteration, $game, $decision, $opened->addDays($offset + 4), $experiments);
        }
    }

    /**
     * Find the prototype version an iteration was run against.
     */
    private function prototypeVersionOf(Game $game, string $prototype, int $number): PrototypeVersion
    {
        $version = PrototypeVersion::query()
            ->whereIn('prototype_id', Prototype::query()
                ->where('game_id', $game->getKey())
                ->where('name', $prototype)
                ->select('id'))
            ->where('version_number', $number)
            ->first();

        return $version ?? throw new \RuntimeException(
            "Sample prototype [{$game->slug}/{$prototype}] has no v{$number}."
        );
    }

    /**
     * Attach a playtest to an iteration as the session that tested it.
     */
    private function attachPlaytest(Iteration $iteration, Game $game, string $title, CarbonImmutable $on): void
    {
        $playtest = Playtest::query()
            ->where('game_id', $game->getKey())
            ->where('title', $title)
            ->first();

        if ($playtest === null) {
            return;
        }

        $link = IterationPlaytest::query()->firstOrNew([
            'iteration_id' => $iteration->getKey(),
            'playtest_id' => $playtest->getKey(),
        ]);

        $link->iteration_id = $iteration->getKey();
        $link->playtest_id = $playtest->getKey();
        $link->created_by = $iteration->created_by;

        $this->stamp($link, $on);
        $link->save();
    }

    /**
     * Record one thing that changed, and why.
     *
     * @param  array{category: DesignChangeCategory, title: string, description?: string, reason: string, by: string}  $definition
     */
    private function change(Iteration $iteration, array $definition, CarbonImmutable $on): void
    {
        $change = DesignChange::query()->firstOrNew([
            'iteration_id' => $iteration->getKey(),
            'title' => $definition['title'],
        ]);

        $change->fill([
            'title' => $definition['title'],
            'description' => $definition['description'] ?? null,
            'reason' => $definition['reason'],
        ]);

        $change->iteration_id = $iteration->getKey();
        $change->category = $definition['category'];
        $change->created_by = $this->user($definition['by'])->id;

        $this->stamp($change, $on);
        $change->save();
    }

    /**
     * Record one question the iteration set out to answer.
     *
     * @param  array<string, mixed>  $definition
     */
    private function experiment(Iteration $iteration, array $definition, CarbonImmutable $on): DesignExperiment
    {
        $experiment = DesignExperiment::query()->firstOrNew([
            'iteration_id' => $iteration->getKey(),
            'title' => $definition['title'],
        ]);

        $experiment->fill([
            'title' => $definition['title'],
            'question' => $definition['question'],
            'hypothesis' => $definition['hypothesis'] ?? null,
            'method' => $definition['method'] ?? null,
            'expected_result' => $definition['expected'] ?? null,
        ]);

        $experiment->iteration_id = $iteration->getKey();
        $experiment->actual_result = $definition['actual'] ?? null;
        $experiment->conclusion = $definition['conclusion'] ?? null;
        $experiment->status = $definition['status'];
        $experiment->started_at = $definition['status'] === ExperimentStatus::Planned ? null : $on;
        $experiment->completed_at = $definition['status'] === ExperimentStatus::Completed ? $on->addDays(6) : null;
        $experiment->created_by = $this->user($definition['by'])->id;

        $this->stamp($experiment, $on, $experiment->completed_at ?? $on);
        $experiment->save();

        return $experiment;
    }

    /**
     * Record one decision and what it was decided on.
     *
     * @param  array<string, mixed>  $definition
     * @param  array<string, DesignExperiment>  $experiments
     */
    private function decision(
        Iteration $iteration,
        Game $game,
        array $definition,
        CarbonImmutable $on,
        array $experiments,
    ): void {
        $decision = DesignDecision::query()->firstOrNew([
            'iteration_id' => $iteration->getKey(),
            'title' => $definition['title'],
        ]);

        $decision->fill([
            'title' => $definition['title'],
            'decision' => $definition['decision'],
            'reason' => $definition['reason'],
        ]);

        $decision->iteration_id = $iteration->getKey();
        $decision->status = $definition['status'];
        $decision->created_by = $this->user($definition['by'])->id;

        $settled = $definition['status'] !== DecisionStatus::Proposed;

        $decision->decided_by = $settled ? $this->user($definition['decided_by'] ?? $definition['by'])->id : null;
        $decision->decided_at = $settled ? $on->addDays(2) : null;

        $this->stamp($decision, $on, $decision->decided_at ?? $on);
        $decision->save();

        foreach ($definition['evidence'] ?? [] as $offset => $evidence) {
            $this->evidence($decision, $game, $evidence, $on->addHours($offset + 1), $experiments);
        }
    }

    /**
     * Cite one piece of evidence for a decision.
     *
     * Only a type and an identifier are stored. Nothing about the playtest,
     * observation or experiment is copied here, so a citation cannot drift out
     * of step with the thing it cites.
     *
     * @param  array<string, mixed>  $definition
     * @param  array<string, DesignExperiment>  $experiments
     */
    private function evidence(
        DesignDecision $decision,
        Game $game,
        array $definition,
        CarbonImmutable $on,
        array $experiments,
    ): void {
        $reference = match ($definition['type']) {
            EvidenceType::Playtest => Playtest::query()
                ->where('game_id', $game->getKey())
                ->where('title', $definition['reference'])
                ->value('id'),
            EvidenceType::Observation => PlaytestObservation::query()
                ->where('content', 'like', $definition['reference'].'%')
                ->value('id'),
            EvidenceType::Feedback => PlaytestFeedback::query()
                ->where('content', 'like', $definition['reference'].'%')
                ->value('id'),
            EvidenceType::Experiment => ($experiments[$definition['reference']] ?? null)?->getKey(),
            default => null,
        };

        $evidence = DecisionEvidence::query()->firstOrNew([
            'decision_id' => $decision->getKey(),
            'description' => $definition['description'],
        ]);

        $evidence->fill(['description' => $definition['description']]);

        $evidence->decision_id = $decision->getKey();
        $evidence->type = $definition['type'];
        $evidence->reference_id = $reference;
        $evidence->created_by = $decision->created_by;

        $this->stamp($evidence, $on);
        $evidence->save();
    }
}
