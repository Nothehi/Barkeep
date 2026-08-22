<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Modules\GameEconomy\Domain\Enums\ActionEffectType;
use Modules\GameEconomy\Domain\Enums\AssumptionCategory;
use Modules\GameEconomy\Domain\Enums\AssumptionConfidence;
use Modules\GameEconomy\Domain\Enums\BalanceProfileStatus;
use Modules\GameEconomy\Domain\Enums\BalanceScenarioStatus;
use Modules\GameEconomy\Domain\Enums\BalanceVariableCategory;
use Modules\GameEconomy\Domain\Enums\ObservationSeverity;
use Modules\GameEconomy\Domain\Enums\ObservationSourceType;
use Modules\GameEconomy\Domain\Enums\ResourceCategory;
use Modules\GameEconomy\Domain\Enums\ResourceFlowType;
use Modules\GameEconomy\Domain\Models\ActionCost;
use Modules\GameEconomy\Domain\Models\ActionEffect;
use Modules\GameEconomy\Domain\Models\ActionReward;
use Modules\GameEconomy\Domain\Models\BalanceAssumption;
use Modules\GameEconomy\Domain\Models\BalanceObservation;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\GameEconomy\Domain\Models\BalanceSnapshot;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\Models\ResourceFlow;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Domain\Models\ScenarioVariable;
use Modules\GameEconomy\Infrastructure\Calculations\SnapshotWriter;
use Modules\Identity\Domain\Models\User;

/**
 * The numbers behind three of the games.
 *
 * Every profile hangs off a design version rather than off a game, which is the
 * whole architecture of this module in one sentence: Harbourmaster's wood
 * income was one thing at v2 and another at v3, and a playtest run against v2
 * is only interpretable if the v2 numbers are still there to read. So
 * Harbourmaster gets two profiles — the v2 one archived, the v3 one active —
 * and nothing copies the newer one back.
 *
 * The profiles are deliberately imperfect. Harbourmaster's coin has no real
 * sink, its dockhand market is a subsystem the studio already suspects is not
 * load-bearing, and Kiln's draft profile has an action with no cost at all.
 * `BalanceAnalyser` will have things to say about all three, which is correct:
 * a half-built economy is full of warnings, and a sample database where the
 * analysis screen is empty would be teaching that the screen does nothing.
 *
 * Amounts are written as integers and decimal strings, never as floats. They go
 * through `AsQuantity` on the way in and come back out as exact decimals, and a
 * probability seeded as `0.65` the PHP float would be the one place in the
 * application where that stopped being true.
 */
class SampleEconomySeeder extends SampleSeeder
{
    /**
     * Seed the balance profiles and everything configured inside them.
     */
    public function run(): void
    {
        $profiles = 0;

        foreach ($this->profiles() as $definition) {
            $this->profile($definition);
            $profiles++;
        }

        $this->command->info("Seeded {$profiles} sample balance profiles.");
    }

