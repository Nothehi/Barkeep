<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Modules\GameRules\Domain\Enums\ConditionOperator;
use Modules\GameRules\Domain\Enums\ConditionType;
use Modules\GameRules\Domain\Enums\EffectType;
use Modules\GameRules\Domain\Enums\GamePhaseType;
use Modules\GameRules\Domain\Enums\LogicOperator;
use Modules\GameRules\Domain\Enums\MechanicCategory;
use Modules\GameRules\Domain\Enums\ReferenceType;
use Modules\GameRules\Domain\Enums\RequirementType;
use Modules\GameRules\Domain\Enums\RuleActionType;
use Modules\GameRules\Domain\Enums\RuleSetStatus;
use Modules\GameRules\Domain\Enums\RuleStatus;
use Modules\GameRules\Domain\Enums\RuleType;
use Modules\GameRules\Domain\Enums\TriggerType;
use Modules\GameRules\Domain\Models\ConditionGroup;
use Modules\GameRules\Domain\Models\ConditionGroupCondition;
use Modules\GameRules\Domain\Models\DefeatCondition;
use Modules\GameRules\Domain\Models\GameEndCondition;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\PhaseTransition;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\GameRules\Domain\Models\RuleEffect;
use Modules\GameRules\Domain\Models\RuleMechanic;
use Modules\GameRules\Domain\Models\RuleReference;
use Modules\GameRules\Domain\Models\RuleRequirement;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\Models\RuleTrigger;
use Modules\GameRules\Domain\Models\VictoryCondition;
use Modules\GameRules\Infrastructure\Persistence\RuleSetCloner;
use Modules\Identity\Domain\Models\User;

/**
 * The rules behind three of the games.
 *
 * Every rule set hangs off a design version rather than off a game, which is
 * this module's architecture in one sentence: Harbourmaster's contracts came
 * from one shared row at v2 and from three piles at v3, and a playtest run
 * against v2 is only interpretable if the v2 rules are still there to read. So
 * Harbourmaster gets both — the v2 set archived, the v3 set in play — and
 * nothing copies the newer one back.
 *
 * It also gets a third: a draft cloned from the live rules, holding the length
 * iteration's three-cycle proposal. That is the whole lifecycle in one game.
 * An active rule set refuses every edit, so the only way to change the rules in
 * play is to copy them, change the copy and activate it — and a sample database
 * where nobody had ever done that would be teaching the wrong workflow.
 *
 * The rule sets are deliberately imperfect. Kiln's draft has an action nobody
 * can place in the turn, a condition nothing points at and no way to win, so
 * `RuleSetValidator` has one error and a handful of warnings to report — which
 * is correct. A half-written rule system is full of findings, and a sample
 * database where the analysis screen is empty would be teaching that the screen
 * does nothing.
 *
 * Where a rule action names something in the game's economy it stores a
 * *handle* and never a cost. `berth-a-ship` is the slug SampleEconomySeeder
 * wrote; what it costs stays in the balance profile, and the action screen reads
 * it live. Seeding "1 crew" here as well would be the one place in the sample
 * data where two records disagreed about the same number.
 */
class SampleRulesSeeder extends SampleSeeder
{
    /**
     * Seed the rule sets and everything written inside them.
     */
    public function run(): void
    {
        $count = 0;

        foreach ($this->ruleSets() as $definition) {
            isset($definition['cloneOf'])
                ? $this->clonedRuleSet($definition)
                : $this->ruleSet($definition);

            $count++;
        }

        $this->command->info("Seeded {$count} sample rule sets.");
    }