    /**
     * The economies themselves.
     *
     * Harbourmaster appears twice because it has two design versions with
     * numbers worth keeping: the v2 profile is archived and still readable, and
     * the v3 profile is the one the current playtests run against.
     *
     * @return list<array<string, mixed>>
     */
    protected function profiles(): array
    {
        return [
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'harbourmaster',
                'version' => 2,
                'name' => 'Tide board economy',
                'description' => 'The numbers as they stood while contracts were a single shared row. Kept '
                    .'because every playtest between the tide board and the contract rework was run against '
                    .'these, and the observations from them only mean anything beside them.',
                'status' => BalanceProfileStatus::Archived,
                'by' => 'devin@lanternandanvil.test',
                'created' => 131,
                'touched' => 45,
                'resources' => [
                    ['name' => 'Crew', 'category' => ResourceCategory::Action, 'unit' => 'crew', 'starting' => 3, 'min' => 0, 'max' => 3, 'tradeable' => false, 'accumulative' => false, 'description' => 'Placed to take an action, and returned at the turn of the tide.'],
                    ['name' => 'Coin', 'category' => ResourceCategory::Currency, 'unit' => 'coin', 'starting' => 5, 'min' => 0, 'convertible' => true, 'description' => 'Earned from berths held, spent on dockhands.'],
                    ['name' => 'Crate', 'category' => ResourceCategory::Material, 'unit' => 'crate', 'starting' => 0, 'min' => 0, 'max' => 12, 'convertible' => true, 'description' => 'Unloaded cargo. The thing contracts are paid in.'],
                    ['name' => 'Reputation', 'category' => ResourceCategory::Victory, 'unit' => 'point', 'starting' => 0, 'min' => 0, 'tradeable' => false, 'spendable' => false, 'description' => 'The score.'],
                ],
                'flows' => [
                    ['resource' => 'Crew', 'name' => 'Crews return with the tide', 'type' => ResourceFlowType::Generation, 'amount' => 3, 'condition' => 'At the start of each tide cycle.'],
                    ['resource' => 'Crew', 'name' => 'Crew placed', 'type' => ResourceFlowType::Consumption, 'amount' => 1, 'condition' => 'Each time a crew is sent to a space.'],
                    ['resource' => 'Coin', 'name' => 'Harbour fee', 'type' => ResourceFlowType::Generation, 'amount' => 2, 'condition' => 'Each round, for every berth you hold.'],
                    ['resource' => 'Crate', 'name' => 'Cargo unloaded', 'type' => ResourceFlowType::Generation, 'amount' => 2, 'condition' => 'Per crane action.'],
                    ['resource' => 'Crate', 'name' => 'Crates spent on a contract', 'type' => ResourceFlowType::Consumption, 'amount' => 3, 'condition' => 'Per contract fulfilled.'],
                    ['resource' => 'Reputation', 'name' => 'Contract fulfilled', 'type' => ResourceFlowType::Reward, 'amount' => 4, 'condition' => 'Per contract completed.'],
                ],
                'actions' => [
                    [
                        'name' => 'Berth a ship',
                        'description' => 'Bring a waiting ship into an open berth.',
                        'costs' => [['resource' => 'Crew', 'amount' => 1]],
                        'rewards' => [['resource' => 'Crate', 'amount' => 1]],
                    ],
                    [
                        'name' => 'Work a crane',
                        'description' => 'Unload a berthed ship.',
                        'costs' => [['resource' => 'Crew', 'amount' => 1]],
                        'rewards' => [['resource' => 'Crate', 'amount' => 2]],
                    ],
                    [
                        'name' => 'Fulfil a contract',
                        'description' => 'Hand crates over against a contract from the shared row.',
                        'costs' => [['resource' => 'Crate', 'amount' => 3]],
                        'rewards' => [['resource' => 'Reputation', 'amount' => 4], ['resource' => 'Coin', 'amount' => 2]],
                    ],
                ],
                'variables' => [
                    ['name' => 'Starting coin', 'category' => BalanceVariableCategory::StartingValue, 'resource' => 'Coin', 'value' => 5, 'min' => 0, 'max' => 12, 'step' => 1, 'unit' => 'coin'],
                    ['name' => 'Crane yield', 'category' => BalanceVariableCategory::Reward, 'action' => 'Work a crane', 'value' => 2, 'min' => 1, 'max' => 4, 'step' => 1, 'unit' => 'crate'],
                    ['name' => 'Contract reputation', 'category' => BalanceVariableCategory::Reward, 'action' => 'Fulfil a contract', 'value' => 4, 'min' => 2, 'max' => 8, 'step' => 1, 'unit' => 'point'],
                ],
                'assumptions' => [
                    ['title' => 'Four contracts on the row is enough choice', 'description' => 'Assumed rather than tested. It is the assumption the three-pile rework eventually replaced.', 'category' => AssumptionCategory::Complexity, 'confidence' => AssumptionConfidence::Low],
                ],
                'observations' => [
                    ['title' => 'Contracts were never contested', 'observation' => 'Across four sessions nobody took a contract to keep it from somebody else. A shared row makes every contract equally available, so taking one is never taking it from anyone.', 'source' => ObservationSourceType::Playtest, 'reference' => 'Does the tide board read at a glance?', 'severity' => ObservationSeverity::High, 'seen' => 104],
                ],
                'snapshots' => [
                    ['name' => 'Economy before the contract rework', 'description' => 'Taken the day the three-pile work started, so there is something to compare the rework against.', 'taken' => 46],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'harbourmaster',
                'version' => 3,
                'name' => 'Contract rework economy',
                'description' => 'The live numbers: three contract piles, a tide that is itself a tracked '
                    .'resource, and a dockhand market nobody is sure is load-bearing.',
                'status' => BalanceProfileStatus::Active,
                'by' => 'devin@lanternandanvil.test',
                'created' => 43,
                'touched' => 3,
                'resources' => [
                    ['name' => 'Crew', 'category' => ResourceCategory::Action, 'unit' => 'crew', 'starting' => 3, 'min' => 0, 'max' => 3, 'tradeable' => false, 'accumulative' => false, 'description' => 'Placed to take an action. Returned at the turn of the tide, never carried over.'],
                    ['name' => 'Coin', 'category' => ResourceCategory::Currency, 'unit' => 'coin', 'starting' => 5, 'min' => 0, 'convertible' => true, 'description' => 'Earned from berths held. The only sink is the dockhand market, which is why it piles up.'],
                    ['name' => 'Crate', 'category' => ResourceCategory::Material, 'unit' => 'crate', 'starting' => 0, 'min' => 0, 'max' => 12, 'convertible' => true, 'description' => 'Unloaded cargo, and the currency contracts are actually paid in.'],
                    ['name' => 'Timber', 'category' => ResourceCategory::Material, 'unit' => 'timber', 'starting' => 2, 'min' => 0, 'max' => 8, 'convertible' => true, 'description' => 'A second good, traded at the yard or spent on dockhands.'],
                    ['name' => 'Contract', 'category' => ResourceCategory::Progression, 'unit' => 'contract', 'starting' => 0, 'min' => 0, 'max' => 6, 'tradeable' => false, 'description' => 'A held contract, taken from one of the three piles and not yet paid.'],
                    ['name' => 'Tide step', 'category' => ResourceCategory::Capacity, 'unit' => 'step', 'starting' => 0, 'min' => 0, 'max' => 20, 'tradeable' => false, 'spendable' => false, 'description' => 'How far the tide has run. The clock every other number is read against.'],
                    ['name' => 'Reputation', 'category' => ResourceCategory::Victory, 'unit' => 'point', 'starting' => 0, 'min' => 0, 'tradeable' => false, 'spendable' => false, 'description' => 'The score.'],
                ],
                'flows' => [
                    ['resource' => 'Crew', 'name' => 'Crews return with the tide', 'type' => ResourceFlowType::Generation, 'amount' => 3, 'condition' => 'At the start of each tide cycle.'],
                    ['resource' => 'Crew', 'name' => 'Crew placed', 'type' => ResourceFlowType::Consumption, 'amount' => 1, 'condition' => 'Each time a crew is sent to a space.'],
                    ['resource' => 'Coin', 'name' => 'Harbour fee', 'type' => ResourceFlowType::Generation, 'amount' => 2, 'condition' => 'Each round, for every berth you hold.'],
                    ['resource' => 'Coin', 'name' => 'Dockhand wages', 'type' => ResourceFlowType::Consumption, 'amount' => 1, 'condition' => 'Per dockhand, at the end of each cycle.'],
                    ['resource' => 'Crate', 'name' => 'Cargo unloaded', 'type' => ResourceFlowType::Generation, 'amount' => 2, 'condition' => 'Per crane action.'],
                    ['resource' => 'Crate', 'name' => 'Crates spent on a contract', 'type' => ResourceFlowType::Consumption, 'amount' => 3, 'condition' => 'Per contract fulfilled.'],
                    ['resource' => 'Crate', 'name' => 'Spoilage', 'type' => ResourceFlowType::Loss, 'amount' => 1, 'condition' => 'Any crates held above the spoilage threshold at the end of a cycle. Has not triggered in a real game yet.'],
                    ['resource' => 'Timber', 'name' => 'Timber traded at the yard', 'type' => ResourceFlowType::Conversion, 'amount' => 2, 'condition' => 'One timber becomes two coin, once per round.'],
                    ['resource' => 'Tide step', 'name' => 'Tide advances', 'type' => ResourceFlowType::Generation, 'amount' => 1, 'condition' => 'Once per round, unmissably.'],
                    ['resource' => 'Reputation', 'name' => 'Contract fulfilled', 'type' => ResourceFlowType::Reward, 'amount' => 4, 'condition' => 'Per contract completed.'],
                    ['resource' => 'Reputation', 'name' => 'Ship stranded', 'type' => ResourceFlowType::Penalty, 'amount' => 2, 'condition' => 'Per ship that leaves still loaded.'],
                ],
                'actions' => [
                    [
                        'name' => 'Berth a ship',
                        'description' => 'Bring a waiting ship into an open berth before the tide closes it.',
                        'costs' => [['resource' => 'Crew', 'amount' => 1]],
                        'rewards' => [['resource' => 'Crate', 'amount' => 2, 'min' => 1, 'max' => 3]],
                        'effects' => [
                            ['type' => ActionEffectType::Unlock, 'target' => 'The crane at that berth', 'value' => 1, 'description' => 'A berthed ship is what makes its crane usable at all.'],
                        ],
                    ],
                    [
                        'name' => 'Work a crane',
                        'description' => 'Unload a berthed ship, one hold at a time.',
                        'costs' => [['resource' => 'Crew', 'amount' => 1], ['resource' => 'Coin', 'amount' => 1]],
                        'rewards' => [['resource' => 'Crate', 'amount' => 2]],
                    ],
                    [
                        'name' => 'Take a contract',
                        'description' => 'Take the top card of one of the three piles.',
                        'costs' => [['resource' => 'Crew', 'amount' => 1]],
                        'rewards' => [['resource' => 'Contract', 'amount' => 1]],
                        'effects' => [
                            ['type' => ActionEffectType::Block, 'target' => 'That pile, for the rest of the round', 'description' => 'The denial the whole rework was for: a pile taken from is a pile nobody else reads this round.'],
                        ],
                    ],
                    [
                        'name' => 'Fulfil a contract',
                        'description' => 'Hand crates over against a contract you hold.',
                        'costs' => [['resource' => 'Crate', 'amount' => 3, 'min' => 2, 'max' => 4]],
                        'rewards' => [['resource' => 'Reputation', 'amount' => 4], ['resource' => 'Coin', 'amount' => 2]],
                        'effects' => [
                            ['type' => ActionEffectType::ResourceModifier, 'target' => 'Contract', 'value' => -1, 'description' => 'The contract leaves your player area when it is paid.'],
                        ],
                    ],
                    [
                        'name' => 'Hire a dockhand',
                        'description' => 'Add a fourth pair of hands for the rest of the game.',
                        'costs' => [['resource' => 'Coin', 'amount' => 4], ['resource' => 'Timber', 'amount' => 1]],
                        'effects' => [
                            ['type' => ActionEffectType::CapacityModifier, 'target' => 'Crews available each cycle', 'value' => 1, 'description' => 'The only thing coin is really for, and the subsystem nobody is sure is load-bearing.'],
                        ],
                    ],
                    [
                        'name' => 'Turn the tide',
                        'description' => 'Push the tide on a step early, closing berths ahead of schedule.',
                        'costs' => [['resource' => 'Crew', 'amount' => 1]],
                        'rewards' => [['resource' => 'Tide step', 'amount' => 1]],
                        'effects' => [
                            ['type' => ActionEffectType::Block, 'target' => 'Berths on the falling side of the tide', 'description' => 'Spiteful, occasionally correct, and the most-discussed action in the game.'],
                        ],
                    ],
                ],
                'variables' => [
                    ['name' => 'Starting coin', 'category' => BalanceVariableCategory::StartingValue, 'resource' => 'Coin', 'value' => 5, 'min' => 0, 'max' => 12, 'step' => 1, 'unit' => 'coin'],
                    ['name' => 'Crews per player', 'category' => BalanceVariableCategory::StartingValue, 'resource' => 'Crew', 'value' => 3, 'min' => 2, 'max' => 5, 'step' => 1, 'unit' => 'crew'],
                    ['name' => 'Crane yield', 'category' => BalanceVariableCategory::Reward, 'action' => 'Work a crane', 'value' => 2, 'min' => 1, 'max' => 4, 'step' => 1, 'unit' => 'crate'],
                    ['name' => 'Contract crate cost', 'category' => BalanceVariableCategory::Cost, 'action' => 'Fulfil a contract', 'value' => 3, 'min' => 2, 'max' => 5, 'step' => 1, 'unit' => 'crate'],
                    ['name' => 'Contract reputation', 'category' => BalanceVariableCategory::Reward, 'action' => 'Fulfil a contract', 'value' => 4, 'min' => 2, 'max' => 8, 'step' => 1, 'unit' => 'point'],
                    ['name' => 'Harbour fee per berth', 'category' => BalanceVariableCategory::Production, 'resource' => 'Coin', 'value' => 2, 'min' => 0, 'max' => 4, 'step' => 1, 'unit' => 'coin'],
                    ['name' => 'Dockhand cost', 'category' => BalanceVariableCategory::Cost, 'action' => 'Hire a dockhand', 'value' => 4, 'min' => 2, 'max' => 8, 'step' => 1, 'unit' => 'coin'],
                    ['name' => 'Crate spoilage threshold', 'category' => BalanceVariableCategory::Threshold, 'resource' => 'Crate', 'value' => 8, 'min' => 4, 'max' => 12, 'step' => 1, 'unit' => 'crate', 'description' => 'Crates above this spoil at the end of a cycle. No session has ever reached it.'],
                    ['name' => 'Rounds per tide cycle', 'category' => BalanceVariableCategory::Timing, 'value' => 5, 'min' => 3, 'max' => 6, 'step' => 1, 'unit' => 'round'],
                    ['name' => 'Tide cycles per game', 'category' => BalanceVariableCategory::Timing, 'value' => 4, 'min' => 3, 'max' => 5, 'step' => 1, 'unit' => 'cycle', 'description' => 'The number the length iteration is proposing to take to three.'],
                    ['name' => 'Stranding penalty', 'category' => BalanceVariableCategory::Cost, 'resource' => 'Reputation', 'value' => 2, 'min' => 0, 'max' => 5, 'step' => 1, 'unit' => 'point'],
                    ['name' => 'Chance a ship arrives loaded', 'category' => BalanceVariableCategory::Probability, 'value' => '0.650000', 'min' => '0.000000', 'max' => '1.000000', 'step' => '0.050000', 'description' => 'Read off the ship deck rather than rolled. Kept here because the whole economy is sized against it.'],
                ],
                'scenarios' => [
                    [
                        'name' => 'Two players',
                        'description' => 'The tightest count: every pile is contested every round, so the '
                            .'cycle is shorter and crates spoil sooner.',
                        'status' => BalanceScenarioStatus::Active,
                        'overrides' => [
                            'Rounds per tide cycle' => 4,
                            'Crate spoilage threshold' => 6,
                        ],
                    ],
                    [
                        'name' => 'Four players, three cycles',
                        'description' => 'The proposal from the length iteration: one cycle fewer, with the '
                            .'end-game bonuses worth more to make up for it.',
                        'status' => BalanceScenarioStatus::Active,
                        'overrides' => [
                            'Tide cycles per game' => 3,
                            'Contract reputation' => 5,
                        ],
                    ],
                    [
                        'name' => 'Five players with a harbourmaster',
                        'description' => 'Sketched for a playtest that has not run yet. Fewer crews each, a '
                            .'longer cycle, and a fifth seat that places none.',
                        'status' => BalanceScenarioStatus::Draft,
                        'overrides' => [
                            'Crews per player' => 2,
                            'Rounds per tide cycle' => 6,
                        ],
                    ],
                ],
                'assumptions' => [
                    ['title' => 'Crates are the bottleneck, not coin', 'description' => 'Every session so far has ended with players holding coin they could not spend and short of the crates they wanted. The economy is sized on the assumption that this is the interesting scarcity.', 'category' => AssumptionCategory::Economy, 'confidence' => AssumptionConfidence::High],
                    ['title' => 'Players will spend most of their coin on dockhands', 'description' => 'This is what the dockhand market is for. It has not happened in any recorded session.', 'category' => AssumptionCategory::Economy, 'confidence' => AssumptionConfidence::Low],
                    ['title' => 'A tide cycle is five rounds because four feels rushed', 'description' => 'Never tested directly. The length work is about to test it by accident.', 'category' => AssumptionCategory::Pacing, 'confidence' => AssumptionConfidence::Low],
                    ['title' => 'Players read the tide before placing', 'description' => 'Observed directly in four sessions: players lean over to check the board before choosing.', 'category' => AssumptionCategory::PlayerBehaviour, 'confidence' => AssumptionConfidence::High],
                    ['title' => 'End-game bonuses are enough of a catch-up', 'description' => 'Two sessions have ended within four points from a nine point gap. Two is not many.', 'category' => AssumptionCategory::Progression, 'confidence' => AssumptionConfidence::Medium],
                    ['title' => 'Six held contracts is enough choice for four players', 'description' => 'Chosen because it fits the player board, which is not a design reason.', 'category' => AssumptionCategory::Complexity, 'confidence' => AssumptionConfidence::Medium],
                ],
                'observations' => [
                    ['title' => 'Coin accumulates with nowhere to go', 'observation' => 'Players finished the four-player session holding between nine and fourteen coin. The only sink is the dockhand market, and three of four players never used it.', 'source' => ObservationSourceType::Playtest, 'reference' => 'Are three contract piles enough of a denial decision?', 'severity' => ObservationSeverity::Medium, 'seen' => 32],
                    ['title' => 'Spoilage has never triggered', 'observation' => 'The threshold is eight crates. The highest anyone has held at the end of a cycle across nine recorded sessions is five.', 'source' => ObservationSourceType::Session, 'reference' => 'Nine sessions, v2 and v3', 'severity' => ObservationSeverity::Low, 'seen' => 27],
                    ['title' => 'Four-player games run over the stated length', 'observation' => 'Eighty-eight, ninety-two and eighty-one minutes against a stated maximum of seventy-five. It has come back in five sessions running.', 'source' => ObservationSourceType::Playtest, 'reference' => 'Does the four-player game finish inside seventy-five minutes?', 'severity' => ObservationSeverity::Critical, 'seen' => 6],
                    ['title' => 'End-game bonuses are worth more in a short cycle', 'observation' => 'Shortening the fourth cycle raised the proportion of the final score coming from bonuses, because there were fewer rounds to earn crates in. Nobody designed that.', 'source' => ObservationSourceType::Calculation, 'reference' => 'Turn timings, sessions 1 to 6', 'severity' => ObservationSeverity::High, 'seen' => 5],
                    ['title' => 'The dockhand market is not load-bearing', 'observation' => 'Removed and re-added twice with no measurable effect on scores, length or the decisions players describe afterwards.', 'source' => ObservationSourceType::Review, 'reference' => 'System design review, v3', 'severity' => ObservationSeverity::High, 'seen' => 21],
                    ['title' => 'Denial is constant at two players', 'observation' => 'Every pile is contested every round, so taking a contract stops being an occasional decision and becomes the whole of the turn.', 'source' => ObservationSourceType::Playtest, 'reference' => 'Are three contract piles enough of a denial decision?', 'severity' => ObservationSeverity::Info, 'seen' => 26],
                ],
                'snapshots' => [
                    ['name' => 'Economy as taken to the length playtests', 'description' => 'The numbers the four-player timing sessions were run against, frozen before anything is changed in response to them.', 'taken' => 15],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'kiln',
                'version' => 2,
                'name' => 'First pass at the clay',
                'description' => 'Barely an economy yet. Three resources, two actions, and no ending — which '
                    .'is exactly what a profile looks like on the day somebody starts one.',
                'status' => BalanceProfileStatus::Draft,
                'by' => 'devin@lanternandanvil.test',
                'created' => 24,
                'touched' => 7,
                'resources' => [
                    ['name' => 'Clay', 'category' => ResourceCategory::Material, 'unit' => 'piece', 'starting' => 6, 'min' => 0, 'max' => 6, 'tradeable' => false, 'description' => 'The whole supply. When it runs out the game probably ends, which is not written down anywhere yet.'],
                    ['name' => 'Heat', 'category' => ResourceCategory::Capacity, 'unit' => 'step', 'starting' => 0, 'min' => 0, 'max' => 8, 'tradeable' => false, 'spendable' => false, 'description' => 'Rises with every piece loaded. Past the cracking point, everything inside is lost.'],
                    ['name' => 'Fired piece', 'category' => ResourceCategory::Victory, 'unit' => 'piece', 'starting' => 0, 'min' => 0, 'tradeable' => false, 'spendable' => false, 'description' => 'A piece that survived its firing. The score, provisionally.'],
                ],
                'flows' => [
                    ['resource' => 'Clay', 'name' => 'Piece loaded', 'type' => ResourceFlowType::Consumption, 'amount' => 1, 'condition' => 'Each time a piece goes into a kiln.'],
                    ['resource' => 'Heat', 'name' => 'Kiln warms', 'type' => ResourceFlowType::Generation, 'amount' => 1, 'condition' => 'Each piece loaded raises the heat by one.'],
                    ['resource' => 'Fired piece', 'name' => 'Firing survived', 'type' => ResourceFlowType::Reward, 'amount' => 1, 'condition' => 'Per piece in the kiln when it is fired below the cracking point.'],
                ],
                'actions' => [
                    [
                        'name' => 'Load a piece',
                        'description' => 'Put a piece of clay into a kiln slot. It cannot come back out.',
                        'costs' => [['resource' => 'Clay', 'amount' => 1]],
                        'effects' => [
                            ['type' => ActionEffectType::ResourceModifier, 'target' => 'Heat', 'value' => 1, 'description' => 'Every load brings the kiln one step nearer cracking.'],
                        ],
                    ],
                    [
                        'name' => 'Call the firing',
                        'description' => 'Open the kiln. Everything inside is scored or lost.',
                        'rewards' => [['resource' => 'Fired piece', 'amount' => 2, 'min' => 0, 'max' => 4]],
                    ],
                ],
                'variables' => [
                    ['name' => 'Starting clay', 'category' => BalanceVariableCategory::StartingValue, 'resource' => 'Clay', 'value' => 6, 'min' => 4, 'max' => 10, 'step' => 1, 'unit' => 'piece'],
                    ['name' => 'Slots per kiln', 'category' => BalanceVariableCategory::Capacity, 'value' => 4, 'min' => 3, 'max' => 6, 'step' => 1, 'unit' => 'slot'],
                    ['name' => 'Heat that cracks a piece', 'category' => BalanceVariableCategory::Threshold, 'resource' => 'Heat', 'value' => 7, 'min' => 5, 'max' => 9, 'step' => 1, 'unit' => 'step'],
                ],
                'assumptions' => [
                    ['title' => 'The game ends when the clay runs out', 'description' => 'Proposed in the current iteration and not decided. Everything in this profile is sized as though it were true.', 'category' => AssumptionCategory::Progression, 'confidence' => AssumptionConfidence::Low],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::NIGHTSHIFT,
                'game' => 'ember-court',
                'version' => 3,
                'name' => 'Rulebook edition economy',
                'description' => 'A card game economy: four numbers and one token, all of them settled and all '
                    .'of them printed in the rulebook.',
                'status' => BalanceProfileStatus::Active,
                'by' => 'yusuf@nightshiftgames.test',
                'created' => 60,
                'touched' => 18,
                'resources' => [
                    ['name' => 'Favour', 'category' => ResourceCategory::Currency, 'unit' => 'favour', 'starting' => 1, 'min' => 0, 'max' => 3, 'tradeable' => false, 'description' => 'Spent to change a bid after hearing somebody else\'s. Never earned during a hand.'],
                    ['name' => 'Trick', 'category' => ResourceCategory::Progression, 'unit' => 'trick', 'starting' => 0, 'min' => 0, 'max' => 16, 'tradeable' => false, 'spendable' => false, 'description' => 'Tricks taken this hand, counted against the bid.'],
                    ['name' => 'Ember', 'category' => ResourceCategory::Other, 'unit' => 'token', 'starting' => 0, 'min' => 0, 'max' => 1, 'tradeable' => false, 'description' => 'One token, always held by exactly one player. Costs a point and pays a favour.'],
                    ['name' => 'Point', 'category' => ResourceCategory::Victory, 'unit' => 'point', 'starting' => 0, 'min' => 0, 'tradeable' => false, 'spendable' => false, 'description' => 'The score. First to fourteen at the end of a hand wins.'],
                ],
                'flows' => [
                    ['resource' => 'Favour', 'name' => 'Favour dealt with the hand', 'type' => ResourceFlowType::Generation, 'amount' => 1, 'condition' => 'Every player, at the start of each hand.'],
                    ['resource' => 'Favour', 'name' => 'Ember holder\'s extra favour', 'type' => ResourceFlowType::Generation, 'amount' => 1, 'condition' => 'The ember holder only, at the start of each hand.'],
                    ['resource' => 'Favour', 'name' => 'Favour spent on a bid', 'type' => ResourceFlowType::Consumption, 'amount' => 1, 'condition' => 'Per step a bid is moved after it has been made.'],
                    ['resource' => 'Trick', 'name' => 'Trick taken', 'type' => ResourceFlowType::Generation, 'amount' => 1, 'condition' => 'Per trick won.'],
                    ['resource' => 'Point', 'name' => 'Bid met exactly', 'type' => ResourceFlowType::Reward, 'amount' => 3, 'condition' => 'Only for a bid met exactly. Over and under both score nothing.'],
                    ['resource' => 'Point', 'name' => 'Holding the ember', 'type' => ResourceFlowType::Penalty, 'amount' => 1, 'condition' => 'At the end of every hand, to whoever holds it.'],
                ],
                'actions' => [
                    [
                        'name' => 'Bid',
                        'description' => 'Say out loud how many tricks you will take this hand.',
                        'effects' => [
                            ['type' => ActionEffectType::Block, 'target' => 'Lowering the bid without favour', 'description' => 'A bid is public and cannot be moved for free. That is the whole tension of the game.'],
                        ],
                    ],
                    [
                        'name' => 'Spend favour on a bid',
                        'description' => 'Move your bid one step in either direction after hearing another.',
                        'costs' => [['resource' => 'Favour', 'amount' => 1]],
                        'effects' => [
                            ['type' => ActionEffectType::ResourceModifier, 'target' => 'Your own bid', 'value' => 1, 'description' => 'One step per favour, up or down.'],
                        ],
                    ],
                    [
                        'name' => 'Take a trick',
                        'description' => 'Win the trick with the highest card of the led suit, or of the ember suit.',
                        'rewards' => [['resource' => 'Trick', 'amount' => 1]],
                    ],
                    [
                        'name' => 'Score the hand',
                        'description' => 'Compare tricks to bids, pay the ember penalty, and pass the ember on.',
                        'rewards' => [['resource' => 'Point', 'amount' => 3, 'min' => 0, 'max' => 3]],
                        'effects' => [
                            ['type' => ActionEffectType::ResourceModifier, 'target' => 'Ember', 'value' => 1, 'description' => 'The ember goes to whoever missed their bid by most, and it has to go to somebody.'],
                        ],
                    ],
                ],
                'variables' => [
                    ['name' => 'Starting favour', 'category' => BalanceVariableCategory::StartingValue, 'resource' => 'Favour', 'value' => 1, 'min' => 0, 'max' => 3, 'step' => 1, 'unit' => 'favour'],
                    ['name' => 'Ember holder favour', 'category' => BalanceVariableCategory::StartingValue, 'resource' => 'Favour', 'value' => 2, 'min' => 1, 'max' => 3, 'step' => 1, 'unit' => 'favour'],
                    ['name' => 'Points for a met bid', 'category' => BalanceVariableCategory::Reward, 'action' => 'Score the hand', 'value' => 3, 'min' => 1, 'max' => 5, 'step' => 1, 'unit' => 'point'],
                    ['name' => 'Ember penalty', 'category' => BalanceVariableCategory::Cost, 'resource' => 'Point', 'value' => 1, 'min' => 0, 'max' => 3, 'step' => 1, 'unit' => 'point'],
                    ['name' => 'Points to win', 'category' => BalanceVariableCategory::Threshold, 'resource' => 'Point', 'value' => 14, 'min' => 10, 'max' => 20, 'step' => 1, 'unit' => 'point'],
                    ['name' => 'Cards dealt per player', 'category' => BalanceVariableCategory::Timing, 'value' => 12, 'min' => 8, 'max' => 16, 'step' => 1, 'unit' => 'card'],
                ],
                'scenarios' => [
                    [
                        'name' => 'Three players',
                        'description' => 'A bigger hand each, and the ember returning to the same player more '
                            .'often than anybody would like.',
                        'status' => BalanceScenarioStatus::Active,
                        'overrides' => [
                            'Cards dealt per player' => 16,
                        ],
                    ],
                    [
                        'name' => 'Five players',
                        'description' => 'Shorter hands and a shorter game, because five hands of twelve runs '
                            .'past the forty-minute limit.',
                        'status' => BalanceScenarioStatus::Active,
                        'overrides' => [
                            'Cards dealt per player' => 10,
                            'Points to win' => 12,
                        ],
                    ],
                ],
                'assumptions' => [
                    ['title' => 'Missing a bid should be survivable, not punishing', 'description' => 'Missing scores nothing and hands over the ember, which pays a favour back. The whole catch-up rests on this being roughly break-even.', 'category' => AssumptionCategory::Progression, 'confidence' => AssumptionConfidence::High],
                    ['title' => 'Three players is supported rather than merely possible', 'description' => 'Four sessions, all fine, none the best game of the evening. The ember returning to the same player is the suspected reason.', 'category' => AssumptionCategory::Interaction, 'confidence' => AssumptionConfidence::Low],
                ],
                'observations' => [
                    ['title' => 'Final margins closed after the favour rework', 'observation' => 'Average margin across ten games fell from six points to 2.4, and four of the ten were decided on the last hand.', 'source' => ObservationSourceType::Calculation, 'reference' => 'Winning margin across ten games', 'severity' => ObservationSeverity::Info, 'seen' => 150],
                    ['title' => 'Players bid to lose on purpose', 'observation' => 'Blind testers found the play of bidding under what they could make, to hand the ember on, without anybody telling them it existed. It is now the best part of the game.', 'source' => ObservationSourceType::Playtest, 'reference' => 'Blind test: can four strangers learn it from the rulebook alone?', 'severity' => ObservationSeverity::Info, 'seen' => 20],
                ],
                'snapshots' => [
                    ['name' => 'Rulebook edition, as printed', 'description' => 'The numbers the written rulebook describes. Nothing changes from here without a new rulebook, so this is the one that has to stay readable.', 'taken' => 58],
                ],
            ],
        ];
    }

    /**
     * Write one profile and its whole configuration.
     *
     * @param  array<string, mixed>  $definition
     */
    private function profile(array $definition): void
    {
        $game = $this->game($definition['workspace'], $definition['game']);
        $version = $this->version($game, $definition['version']);
        $author = $this->user($definition['by']);

        $profile = BalanceProfile::query()->firstOrNew([
            'game_version_id' => $version->getKey(),
            'name' => $definition['name'],
        ]);

        $profile->fill([
            'name' => $definition['name'],
            'description' => $definition['description'],
        ]);

        $profile->game_version_id = $version->getKey();
        $profile->status = $definition['status'];
        $profile->created_by = $author->id;

        $this->stamp($profile, $this->daysAgo($definition['created'], 12), $this->daysAgo($definition['touched'], 12));
        $profile->save();

        $resources = [];

        foreach ($definition['resources'] as $position => $resource) {
            $resources[$resource['name']] = $this->resource($profile, $resource, $position + 1);
        }

        foreach ($definition['flows'] ?? [] as $position => $flow) {
            $this->flow($profile, $resources, $flow, $position + 1);
        }

        $actions = [];

        foreach ($definition['actions'] ?? [] as $position => $action) {
            $actions[$action['name']] = $this->action($profile, $resources, $action, $position + 1);
        }

        $variables = [];

        foreach ($definition['variables'] ?? [] as $variable) {
            $variables[$variable['name']] = $this->variable($profile, $resources, $actions, $variable);
        }

        foreach ($definition['scenarios'] ?? [] as $scenario) {
            $this->scenario($profile, $variables, $scenario, $author);
        }

        foreach ($definition['assumptions'] ?? [] as $offset => $assumption) {
            $this->assumption($profile, $assumption, $author, $definition['created'] - $offset);
        }

        foreach ($definition['observations'] ?? [] as $observation) {
            $this->observation($profile, $observation, $author);
        }

        foreach ($definition['snapshots'] ?? [] as $snapshot) {
            $this->snapshot($profile, $snapshot, $author);
        }
    }

    /**
     * The address a named thing is filed under inside a profile.
     *
     * Derived from the name where the name is in English, and taken from the definition where it
     * is not. These slugs are what the analyser's findings, a scenario's overrides and a snapshot
     * comparison all match on, and `Str::slug('سکه')` is `skh` — neither readable nor stable enough
     * to key a comparison against. The name is what the screen shows; the slug is what the data
     * agrees on.
     *
     * @param  array<string, mixed>  $definition  the resource, action or variable being written
     */
    protected function address(array $definition): string
    {
        return (string) ($definition['slug'] ?? Str::slug((string) $definition['name']));
    }

    /**
     * Write one resource.
     *
     * @param  array<string, mixed>  $definition
     */
    private function resource(BalanceProfile $profile, array $definition, int $position): ResourceType
    {
        $slug = $this->address($definition);

        $resource = ResourceType::query()->firstOrNew([
            'balance_profile_id' => $profile->getKey(),
            'slug' => $slug,
        ]);

        $resource->fill([
            'name' => $definition['name'],
            'description' => $definition['description'] ?? null,
            'unit' => $definition['unit'] ?? null,
        ]);

        $resource->balance_profile_id = $profile->getKey();
        $resource->slug = $slug;
        $resource->category = $definition['category'];
        $resource->is_tradeable = $definition['tradeable'] ?? true;
        $resource->is_accumulative = $definition['accumulative'] ?? true;
        $resource->is_spendable = $definition['spendable'] ?? true;
        $resource->is_convertible = $definition['convertible'] ?? false;
        $resource->min_value = $definition['min'] ?? null;
        $resource->max_value = $definition['max'] ?? null;
        $resource->starting_value = $definition['starting'] ?? null;
        $resource->position = $position;

        $this->stamp($resource, $profile->created_at);
        $resource->save();

        return $resource;
    }

    /**
     * Write one place a resource comes from or goes to.
     *
     * @param  array<string, ResourceType>  $resources
     * @param  array<string, mixed>  $definition
     */
    private function flow(BalanceProfile $profile, array $resources, array $definition, int $position): void
    {
        $flow = ResourceFlow::query()->firstOrNew([
            'balance_profile_id' => $profile->getKey(),
            'name' => $definition['name'],
        ]);

        $flow->fill([
            'name' => $definition['name'],
            'description' => $definition['description'] ?? null,
            'condition' => $definition['condition'] ?? null,
        ]);

        $flow->balance_profile_id = $profile->getKey();
        $flow->resource_type_id = $resources[$definition['resource']]->getKey();
        $flow->flow_type = $definition['type'];
        $flow->amount = $definition['amount'];
        $flow->position = $position;

        $this->stamp($flow, $profile->created_at);
        $flow->save();
    }

    /**
     * Write one action, with what it costs, gives and does.
     *
     * @param  array<string, ResourceType>  $resources
     * @param  array<string, mixed>  $definition
     */
    private function action(BalanceProfile $profile, array $resources, array $definition, int $position): EconomyAction
    {
        $slug = $this->address($definition);

        $action = EconomyAction::query()->firstOrNew([
            'balance_profile_id' => $profile->getKey(),
            'slug' => $slug,
        ]);

        $action->fill([
            'name' => $definition['name'],
            'description' => $definition['description'] ?? null,
        ]);

        $action->balance_profile_id = $profile->getKey();
        $action->slug = $slug;
        $action->position = $position;

        $this->stamp($action, $profile->created_at);
        $action->save();

        foreach ($definition['costs'] ?? [] as $cost) {
            $this->amount(ActionCost::class, $action, $resources, $cost, $profile);
        }

        foreach ($definition['rewards'] ?? [] as $reward) {
            $this->amount(ActionReward::class, $action, $resources, $reward, $profile);
        }

        foreach ($definition['effects'] ?? [] as $effect) {
            $this->effect($action, $effect, $profile);
        }

        return $action;
    }

    /**
     * Write one side of an action's exchange.
     *
     * Costs and rewards are the same three columns pointed in opposite
     * directions, and both are keyed by the pair their unique index is on, so
     * re-seeding corrects an amount rather than colliding with it.
     *
     * @param  class-string<ActionCost|ActionReward>  $type
     * @param  array<string, ResourceType>  $resources
     * @param  array<string, mixed>  $definition
     */
    private function amount(
        string $type,
        EconomyAction $action,
        array $resources,
        array $definition,
        BalanceProfile $profile,
    ): void {
        $resource = $resources[$definition['resource']];

        $row = $type::query()->firstOrNew([
            'action_id' => $action->getKey(),
            'resource_type_id' => $resource->getKey(),
        ]);

        $row->action_id = $action->getKey();
        $row->resource_type_id = $resource->getKey();
        $row->amount = $definition['amount'];
        $row->is_variable = isset($definition['min']) || isset($definition['max']);
        $row->min_amount = $definition['min'] ?? null;
        $row->max_amount = $definition['max'] ?? null;

        $this->stamp($row, $profile->created_at);
        $row->save();
    }

    /**
     * Write something an action does that is not an exchange.
     *
     * @param  array<string, mixed>  $definition
     */
    private function effect(EconomyAction $action, array $definition, BalanceProfile $profile): void
    {
        $effect = ActionEffect::query()->firstOrNew([
            'action_id' => $action->getKey(),
            'target' => $definition['target'],
        ]);

        $effect->fill([
            'target' => $definition['target'],
            'description' => $definition['description'] ?? null,
        ]);

        $effect->action_id = $action->getKey();
        $effect->effect_type = $definition['type'];
        $effect->value = $definition['value'] ?? null;

        $this->stamp($effect, $profile->created_at);
        $effect->save();
    }

    /**
     * Write one number somebody might want to turn.
     *
     * @param  array<string, ResourceType>  $resources
     * @param  array<string, EconomyAction>  $actions
     * @param  array<string, mixed>  $definition
     */
    private function variable(
        BalanceProfile $profile,
        array $resources,
        array $actions,
        array $definition,
    ): BalanceVariable {
        $slug = $this->address($definition);

        $variable = BalanceVariable::query()->firstOrNew([
            'balance_profile_id' => $profile->getKey(),
            'slug' => $slug,
        ]);

        $variable->fill([
            'name' => $definition['name'],
            'description' => $definition['description'] ?? null,
            'unit' => $definition['unit'] ?? null,
        ]);

        $variable->balance_profile_id = $profile->getKey();
        $variable->slug = $slug;
        $variable->resource_type_id = isset($definition['resource'])
            ? $resources[$definition['resource']]->getKey()
            : null;
        $variable->action_id = isset($definition['action'])
            ? $actions[$definition['action']]->getKey()
            : null;
        $variable->value = $definition['value'];
        $variable->min_value = $definition['min'] ?? null;
        $variable->max_value = $definition['max'] ?? null;
        $variable->step = $definition['step'] ?? null;
        $variable->category = $definition['category'];

        $this->stamp($variable, $profile->created_at);
        $variable->save();

        return $variable;
    }

    /**
     * Write one "what if", as overrides on top of the base numbers.
     *
     * Nothing here writes back to `balance_variables`. A scenario is a set of
     * different answers to the same questions, and the base profile is what it
     * is different from.
     *
     * @param  array<string, BalanceVariable>  $variables
     * @param  array<string, mixed>  $definition
     */
    private function scenario(
        BalanceProfile $profile,
        array $variables,
        array $definition,
        User $author,
    ): void {
        $scenario = BalanceScenario::query()->firstOrNew([
            'balance_profile_id' => $profile->getKey(),
            'name' => $definition['name'],
        ]);

        $scenario->fill([
            'name' => $definition['name'],
            'description' => $definition['description'] ?? null,
        ]);

        $scenario->balance_profile_id = $profile->getKey();
        $scenario->status = $definition['status'];
        $scenario->created_by = $author->id;

        $this->stamp($scenario, $profile->updated_at);
        $scenario->save();

        foreach ($definition['overrides'] as $name => $value) {
            $variable = $variables[$name];

            $override = ScenarioVariable::query()->firstOrNew([
                'scenario_id' => $scenario->getKey(),
                'balance_variable_id' => $variable->getKey(),
            ]);

            $override->scenario_id = $scenario->getKey();
            $override->balance_variable_id = $variable->getKey();
            $override->value = $value;

            $this->stamp($override, $profile->updated_at);
            $override->save();
        }
    }

    /**
     * Write one thing the numbers are taking on trust.
     *
     * @param  array<string, mixed>  $definition
     */
    private function assumption(
        BalanceProfile $profile,
        array $definition,
        User $author,
        int $daysAgo,
    ): void {
        $assumption = BalanceAssumption::query()->firstOrNew([
            'balance_profile_id' => $profile->getKey(),
            'title' => $definition['title'],
        ]);

        $assumption->fill([
            'title' => $definition['title'],
            'description' => $definition['description'] ?? null,
        ]);

        $assumption->balance_profile_id = $profile->getKey();
        $assumption->category = $definition['category'];
        $assumption->confidence = $definition['confidence'];
        $assumption->created_by = $author->id;

        $this->stamp($assumption, $this->daysAgo(max(1, $daysAgo), 13));
        $assumption->save();
    }

    /**
     * Write one thing the table turned out to do.
     *
     * @param  array<string, mixed>  $definition
     */
    private function observation(
        BalanceProfile $profile,
        array $definition,
        User $author,
    ): void {
        $observation = BalanceObservation::query()->firstOrNew([
            'balance_profile_id' => $profile->getKey(),
            'title' => $definition['title'],
        ]);

        $observation->fill([
            'title' => $definition['title'],
            'observation' => $definition['observation'],
            'source_reference' => $definition['reference'] ?? null,
        ]);

        $observation->balance_profile_id = $profile->getKey();
        $observation->source_type = $definition['source'];
        $observation->severity = $definition['severity'];
        $observation->created_by = $author->id;

        $this->stamp($observation, $this->daysAgo($definition['seen'], 20));
        $observation->save();
    }

    /**
     * Freeze the profile as it stands.
     *
     * Captured through `SnapshotWriter` rather than hand-written, so the stored
     * shape is the one the comparison screen knows how to read — and so a
     * seeded snapshot cannot drift out of step with the real thing when the
     * shape changes.
     *
     * Snapshots are immutable and have no `updated_at`, so this writes one only
     * where there is not one already.
     *
     * @param  array<string, mixed>  $definition
     */
    private function snapshot(
        BalanceProfile $profile,
        array $definition,
        User $author,
    ): void {
        $exists = BalanceSnapshot::query()
            ->where('balance_profile_id', $profile->getKey())
            ->where('name', $definition['name'])
            ->exists();

        if ($exists) {
            return;
        }

        $snapshot = new BalanceSnapshot;

        $snapshot->balance_profile_id = $profile->getKey();
        $snapshot->name = $definition['name'];
        $snapshot->description = $definition['description'] ?? null;
        $snapshot->snapshot_data = app(SnapshotWriter::class)->capture($profile->fresh());
        $snapshot->created_by = $author->id;
        $snapshot->created_at = $this->daysAgo($definition['taken'], 18);

        $snapshot->save();
    }
}