    /**
     * The rule systems themselves.
     *
     * Harbourmaster appears three times because its rules have a history worth
     * keeping: the v2 set is archived and still readable, the v3 set is the one
     * in play, and the v3 draft is the length iteration's proposal, cloned from
     * the live rules because that is the only way to change rules that are in
     * play.
     *
     * @return list<array<string, mixed>>
     */
    protected function ruleSets(): array
    {
        return [
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'harbourmaster',
                'version' => 2,
                'name' => 'Tide board rules',
                'description' => 'The rules as they stood while contracts were a single shared row of four '
                    .'cards. Kept because every playtest between the tide board and the contract rework was '
                    .'run under these, and the observations from those sessions only mean anything beside '
                    .'them.',
                'status' => RuleSetStatus::Archived,
                'by' => 'mara@lanternandanvil.test',
                'created' => 126,
                'touched' => 44,
                'mechanics' => [
                    ['name' => 'Worker placement', 'category' => MechanicCategory::Action, 'description' => 'Three crews each, placed one at a time onto spaces that close behind them.'],
                    ['name' => 'Tide track', 'category' => MechanicCategory::Progression, 'description' => 'A shared clock that opens and closes berths as it runs. Everything else is read against it.'],
                    ['name' => 'Set collection', 'category' => MechanicCategory::Scoring, 'description' => 'Contracts want crates in particular combinations rather than in bulk.'],
                ],
                'phases' => [
                    ['name' => 'Setup', 'type' => GamePhaseType::Setup, 'description' => 'Lay out the harbour, deal three contracts face up, and give every player three crews and five coin.'],
                    ['name' => 'Round start', 'type' => GamePhaseType::Round, 'description' => 'Advance the tide one step and open or close berths accordingly.'],
                    ['name' => 'Placement', 'type' => GamePhaseType::Action, 'description' => 'Players take turns placing one crew each until all crews are down.'],
                    ['name' => 'Resolution', 'type' => GamePhaseType::Resolution, 'description' => 'Work through the placed crews in harbour order rather than in turn order.'],
                    ['name' => 'Tide turn', 'type' => GamePhaseType::Cleanup, 'description' => 'Return every crew, pay harbour fees, and refill the contract row.'],
                    ['name' => 'Game end', 'type' => GamePhaseType::EndGame, 'description' => 'Score held contracts and reputation. Highest total wins.'],
                ],
                'conditions' => [
                    ['name' => 'All crews have been placed', 'type' => ConditionType::GameState, 'operator' => ConditionOperator::IsTrue, 'description' => 'Nobody has a crew left in hand.'],
                    ['name' => 'The fourth tide cycle is over', 'type' => ConditionType::Counter, 'operator' => ConditionOperator::GreaterThanOrEqual, 'value' => '4', 'description' => 'Counted on the tide board, not tracked separately.'],
                    ['name' => 'Reputation is the highest at the table', 'type' => ConditionType::Score, 'operator' => ConditionOperator::GreaterThan, 'value' => '0', 'description' => 'Ties go to whoever holds fewest unfulfilled contracts.'],
                ],
                'triggers' => [
                    ['name' => 'At the turn of the tide', 'type' => TriggerType::RoundEnd, 'description' => 'The one moment in the round when anything happens automatically.'],
                ],
                'transitions' => [
                    ['from' => 'Setup', 'to' => 'Round start'],
                    ['from' => 'Round start', 'to' => 'Placement'],
                    ['from' => 'Placement', 'to' => 'Resolution', 'condition' => 'All crews have been placed'],
                    ['from' => 'Resolution', 'to' => 'Tide turn'],
                    ['from' => 'Tide turn', 'to' => 'Game end', 'condition' => 'The fourth tide cycle is over', 'trigger' => 'At the turn of the tide'],
                    ['from' => 'Tide turn', 'to' => 'Round start', 'trigger' => 'At the turn of the tide'],
                ],
                'rules' => [
                    ['name' => 'Placing crews', 'type' => RuleType::Turn, 'phase' => 'Placement', 'description' => 'Starting with the first player and going clockwise, each player places one crew onto an open space. Play continues around the table until every crew is down.'],
                    ['name' => 'One crew to a space', 'parent' => 'Placing crews', 'type' => RuleType::Turn, 'phase' => 'Placement', 'description' => 'A space holds one crew. Once taken it is closed for the rest of the round, whoever took it.'],
                    ['name' => 'Crews come back with the tide', 'parent' => 'Placing crews', 'type' => RuleType::Turn, 'phase' => 'Tide turn', 'description' => 'Every crew returns at the turn of the tide. Nothing carries over, so a crew unspent is a crew wasted.'],
                    ['name' => 'Contracts', 'type' => RuleType::Resource, 'description' => 'Four contracts sit face up in a shared row. Any player may take any of them, and the row refills at the turn of the tide.'],
                    ['name' => 'Fulfilling a contract', 'parent' => 'Contracts', 'type' => RuleType::Scoring, 'phase' => 'Resolution', 'description' => 'Hand over the crates a contract names to score its reputation. A contract you cannot pay stays in your player area, worth nothing.'],
                    ['name' => 'The tide', 'type' => RuleType::General, 'description' => 'The tide advances one step every round and never goes back. Berths on the falling side close whether a ship is in them or not.'],
                    ['name' => 'Stranding', 'parent' => 'The tide', 'type' => RuleType::General, 'phase' => 'Tide turn', 'description' => 'A ship still loaded when its berth closes leaves stranded, and the player who berthed it loses two reputation.'],
                ],
                'actions' => [
                    ['name' => 'Berth a ship', 'type' => RuleActionType::Basic, 'phase' => 'Placement', 'economy' => 'berth-a-ship', 'description' => 'Bring a waiting ship into an open berth before the tide closes it.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'A crew still in hand.', 'value' => '1', 'resource' => 'crew'],
                        ['type' => RequirementType::Position, 'description' => 'A berth open on the current tide step.'],
                    ], 'effects' => [
                        ['type' => EffectType::Unlock, 'target' => 'The crane at that berth', 'description' => 'A berthed ship is what makes its crane usable at all.'],
                    ]],
                    ['name' => 'Work a crane', 'type' => RuleActionType::Resource, 'phase' => 'Placement', 'economy' => 'work-a-crane', 'description' => 'Unload a berthed ship, one hold at a time.', 'requirements' => [
                        ['type' => RequirementType::Ownership, 'description' => 'A berthed ship with cargo still in it.'],
                    ], 'effects' => [
                        ['type' => EffectType::Resource, 'target' => 'Crate', 'value' => '+2', 'resource' => 'crate', 'description' => 'The amount is the economy\'s. This says only that crates arrive.'],
                    ]],
                    ['name' => 'Take a contract', 'type' => RuleActionType::Card, 'phase' => 'Placement', 'description' => 'Take one of the four face-up contracts into your player area.', 'requirements' => [
                        ['type' => RequirementType::Card, 'description' => 'At least one contract face up in the row.'],
                    ], 'effects' => [
                        ['type' => EffectType::Draw, 'target' => 'Contract', 'value' => '1'],
                    ]],
                    ['name' => 'Fulfil a contract', 'type' => RuleActionType::Basic, 'phase' => 'Resolution', 'economy' => 'fulfil-a-contract', 'description' => 'Hand crates over against a contract you hold.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'The crates the contract names.', 'resource' => 'crate'],
                    ], 'effects' => [
                        ['type' => EffectType::Score, 'target' => 'Reputation', 'value' => '+4', 'resource' => 'reputation'],
                        ['type' => EffectType::Discard, 'target' => 'The contract card', 'value' => '1', 'description' => 'It leaves your player area when it is paid.'],
                    ]],
                ],
                'groups' => [
                    ['name' => 'The game is over', 'operator' => LogicOperator::And, 'description' => 'Both have to hold. The tide running out mid-cycle does not end anything.', 'conditions' => ['The fourth tide cycle is over', 'All crews have been placed']],
                ],
                'victory' => [
                    ['name' => 'Most reputation after four tide cycles', 'condition' => 'Reputation is the highest at the table', 'description' => 'Ties broken by fewest unfulfilled contracts held, then by most crates.'],
                ],
                'endings' => [
                    ['name' => 'The fourth tide cycle ends', 'condition' => 'The fourth tide cycle is over', 'description' => 'The game always runs its full length. There is no early finish.'],
                ],
                'references' => [
                    ['from' => 'Stranding', 'to' => 'The tide', 'type' => ReferenceType::DependsOn, 'description' => 'Stranding is only meaningful because the tide closes berths on its own.'],
                    ['from' => 'Fulfilling a contract', 'to' => 'Contracts', 'type' => ReferenceType::DependsOn],
                    ['from' => 'Crews come back with the tide', 'to' => 'One crew to a space', 'type' => ReferenceType::RelatedTo, 'description' => 'Read together, these two are the whole of why a round feels tight.'],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'harbourmaster',
                'version' => 3,
                'name' => 'Contract rework rules',
                'description' => 'The rules in play. Contracts come from three visible piles instead of one '
                    .'shared row, so taking the contract you want is also taking the one somebody else was '
                    .'building towards — and that denial is what the whole rework was for.',
                'status' => RuleSetStatus::Active,
                'by' => 'mara@lanternandanvil.test',
                'created' => 40,
                'touched' => 3,
                'mechanics' => [
                    ['name' => 'Worker placement', 'category' => MechanicCategory::Action, 'description' => 'Three crews each, placed one at a time onto spaces that close behind them.'],
                    ['name' => 'Contract drafting', 'category' => MechanicCategory::Card, 'description' => 'Three visible piles, top card only. Taking one closes the pile for the round.'],
                    ['name' => 'Tide track', 'category' => MechanicCategory::Progression, 'description' => 'A shared clock that opens and closes berths as it runs.'],
                    ['name' => 'Set collection', 'category' => MechanicCategory::Scoring, 'description' => 'Contracts want crates in particular combinations rather than in bulk.'],
                    ['name' => 'Take that', 'category' => MechanicCategory::PlayerInteraction, 'description' => 'Turning the tide early closes berths on somebody else. Spiteful, occasionally correct, and the most-discussed action in the game.'],
                ],
                'phases' => [
                    ['name' => 'Setup', 'type' => GamePhaseType::Setup, 'description' => 'Lay out the harbour, build the three contract piles, and give every player three crews, five coin and two timber.'],
                    ['name' => 'Tide cycle', 'type' => GamePhaseType::Round, 'description' => 'Five rounds, after which the tide turns and everything resets. Four cycles make a game.'],
                    ['name' => 'Round start', 'type' => GamePhaseType::Round, 'parent' => 'Tide cycle', 'description' => 'Advance the tide one step. Berths open and close with it.'],
                    ['name' => 'Placement', 'type' => GamePhaseType::Action, 'parent' => 'Tide cycle', 'description' => 'Players take turns placing one crew each until every crew is down.'],
                    ['name' => 'Resolution', 'type' => GamePhaseType::Resolution, 'parent' => 'Tide cycle', 'description' => 'Work through the placed crews in harbour order rather than in turn order.'],
                    ['name' => 'Cleanup', 'type' => GamePhaseType::Cleanup, 'parent' => 'Tide cycle', 'description' => 'Return crews, pay harbour fees, spoil crates above the threshold, and reopen the contract piles.'],
                    ['name' => 'Game end', 'type' => GamePhaseType::EndGame, 'description' => 'Score held contracts and reputation, then compare.'],
                ],
                'conditions' => [
                    ['name' => 'All crews have been placed', 'type' => ConditionType::GameState, 'operator' => ConditionOperator::IsTrue, 'description' => 'Nobody has a crew left in hand.'],
                    ['name' => 'The cycle has run five rounds', 'type' => ConditionType::Counter, 'operator' => ConditionOperator::GreaterThanOrEqual, 'value' => '5', 'description' => 'Read off the tide board. The number the length iteration is arguing about.'],
                    ['name' => 'Four cycles are over', 'type' => ConditionType::Counter, 'operator' => ConditionOperator::GreaterThanOrEqual, 'value' => '4'],
                    ['name' => 'Reputation is the highest at the table', 'type' => ConditionType::Score, 'operator' => ConditionOperator::GreaterThan, 'value' => '0', 'description' => 'Ties go to whoever holds fewest unfulfilled contracts.'],
                    ['name' => 'The contract deck is empty', 'type' => ConditionType::Card, 'operator' => ConditionOperator::IsTrue, 'description' => 'All three piles exhausted. Has happened once, in a five-player game.'],
                ],
                'triggers' => [
                    ['name' => 'At the turn of the tide', 'type' => TriggerType::RoundEnd, 'description' => 'End of the fifth round in a cycle.'],
                    ['name' => 'When a contract pile is taken from', 'type' => TriggerType::ActionExecuted, 'description' => 'Closes that pile for the rest of the round. The denial the rework exists for.'],
                ],
                'transitions' => [
                    ['from' => 'Setup', 'to' => 'Round start'],
                    ['from' => 'Round start', 'to' => 'Placement'],
                    ['from' => 'Placement', 'to' => 'Resolution', 'condition' => 'All crews have been placed'],
                    ['from' => 'Resolution', 'to' => 'Cleanup'],
                    ['from' => 'Cleanup', 'to' => 'Round start'],
                    ['from' => 'Cleanup', 'to' => 'Game end', 'condition' => 'Four cycles are over', 'trigger' => 'At the turn of the tide'],
                ],
                'rules' => [
                    ['name' => 'Placing crews', 'type' => RuleType::Turn, 'phase' => 'Placement', 'description' => 'Starting with the first player and going clockwise, each player places one crew onto an open space. Play continues around the table until every crew is down.'],
                    ['name' => 'One crew to a space', 'parent' => 'Placing crews', 'type' => RuleType::Turn, 'phase' => 'Placement', 'description' => 'A space holds one crew. Once taken it is closed for the rest of the round, whoever took it.'],
                    ['name' => 'Crews come back with the tide', 'parent' => 'Placing crews', 'type' => RuleType::Turn, 'phase' => 'Cleanup', 'description' => 'Every crew returns at the turn of the tide. Nothing carries over, so a crew unspent is a crew wasted.'],
                    ['name' => 'Contracts', 'type' => RuleType::Resource, 'description' => 'Three piles, face up, top card only. A pile taken from this round is closed until the next.'],
                    ['name' => 'Taking from a pile closes it', 'parent' => 'Contracts', 'type' => RuleType::PlayerInteraction, 'phase' => 'Placement', 'description' => 'The whole point of the rework: the card you take is also the card somebody else was reading, and the pile behind it goes with it.'],
                    ['name' => 'Fulfilling a contract', 'parent' => 'Contracts', 'type' => RuleType::Scoring, 'phase' => 'Resolution', 'description' => 'Hand over the crates a contract names to score its reputation. A contract you cannot pay stays in your player area, worth nothing.'],
                    ['name' => 'Six held contracts is the limit', 'parent' => 'Contracts', 'type' => RuleType::Resource, 'description' => 'Chosen because it fits the player board, which the studio knows is not a design reason.'],
                    ['name' => 'The tide', 'type' => RuleType::General, 'description' => 'The tide advances one step every round and never goes back. Berths on the falling side close whether a ship is in them or not.'],
                    ['name' => 'Stranding', 'parent' => 'The tide', 'type' => RuleType::General, 'phase' => 'Cleanup', 'description' => 'A ship still loaded when its berth closes leaves stranded, and the player who berthed it loses two reputation.'],
                    ['name' => 'Turning the tide early', 'parent' => 'The tide', 'type' => RuleType::PlayerInteraction, 'phase' => 'Placement', 'description' => 'A crew may push the tide on a step ahead of schedule, closing berths early on whoever is still holding one.'],
                    ['name' => 'Spoilage', 'type' => RuleType::Resource, 'phase' => 'Cleanup', 'description' => 'Crates above the spoilage threshold are lost at the end of a cycle. No recorded session has ever reached it.'],
                    ['name' => 'Setting up', 'type' => RuleType::Setup, 'phase' => 'Setup', 'description' => 'Six berths, three contract piles shuffled separately, three crews, five coin and two timber each. The tide starts at step zero.'],
                    ['name' => 'The dockhand market', 'type' => RuleType::Resource, 'status' => RuleStatus::Deprecated, 'description' => 'Kept for the record. Removed and re-added twice with no measurable effect on scores, length or the decisions players describe afterwards — the studio is fairly sure it is not load-bearing.'],
                ],
                'actions' => [
                    ['name' => 'Berth a ship', 'type' => RuleActionType::Basic, 'phase' => 'Placement', 'economy' => 'berth-a-ship', 'description' => 'Bring a waiting ship into an open berth before the tide closes it.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'A crew still in hand.', 'value' => '1', 'resource' => 'crew'],
                        ['type' => RequirementType::Position, 'description' => 'A berth open on the current tide step.'],
                    ], 'effects' => [
                        ['type' => EffectType::Unlock, 'target' => 'The crane at that berth', 'description' => 'A berthed ship is what makes its crane usable at all.'],
                    ]],
                    ['name' => 'Work a crane', 'type' => RuleActionType::Resource, 'phase' => 'Placement', 'economy' => 'work-a-crane', 'description' => 'Unload a berthed ship, one hold at a time.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'A crew still in hand.', 'value' => '1', 'resource' => 'crew'],
                        ['type' => RequirementType::Ownership, 'description' => 'A berthed ship with cargo still in it.'],
                    ], 'effects' => [
                        ['type' => EffectType::Resource, 'target' => 'Crate', 'value' => '+2', 'resource' => 'crate', 'description' => 'How many is the economy\'s to say. This says only that crates arrive.'],
                    ]],
                    ['name' => 'Take a contract', 'type' => RuleActionType::Card, 'phase' => 'Placement', 'economy' => 'take-a-contract', 'description' => 'Take the top card of one of the three piles.', 'requirements' => [
                        ['type' => RequirementType::Card, 'description' => 'A pile that has not been taken from this round.'],
                        ['type' => RequirementType::PlayerState, 'description' => 'Fewer than six contracts already held.', 'value' => '6'],
                    ], 'effects' => [
                        ['type' => EffectType::Draw, 'target' => 'Contract', 'value' => '1'],
                        ['type' => EffectType::Lock, 'target' => 'That pile, for the rest of the round', 'description' => 'The denial the whole rework was for.'],
                    ]],
                    ['name' => 'Fulfil a contract', 'type' => RuleActionType::Basic, 'phase' => 'Resolution', 'economy' => 'fulfil-a-contract', 'description' => 'Hand crates over against a contract you hold.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'The crates the contract names.', 'resource' => 'crate'],
                    ], 'effects' => [
                        ['type' => EffectType::Score, 'target' => 'Reputation', 'value' => '+4', 'resource' => 'reputation'],
                        ['type' => EffectType::Discard, 'target' => 'The contract card', 'value' => '1'],
                    ]],
                    ['name' => 'Hire a dockhand', 'type' => RuleActionType::Build, 'phase' => 'Placement', 'economy' => 'hire-a-dockhand', 'status' => RuleStatus::Deprecated, 'description' => 'Add a fourth pair of hands for the rest of the game. Kept for the record while the studio decides whether the market stays.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'Coin and a timber.', 'resource' => 'coin'],
                    ], 'effects' => [
                        ['type' => EffectType::Unlock, 'target' => 'A fourth crew each cycle'],
                    ]],
                    ['name' => 'Turn the tide', 'type' => RuleActionType::Special, 'phase' => 'Placement', 'economy' => 'turn-the-tide', 'description' => 'Push the tide on a step early, closing berths ahead of schedule.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'A crew still in hand.', 'value' => '1', 'resource' => 'crew'],
                    ], 'effects' => [
                        ['type' => EffectType::Resource, 'target' => 'Tide step', 'value' => '+1', 'resource' => 'tide-step'],
                        ['type' => EffectType::Lock, 'target' => 'Berths on the falling side of the tide'],
                    ]],
                    ['name' => 'Pass', 'type' => RuleActionType::Pass, 'phase' => 'Placement', 'description' => 'Decline to place, keeping the crew. Legal, occasionally correct, and almost never taken.', 'effects' => [
                        ['type' => EffectType::TurnChange, 'target' => 'The next player'],
                    ]],
                ],
                'groups' => [
                    ['name' => 'The game is over', 'operator' => LogicOperator::Or, 'description' => 'Either ends it. The empty deck has happened once, in a five-player game.', 'conditions' => ['Four cycles are over', 'The contract deck is empty']],
                ],
                'victory' => [
                    ['name' => 'Most reputation when the game ends', 'condition' => 'Reputation is the highest at the table', 'description' => 'Ties broken by fewest unfulfilled contracts held, then by most crates.'],
                ],
                'endings' => [
                    ['name' => 'Four tide cycles are over', 'condition' => 'Four cycles are over', 'description' => 'The ordinary ending, and the one the length iteration is proposing to shorten to three.'],
                    ['name' => 'The contract deck runs out', 'condition' => 'The contract deck is empty', 'description' => 'Finish the current cycle, then score. Written down after it happened in a five-player game and nobody knew what to do.'],
                ],
                'references' => [
                    ['from' => 'Stranding', 'to' => 'The tide', 'type' => ReferenceType::DependsOn, 'description' => 'Stranding is only meaningful because the tide closes berths on its own.'],
                    ['from' => 'Turning the tide early', 'to' => 'The tide', 'type' => ReferenceType::Modifies, 'description' => 'It is the one thing in the game that moves the clock out of its own schedule.'],
                    ['from' => 'Taking from a pile closes it', 'to' => 'Contracts', 'type' => ReferenceType::Modifies],
                    ['from' => 'Fulfilling a contract', 'to' => 'Contracts', 'type' => ReferenceType::DependsOn],
                    ['from' => 'Six held contracts is the limit', 'to' => 'Contracts', 'type' => ReferenceType::ExceptionTo, 'description' => 'Any pile, any time — except when your player board is already full.'],
                    ['from' => 'Spoilage', 'to' => 'Fulfilling a contract', 'type' => ReferenceType::RelatedTo, 'description' => 'Read together they are supposed to make hoarding crates a bad idea. In practice nobody has hoarded enough to find out.'],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'harbourmaster',
                'version' => 3,
                'cloneOf' => 'Contract rework rules',
                'name' => 'Three-cycle draft',
                'description' => 'The length iteration\'s proposal, copied from the live rules because there '
                    .'is no other way to change rules that are in play. Three cycles instead of four, with '
                    .'the end-game bonuses raised to make up for the rounds lost.',
                'by' => 'devin@lanternandanvil.test',
                'created' => 11,
                'touched' => 2,
                'edits' => [
                    'conditions' => [
                        'Four cycles are over' => ['name' => 'Three cycles are over', 'value' => '3', 'description' => 'The change the whole draft exists for.'],
                    ],
                    'rules' => [
                        ['name' => 'End-game bonuses are worth more', 'type' => RuleType::Scoring, 'phase' => 'Game end', 'description' => 'Each fulfilled contract type scores one more at the end than it did over four cycles. Written to hold the score range steady while the game gets shorter; nobody has checked whether it does.'],
                    ],
                    'rename' => [
                        'Four tide cycles are over' => 'Three tide cycles are over',
                    ],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'kiln',
                'version' => 2,
                'name' => 'Two-kiln rules',
                'description' => 'Barely a rule system yet. Two actions, a firing that resolves whenever a '
                    .'player says so, and no ending — which is exactly what a rule set looks like on the day '
                    .'somebody starts one.',
                'status' => RuleSetStatus::Draft,
                'by' => 'devin@lanternandanvil.test',
                'created' => 23,
                'touched' => 6,
                'mechanics' => [
                    ['name' => 'Push your luck', 'category' => MechanicCategory::Dice, 'description' => 'Every piece loaded raises the heat. One more is always worth it right up until it is not.'],
                ],
                'phases' => [
                    ['name' => 'Setup', 'type' => GamePhaseType::Setup, 'description' => 'Six clay each, two kilns, four slots apiece.'],
                    ['name' => 'Loading', 'type' => GamePhaseType::Action, 'description' => 'Players alternate putting pieces into either kiln.'],
                    ['name' => 'Firing', 'type' => GamePhaseType::Resolution, 'description' => 'Whoever called it opens the kiln. Everything inside is scored or lost.'],
                ],
                /*
                 * Written and then never pointed at, which is the shape the
                 * validator reports as an unused condition. Left in because it
                 * is what half-finished work actually looks like.
                 */
                'conditions' => [
                    ['name' => 'The kiln is past the cracking point', 'type' => ConditionType::Counter, 'operator' => ConditionOperator::GreaterThan, 'value' => '7', 'description' => 'Sketched during the two-kiln session and not wired to anything yet.'],
                ],
                'triggers' => [
                    ['name' => 'When somebody calls the firing', 'type' => TriggerType::PlayerEvent, 'description' => 'The only thing that ends a round. Not attached to a transition, because there is nowhere for play to go yet.'],
                ],
                'transitions' => [
                    ['from' => 'Setup', 'to' => 'Loading'],
                    ['from' => 'Loading', 'to' => 'Firing'],
                ],
                'rules' => [
                    ['name' => 'Loading a piece', 'type' => RuleType::Action, 'phase' => 'Loading', 'description' => 'Put one piece of clay into an open slot in either kiln. It cannot come back out.'],
                    ['name' => 'Looking in the other kiln', 'type' => RuleType::PlayerInteraction, 'phase' => 'Loading', 'description' => 'You may count the pieces in your opponent\'s kiln at any time. This is what turns the timing decision from arithmetic into a read on the other player.'],
                    ['name' => 'Heat', 'type' => RuleType::Resource, 'description' => null],
                    ['name' => 'Calling the firing', 'type' => RuleType::Action, 'phase' => 'Firing', 'description' => 'Either player may call the firing instead of loading. Everything in both kilns resolves at once.'],
                ],
                'actions' => [
                    ['name' => 'Load a piece', 'type' => RuleActionType::Resource, 'phase' => 'Loading', 'economy' => 'load-a-piece', 'description' => 'Put a piece of clay into a kiln slot.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'A piece of clay still in your supply.', 'resource' => 'clay'],
                    ], 'effects' => [
                        ['type' => EffectType::Resource, 'target' => 'Heat', 'value' => '+1', 'resource' => 'heat', 'description' => 'Every load brings the kiln one step nearer cracking.'],
                    ]],
                    /*
                     * No phase, which the validator reports as an error rather
                     * than a warning: an action nobody can place in the turn is
                     * an action nobody can take. It is also why this rule set
                     * cannot be activated, which is the point of seeding it.
                     */
                    ['name' => 'Call the firing', 'type' => RuleActionType::Special, 'economy' => 'call-the-firing', 'description' => 'Open the kilns and score what survived. Nobody has decided yet whether this is a placement or something you may do at any time.', 'effects' => [
                        ['type' => EffectType::EndGame, 'target' => 'The round'],
                    ]],
                ],
                'endings' => [
                    ['name' => 'The clay runs out', 'description' => 'Probably. Nobody has written a condition for it, and two sessions ended by agreement rather than by rule.'],
                ],
                'references' => [
                    ['from' => 'Calling the firing', 'to' => 'Heat', 'type' => ReferenceType::DependsOn, 'description' => 'The whole decision is a read on the heat, and the heat rule is a heading with nothing under it.'],
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::NIGHTSHIFT,
                'game' => 'ember-court',
                'version' => 3,
                'name' => 'Rulebook edition rules',
                'description' => 'The rules the written rulebook describes, word for word. Nothing changes '
                    .'from here without a new rulebook, which is why this is the set every later playtest is '
                    .'measured against.',
                'status' => RuleSetStatus::Active,
                'by' => 'yusuf@nightshiftgames.test',
                'created' => 60,
                'touched' => 19,
                'mechanics' => [
                    ['name' => 'Trick taking', 'category' => MechanicCategory::Card, 'description' => 'Four suits, follow if you can, highest of the led suit takes it.'],
                    ['name' => 'Bidding', 'category' => MechanicCategory::PlayerInteraction, 'description' => 'A bid before the hand, and only an exact bid scores.'],
                    ['name' => 'Hot potato', 'category' => MechanicCategory::PlayerInteraction, 'description' => 'The ember passes to whoever takes the last trick, and costs its holder a point every hand.'],
                ],
                'phases' => [
                    ['name' => 'Setup', 'type' => GamePhaseType::Setup, 'description' => 'Shuffle, deal twelve each, and give the ember to the player who dealt.'],
                    ['name' => 'Hand', 'type' => GamePhaseType::Round, 'description' => 'One deal, played out and scored. Hands continue until somebody reaches fourteen.'],
                    ['name' => 'Bidding', 'type' => GamePhaseType::Action, 'parent' => 'Hand', 'description' => 'Each player states how many tricks they intend to take.'],
                    ['name' => 'Tricks', 'type' => GamePhaseType::Turn, 'parent' => 'Hand', 'description' => 'Twelve tricks, led by whoever took the last one.'],
                    ['name' => 'Scoring', 'type' => GamePhaseType::Resolution, 'parent' => 'Hand', 'description' => 'Compare tricks taken against the bid, then pass the ember.'],
                    ['name' => 'Game end', 'type' => GamePhaseType::EndGame, 'description' => 'The hand in which somebody crosses fourteen is played to the end before anybody wins.'],
                ],
                'conditions' => [
                    ['name' => 'Every player has bid', 'type' => ConditionType::GameState, 'operator' => ConditionOperator::IsTrue],
                    ['name' => 'Twelve tricks have been played', 'type' => ConditionType::Counter, 'operator' => ConditionOperator::Equals, 'value' => '12'],
                    ['name' => 'Somebody has fourteen points', 'type' => ConditionType::Score, 'operator' => ConditionOperator::GreaterThanOrEqual, 'value' => '14', 'description' => 'Checked only at the end of a hand, never mid-trick.'],
                    ['name' => 'Highest score at the end of the hand', 'type' => ConditionType::Score, 'operator' => ConditionOperator::GreaterThan, 'value' => '0', 'description' => 'Ties are shared. The rulebook says so in one line and nobody has argued.'],
                ],
                'triggers' => [
                    ['name' => 'At the end of every hand', 'type' => TriggerType::RoundEnd, 'description' => 'Scoring, the ember penalty and the ember passing all hang off this one moment.'],
                    ['name' => 'When the last trick is taken', 'type' => TriggerType::PlayerEvent, 'description' => 'Whoever takes it holds the ember for the next hand.'],
                ],
                'transitions' => [
                    ['from' => 'Setup', 'to' => 'Bidding'],
                    ['from' => 'Bidding', 'to' => 'Tricks', 'condition' => 'Every player has bid'],
                    ['from' => 'Tricks', 'to' => 'Scoring', 'condition' => 'Twelve tricks have been played', 'trigger' => 'When the last trick is taken'],
                    ['from' => 'Scoring', 'to' => 'Game end', 'condition' => 'Somebody has fourteen points', 'trigger' => 'At the end of every hand'],
                    ['from' => 'Scoring', 'to' => 'Bidding', 'trigger' => 'At the end of every hand'],
                ],
                'rules' => [
                    ['name' => 'Dealing', 'type' => RuleType::Setup, 'phase' => 'Setup', 'description' => 'Twelve cards each from a forty-eight card deck. The dealer takes the ember and the deal passes left every hand.'],
                    ['name' => 'The bid', 'type' => RuleType::Turn, 'phase' => 'Bidding', 'description' => 'Starting left of the dealer, each player states a number from zero to twelve. Bids are public and are not adjusted for the total.'],
                    ['name' => 'Changing a bid', 'parent' => 'The bid', 'type' => RuleType::Special, 'phase' => 'Bidding', 'description' => 'A player may spend favour to move their own bid by one step per favour, after hearing somebody else\'s. This is the only use favour has.'],
                    ['name' => 'Following suit', 'type' => RuleType::Turn, 'phase' => 'Tricks', 'description' => 'Follow the led suit if you hold it. If you do not, play anything; there are no trumps.'],
                    ['name' => 'Taking a trick', 'parent' => 'Following suit', 'type' => RuleType::Turn, 'phase' => 'Tricks', 'description' => 'The highest card of the led suit takes the trick, and its taker leads the next.'],
                    ['name' => 'Scoring a hand', 'type' => RuleType::Scoring, 'phase' => 'Scoring', 'description' => 'A bid met exactly scores three points. A bid missed in either direction scores nothing at all — overshooting is no better than falling short.'],
                    ['name' => 'The ember', 'type' => RuleType::PlayerInteraction, 'description' => 'Exactly one player holds the ember at any time. It costs its holder a point at the end of every hand and pays them an extra favour at the start of the next.'],
                    ['name' => 'Passing the ember', 'parent' => 'The ember', 'type' => RuleType::PlayerInteraction, 'phase' => 'Scoring', 'description' => 'Whoever took the last trick of the hand takes the ember with it.'],
                    ['name' => 'Finishing the hand', 'type' => RuleType::EndGame, 'phase' => 'Game end', 'description' => 'The hand in which somebody crosses fourteen is played to the end. Two players have crossed on the same hand twice, and both times the higher total took it.'],
                ],
                'actions' => [
                    ['name' => 'Bid', 'type' => RuleActionType::Basic, 'phase' => 'Bidding', 'economy' => 'bid', 'description' => 'State how many tricks you intend to take.', 'requirements' => [
                        ['type' => RequirementType::Turn, 'description' => 'Your turn to bid, going clockwise from the dealer\'s left.'],
                    ], 'effects' => [
                        ['type' => EffectType::StateChange, 'target' => 'Your bid, for the rest of the hand'],
                    ]],
                    ['name' => 'Spend favour on a bid', 'type' => RuleActionType::Free, 'phase' => 'Bidding', 'economy' => 'spend-favour-on-a-bid', 'description' => 'Move your own bid one step per favour spent.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'Favour in hand.', 'value' => '1', 'resource' => 'favour'],
                        ['type' => RequirementType::GameState, 'description' => 'At least one other player has already bid.'],
                    ], 'effects' => [
                        ['type' => EffectType::Resource, 'target' => 'Favour', 'value' => '-1', 'resource' => 'favour'],
                        ['type' => EffectType::StateChange, 'target' => 'Your bid'],
                    ]],
                    ['name' => 'Play a card', 'type' => RuleActionType::Card, 'phase' => 'Tricks', 'economy' => 'take-a-trick', 'description' => 'Play one card to the trick, following the led suit if you can.', 'requirements' => [
                        ['type' => RequirementType::Turn, 'description' => 'Your turn in the trick.'],
                        ['type' => RequirementType::Card, 'description' => 'A card of the led suit, if you hold one.'],
                    ], 'effects' => [
                        ['type' => EffectType::Discard, 'target' => 'The card played', 'value' => '1'],
                    ]],
                    ['name' => 'Score the hand', 'type' => RuleActionType::Basic, 'phase' => 'Scoring', 'economy' => 'score-the-hand', 'description' => 'Compare tricks taken against the bid and pay out.', 'requirements' => [
                        ['type' => RequirementType::GameState, 'description' => 'All twelve tricks played.', 'value' => '12'],
                    ], 'effects' => [
                        ['type' => EffectType::Score, 'target' => 'Point', 'value' => '+3', 'resource' => 'point', 'description' => 'Only for a bid met exactly. Over and under both score nothing.'],
                        ['type' => EffectType::Damage, 'target' => 'The ember holder\'s score', 'value' => '-1', 'resource' => 'point'],
                        ['type' => EffectType::PhaseChange, 'target' => 'The next hand, or the end of the game'],
                    ]],
                ],
                'groups' => [
                    ['name' => 'The hand is finished', 'operator' => LogicOperator::And, 'conditions' => ['Every player has bid', 'Twelve tricks have been played']],
                ],
                'victory' => [
                    ['name' => 'Highest score once somebody passes fourteen', 'condition' => 'Highest score at the end of the hand', 'description' => 'Shared on a tie. The rulebook says so in one line and nobody has argued with it.'],
                ],
                'endings' => [
                    ['name' => 'A player reaches fourteen points', 'condition' => 'Somebody has fourteen points', 'description' => 'Checked at the end of a hand. The hand is always played out first.'],
                ],
                'references' => [
                    ['from' => 'Changing a bid', 'to' => 'The bid', 'type' => ReferenceType::Modifies, 'description' => 'The only way a bid moves once it has been stated.'],
                    ['from' => 'Passing the ember', 'to' => 'Taking a trick', 'type' => ReferenceType::DependsOn, 'description' => 'Which is why the last trick of a hand is played quite differently from the eleven before it.'],
                    ['from' => 'Scoring a hand', 'to' => 'The bid', 'type' => ReferenceType::DependsOn],
                    ['from' => 'Finishing the hand', 'to' => 'Scoring a hand', 'type' => ReferenceType::DependsOn],
                    ['from' => 'The ember', 'to' => 'Changing a bid', 'type' => ReferenceType::RelatedTo, 'description' => 'The ember costs a point and pays a favour, and favour only ever moves a bid. Read apart, neither looks like much.'],
                ],
            ],
        ];
    }

    /**
     * The address a named thing is filed under inside a rule set.
     *
     * Underscores rather than the hyphens GameDesign puts in URLs, because these
     * are `RuleSlug`s: they never appear in an address, and `RuleSlug::fromName`
     * is what a rule created through the interface would derive. Taken from the
     * definition where the name is not in English — `Str::slug('برپایی')` is
     * `brpayy`, which is stable but unreadable, and a Persian workshop's rule set
     * should not be keyed on something nobody can say out loud.
     *
     * @param  array<string, mixed>  $definition  the rule, mechanic, phase or action being written
     */
    protected function address(array $definition): string
    {
        return (string) ($definition['slug'] ?? Str::slug((string) $definition['name'], '_'));
    }

    /**
     * Copy a rule set the way a designer would, then make the changes they made.
     *
     * Through `RuleSetCloner` rather than by writing the copy out by hand, for
     * the reason `SampleEconomySeeder` puts snapshots through `SnapshotWriter`:
     * a hand-written clone would be somebody's idea of what cloning produces,
     * and would drift out of step with what the button actually does the first
     * time the cloner learned about a new table.
     *
     * It is also the honest fiction. An active rule set refuses every edit, so
     * the only way this draft could exist is that somebody pressed Clone — and a
     * sample database where nobody ever had would be teaching the wrong
     * workflow.
     *
     * Re-running does not re-take the copy. A clone is a copy of a moment, and
     * re-cloning would quietly pull later changes to the source into a draft
     * somebody has since edited.
     *
     * @param  array<string, mixed>  $definition
     */
    private function clonedRuleSet(array $definition): void
    {
        $game = $this->game($definition['workspace'], $definition['game']);
        $version = $this->version($game, $definition['version']);

        if (RuleSet::query()->where('game_version_id', $version->getKey())->where('name', $definition['name'])->exists()) {
            return;
        }

        $source = RuleSet::query()
            ->where('game_version_id', $version->getKey())
            ->where('name', $definition['cloneOf'])
            ->first();

        if ($source === null) {
            throw new \RuntimeException(
                "Sample rule set [{$definition['cloneOf']}] is missing, so [{$definition['name']}] cannot be cloned from it."
            );
        }

        $author = $this->user($definition['by']);

        $clone = app(RuleSetCloner::class)->clone(
            $source->setRelation('version', $version),
            $author,
            $definition['name'],
            $definition['description'],
        );

        $this->stamp($clone, $this->daysAgo($definition['created'], 11), $this->daysAgo($definition['touched'], 11));
        $clone->save();

        $this->applyEdits($clone, $definition['edits'] ?? [], $author);
    }

    /**
     * The changes the designer made to their copy.
     *
     * Deliberately small. What makes a clone worth seeding is not how different
     * it is but that it is *independent* — the length iteration moved one number
     * and added one rule, and the original is untouched.
     *
     * @param  array<string, mixed>  $edits
     */
    private function applyEdits(RuleSet $clone, array $edits, User $author): void
    {
        foreach ($edits['conditions'] ?? [] as $name => $change) {
            $condition = $clone->conditions()->where('name', $name)->first();

            if ($condition === null) {
                continue;
            }

            $condition->name = $change['name'] ?? $condition->name;
            $condition->value = $change['value'] ?? $condition->value;
            $condition->description = $change['description'] ?? $condition->description;
            $condition->save();
        }

        foreach ($edits['rename'] ?? [] as $from => $to) {
            $outcome = $clone->endConditions()->where('name', $from)->first();

            if ($outcome === null) {
                continue;
            }

            $outcome->name = $to;
            $outcome->save();
        }

        if (($edits['rules'] ?? []) !== []) {
            $phases = $clone->phases()->get()->keyBy('name')->all();

            $this->rules($clone, $phases, $edits['rules'], $author, $clone->rules()->count());
        }
    }

    /**
     * Write one rule set and everything inside it.
     *
     * The order below is the dependency order, and it is also the order a
     * designer works in: the stages of play first, then the sentences everything
     * points at, then the rules and the actions that sit in those stages, then
     * how play moves between them, then how the game ends.
     *
     * @param  array<string, mixed>  $definition
     */
    private function ruleSet(array $definition): void
    {
        $game = $this->game($definition['workspace'], $definition['game']);
        $version = $this->version($game, $definition['version']);
        $author = $this->user($definition['by']);

        $ruleSet = RuleSet::query()->firstOrNew([
            'game_version_id' => $version->getKey(),
            'name' => $definition['name'],
        ]);

        $ruleSet->fill([
            'name' => $definition['name'],
            'description' => $definition['description'],
        ]);

        $ruleSet->game_version_id = $version->getKey();
        $ruleSet->status = $definition['status'];
        $ruleSet->created_by = $author->id;

        /*
         * Lineage, resolved by name on the same version rather than by id. The
         * source has to appear earlier in `ruleSets()`, which is also the order
         * the studio wrote them in.
         */
        $ruleSet->cloned_from_rule_set_id = isset($definition['clonedFrom'])
            ? RuleSet::query()
                ->where('game_version_id', $version->getKey())
                ->where('name', $definition['clonedFrom'])
                ->value('id')
            : null;

        $this->stamp($ruleSet, $this->daysAgo($definition['created'], 11), $this->daysAgo($definition['touched'], 11));
        $ruleSet->save();

        $phases = $this->phases($ruleSet, $definition['phases'] ?? []);
        $conditions = $this->conditions($ruleSet, $definition['conditions'] ?? []);
        $triggers = $this->triggers($ruleSet, $definition['triggers'] ?? []);

        foreach ($definition['mechanics'] ?? [] as $position => $mechanic) {
            $this->mechanic($ruleSet, $mechanic, $position + 1);
        }

        $rules = $this->rules($ruleSet, $phases, $definition['rules'] ?? [], $author);
        $actions = $this->actions($ruleSet, $phases, $definition['actions'] ?? []);

        foreach ($definition['transitions'] ?? [] as $position => $transition) {
            $this->transition($ruleSet, $phases, $conditions, $triggers, $transition, $position + 1);
        }

        foreach ($definition['groups'] ?? [] as $group) {
            $this->group($ruleSet, $conditions, $group);
        }

        $this->outcomes($ruleSet, $conditions, $definition);

        foreach ($definition['references'] ?? [] as $reference) {
            $this->reference($rules, $reference);
        }

        unset($actions);
    }

    /**
     * Write the stages of play, then hang the nested ones off their parents.
     *
     * Two passes, because a phase's parent is another row in the same table and
     * naming it before it exists would need the list sorted by depth. The
     * position is the reading order of the definition, and it is a rule rather
     * than a preference: a turn structure read out of sequence is a different
     * turn structure, and the flow diagram takes the first phase as where play
     * begins when none is marked as setup.
     *
     * @param  list<array<string, mixed>>  $definitions
     * @return array<string, GamePhase>
     */
    private function phases(RuleSet $ruleSet, array $definitions): array
    {
        $phases = [];

        foreach ($definitions as $position => $definition) {
            $slug = $this->address($definition);

            $phase = GamePhase::query()->firstOrNew([
                'rule_set_id' => $ruleSet->getKey(),
                'slug' => $slug,
            ]);

            $phase->fill([
                'name' => $definition['name'],
                'description' => $definition['description'] ?? null,
            ]);

            $phase->rule_set_id = $ruleSet->getKey();
            $phase->slug = $slug;
            $phase->phase_type = $definition['type'];
            $phase->status = $definition['status'] ?? RuleStatus::Active;
            $phase->position = $position + 1;

            $this->stamp($phase, $ruleSet->created_at);
            $phase->save();

            $phases[$definition['name']] = $phase;
        }

        foreach ($definitions as $definition) {
            if (! isset($definition['parent'])) {
                continue;
            }

            $phase = $phases[$definition['name']];
            $phase->parent_phase_id = $phases[$definition['parent']]->getKey();
            $phase->save();
        }

        return $phases;
    }

    /**
     * Write the named sentences everything else points at.
     *
     * @param  list<array<string, mixed>>  $definitions
     * @return array<string, RuleCondition>
     */
    private function conditions(RuleSet $ruleSet, array $definitions): array
    {
        $conditions = [];

        foreach ($definitions as $definition) {
            $condition = RuleCondition::query()->firstOrNew([
                'rule_set_id' => $ruleSet->getKey(),
                'name' => $definition['name'],
            ]);

            $condition->fill([
                'name' => $definition['name'],
                'description' => $definition['description'] ?? null,
                'value' => $definition['value'] ?? null,
            ]);

            $condition->rule_set_id = $ruleSet->getKey();
            $condition->condition_type = $definition['type'];
            $condition->operator = $definition['operator'];

            $this->stamp($condition, $ruleSet->created_at);
            $condition->save();

            $conditions[$definition['name']] = $condition;
        }

        return $conditions;
    }

    /**
     * Write the things that happen without anybody choosing them.
     *
     * Recorded, never fired. Nothing in the module runs one, and nothing in the
     * sample data implies otherwise — a trigger is here because a transition
     * names it.
     *
     * @param  list<array<string, mixed>>  $definitions
     * @return array<string, RuleTrigger>
     */
    private function triggers(RuleSet $ruleSet, array $definitions): array
    {
        $triggers = [];

        foreach ($definitions as $position => $definition) {
            $trigger = RuleTrigger::query()->firstOrNew([
                'rule_set_id' => $ruleSet->getKey(),
                'name' => $definition['name'],
            ]);

            $trigger->fill([
                'name' => $definition['name'],
                'description' => $definition['description'] ?? null,
            ]);

            $trigger->rule_set_id = $ruleSet->getKey();
            $trigger->trigger_type = $definition['type'];
            $trigger->position = $position + 1;

            $this->stamp($trigger, $ruleSet->created_at);
            $trigger->save();

            $triggers[$definition['name']] = $trigger;
        }

        return $triggers;
    }

    /**
     * Write one mechanism the rule system says it uses.
     *
     * This studio's own word for it, not an entry in GameDesign's shared design
     * vocabulary. The two have similar names and mean different things.
     *
     * @param  array<string, mixed>  $definition
     */
    private function mechanic(RuleSet $ruleSet, array $definition, int $position): void
    {
        $slug = $this->address($definition);

        $mechanic = RuleMechanic::query()->firstOrNew([
            'rule_set_id' => $ruleSet->getKey(),
            'slug' => $slug,
        ]);

        $mechanic->fill([
            'name' => $definition['name'],
            'description' => $definition['description'] ?? null,
        ]);

        $mechanic->rule_set_id = $ruleSet->getKey();
        $mechanic->slug = $slug;
        $mechanic->category = $definition['category'];
        $mechanic->position = $position;

        $this->stamp($mechanic, $ruleSet->created_at);
        $mechanic->save();
    }

    /**
     * Write the rules, then hang the nested ones off their parents.
     *
     * The offset exists for the cloned rule set: a rule added to a copy has to
     * land after the ones that came across with it, not on top of them.
     *
     * @param  array<string, GamePhase>  $phases
     * @param  list<array<string, mixed>>  $definitions
     * @return array<string, GameRule>
     */
    private function rules(RuleSet $ruleSet, array $phases, array $definitions, User $author, int $offset = 0): array
    {
        $rules = [];

        foreach ($definitions as $position => $definition) {
            $slug = $this->address($definition);

            $rule = GameRule::query()->firstOrNew([
                'rule_set_id' => $ruleSet->getKey(),
                'slug' => $slug,
            ]);

            $rule->fill([
                'name' => $definition['name'],
                'description' => $definition['description'] ?? null,
            ]);

            $rule->rule_set_id = $ruleSet->getKey();
            $rule->slug = $slug;
            $rule->phase_id = isset($definition['phase']) ? $phases[$definition['phase']]->getKey() : null;
            $rule->rule_type = $definition['type'] ?? RuleType::General;
            $rule->status = $definition['status'] ?? RuleStatus::Active;
            $rule->position = $offset + $position + 1;
            $rule->created_by = $author->id;

            $this->stamp($rule, $ruleSet->created_at);
            $rule->save();

            $rules[$definition['name']] = $rule;
        }

        foreach ($definitions as $definition) {
            $rule = $rules[$definition['name']];

            if (isset($definition['parent'])) {
                $rule->parent_rule_id = $rules[$definition['parent']]->getKey();
                $rule->save();
            }

            foreach ($definition['requirements'] ?? [] as $position => $requirement) {
                $this->requirement($ruleSet, $requirement, $position + 1, ruleId: $rule->getKey());
            }

            foreach ($definition['effects'] ?? [] as $position => $effect) {
                $this->effect($ruleSet, $effect, $position + 1, ruleId: $rule->getKey());
            }
        }

        return $rules;
    }

    /**
     * Write the things a player may do, with what gates them and what follows.
     *
     * @param  array<string, GamePhase>  $phases
     * @param  list<array<string, mixed>>  $definitions
     * @return array<string, RuleAction>
     */
    private function actions(RuleSet $ruleSet, array $phases, array $definitions): array
    {
        $actions = [];

        foreach ($definitions as $position => $definition) {
            $slug = $this->address($definition);

            $action = RuleAction::query()->firstOrNew([
                'rule_set_id' => $ruleSet->getKey(),
                'slug' => $slug,
            ]);

            $action->fill([
                'name' => $definition['name'],
                'description' => $definition['description'] ?? null,
            ]);

            $action->rule_set_id = $ruleSet->getKey();
            $action->slug = $slug;
            $action->phase_id = isset($definition['phase']) ? $phases[$definition['phase']]->getKey() : null;
            $action->action_type = $definition['type'] ?? RuleActionType::Basic;
            $action->status = $definition['status'] ?? RuleStatus::Active;

            /*
             * A handle into the game's balance profile, and never an amount. The
             * slug is the one SampleEconomySeeder wrote; what the action costs is
             * read live, so the rules screen and the balance screen can never end
             * up holding two different answers.
             */
            $action->economy_action_slug = $definition['economy'] ?? null;
            $action->position = $position + 1;

            $this->stamp($action, $ruleSet->created_at);
            $action->save();

            $actions[$definition['name']] = $action;

            foreach ($definition['requirements'] ?? [] as $offset => $requirement) {
                $this->requirement($ruleSet, $requirement, $offset + 1, actionId: $action->getKey());
            }

            foreach ($definition['effects'] ?? [] as $offset => $effect) {
                $this->effect($ruleSet, $effect, $offset + 1, actionId: $action->getKey());
            }
        }

        return $actions;
    }

    /**
     * Write one gate on a rule or an action.
     *
     * Exactly one owner, which is what the commands insist on and what the
     * validator reports when it is missing.
     *
     * @param  array<string, mixed>  $definition
     */
    private function requirement(
        RuleSet $ruleSet,
        array $definition,
        int $position,
        ?string $ruleId = null,
        ?string $actionId = null,
    ): void {
        $requirement = RuleRequirement::query()->firstOrNew([
            'rule_set_id' => $ruleSet->getKey(),
            'rule_id' => $ruleId,
            'action_id' => $actionId,
            'description' => $definition['description'],
        ]);

        $requirement->fill([
            'description' => $definition['description'],
            'value' => $definition['value'] ?? null,
        ]);

        $requirement->rule_set_id = $ruleSet->getKey();
        $requirement->rule_id = $ruleId;
        $requirement->action_id = $actionId;
        $requirement->requirement_type = $definition['type'] ?? RequirementType::Custom;
        $requirement->economy_resource_slug = $definition['resource'] ?? null;
        $requirement->position = $position;

        $this->stamp($requirement, $ruleSet->created_at);
        $requirement->save();
    }

    /**
     * Write one thing that happens when a rule or an action resolves.
     *
     * The value is a string here as it is everywhere else in the module, because
     * nothing computes with it — "half, rounded down" is a thing a rulebook says.
     *
     * @param  array<string, mixed>  $definition
     */
    private function effect(
        RuleSet $ruleSet,
        array $definition,
        int $position,
        ?string $ruleId = null,
        ?string $actionId = null,
    ): void {
        $effect = RuleEffect::query()->firstOrNew([
            'rule_set_id' => $ruleSet->getKey(),
            'rule_id' => $ruleId,
            'action_id' => $actionId,
            'target' => $definition['target'],
        ]);

        $effect->fill([
            'target' => $definition['target'],
            'value' => $definition['value'] ?? null,
            'description' => $definition['description'] ?? null,
        ]);

        $effect->rule_set_id = $ruleSet->getKey();
        $effect->rule_id = $ruleId;
        $effect->action_id = $actionId;
        $effect->effect_type = $definition['type'] ?? EffectType::Custom;
        $effect->economy_resource_slug = $definition['resource'] ?? null;
        $effect->position = $position;

        $this->stamp($effect, $ruleSet->created_at);
        $effect->save();
    }

    /**
     * Write one way play can advance.
     *
     * @param  array<string, GamePhase>  $phases
     * @param  array<string, RuleCondition>  $conditions
     * @param  array<string, RuleTrigger>  $triggers
     * @param  array<string, mixed>  $definition
     */
    private function transition(
        RuleSet $ruleSet,
        array $phases,
        array $conditions,
        array $triggers,
        array $definition,
        int $position,
    ): void {
        $from = $phases[$definition['from']];
        $to = $phases[$definition['to']];
        $condition = isset($definition['condition']) ? $conditions[$definition['condition']] : null;

        $transition = PhaseTransition::query()->firstOrNew([
            'from_phase_id' => $from->getKey(),
            'to_phase_id' => $to->getKey(),
            'condition_id' => $condition?->getKey(),
        ]);

        $transition->rule_set_id = $ruleSet->getKey();
        $transition->from_phase_id = $from->getKey();
        $transition->to_phase_id = $to->getKey();
        $transition->condition_id = $condition?->getKey();
        $transition->trigger_id = isset($definition['trigger']) ? $triggers[$definition['trigger']]->getKey() : null;
        $transition->position = $position;

        $this->stamp($transition, $ruleSet->created_at);
        $transition->save();
    }

    /**
     * Write one grouping of conditions, and what is in it.
     *
     * @param  array<string, RuleCondition>  $conditions
     * @param  array<string, mixed>  $definition
     */
    private function group(RuleSet $ruleSet, array $conditions, array $definition): void
    {
        $group = ConditionGroup::query()->firstOrNew([
            'rule_set_id' => $ruleSet->getKey(),
            'name' => $definition['name'],
        ]);

        $group->fill([
            'name' => $definition['name'],
            'description' => $definition['description'] ?? null,
        ]);

        $group->rule_set_id = $ruleSet->getKey();
        $group->logic_operator = $definition['operator'] ?? LogicOperator::And;

        $this->stamp($group, $ruleSet->created_at);
        $group->save();

        foreach ($definition['conditions'] as $position => $name) {
            $membership = ConditionGroupCondition::query()->firstOrNew([
                'condition_group_id' => $group->getKey(),
                'condition_id' => $conditions[$name]->getKey(),
            ]);

            $membership->condition_group_id = $group->getKey();
            $membership->condition_id = $conditions[$name]->getKey();
            $membership->position = $position + 1;
            $membership->save();
        }
    }

    /**
     * Write how the game is won, lost and brought to a close.
     *
     * Three tables rather than one with a kind column, because a game answers
     * all three questions at once and collapsing them would make "which of these
     * ends it" a filter every screen had to remember.
     *
     * @param  array<string, RuleCondition>  $conditions
     * @param  array<string, mixed>  $definition
     */
    private function outcomes(RuleSet $ruleSet, array $conditions, array $definition): void
    {
        $kinds = [
            'victory' => VictoryCondition::class,
            'defeat' => DefeatCondition::class,
            'endings' => GameEndCondition::class,
        ];

        foreach ($kinds as $key => $model) {
            foreach ($definition[$key] ?? [] as $priority => $outcome) {
                /** @var VictoryCondition|DefeatCondition|GameEndCondition $record */
                $record = $model::query()->firstOrNew([
                    'rule_set_id' => $ruleSet->getKey(),
                    'name' => $outcome['name'],
                ]);

                $record->fill([
                    'name' => $outcome['name'],
                    'description' => $outcome['description'] ?? null,
                ]);

                $record->rule_set_id = $ruleSet->getKey();
                $record->condition_id = isset($outcome['condition'])
                    ? $conditions[$outcome['condition']]->getKey()
                    : null;
                $record->priority = $priority + 1;

                $this->stamp($record, $ruleSet->created_at);
                $record->save();
            }
        }
    }

    /**
     * Write one relationship between two rules.
     *
     * @param  array<string, GameRule>  $rules
     * @param  array<string, mixed>  $definition
     */
    private function reference(array $rules, array $definition): void
    {
        $rule = $rules[$definition['from']];
        $referenced = $rules[$definition['to']];

        $reference = RuleReference::query()->firstOrNew([
            'rule_id' => $rule->getKey(),
            'referenced_rule_id' => $referenced->getKey(),
            'reference_type' => $definition['type'],
        ]);

        $reference->fill(['description' => $definition['description'] ?? null]);

        $reference->rule_id = $rule->getKey();
        $reference->referenced_rule_id = $referenced->getKey();
        $reference->reference_type = $definition['type'];

        $this->stamp($reference, $rule->created_at);
        $reference->save();
    }
}
