<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Modules\DesignFramework\Domain\Enums\CriterionRating;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\Enums\GameFrameworkStatus;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\ChecklistItemCompletion;
use Modules\DesignFramework\Domain\Models\CriterionEvaluation;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\DesignPractice;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Domain\Models\PracticeCompletion;
use Modules\DesignFramework\Domain\Models\PromptResponse;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;

/**
 * Four games part-way through Barkeep's own methodology.
 *
 * Each adoption stops somewhere honest. Harbourmaster has worked through
 * iteration and is looping back; Kiln has only reached the core loop and is
 * about to be told it has no ending; Ember Court is in development with the
 * early stages behind it; Tidewrack stopped at prototyping and its adoption is
 * paused rather than deleted, because the answers it gave are the record of why
 * the game was shelved.
 *
 * Two things are deliberately *not* seeded. Criteria and checklist items with a
 * `satisfied_by` fact are answered by the design record itself — writing an
 * evaluation for one would put a second, disagreeing answer next to it. And
 * nothing here runs through the module's commands: they want an acting request
 * and they announce progress to a notification system, neither of which a
 * seeder has any business supplying.
 */
class SampleFrameworkProgressSeeder extends SampleSeeder
{
    /**
     * The methodology these games follow.
     *
     * A method rather than a constant because a studio that works in another language follows
     * another edition, and which edition is the only thing that differs between the two.
     */
    protected function frameworkSlug(): string
    {
        return DesignFrameworkSeeder::SLUG;
    }

    /**
     * Seed each game's progress through the methodology.
     */
    public function run(): void
    {
        $version = $this->publishedVersion();

        if ($version === null) {
            $this->command->warn('No published design framework found — run DesignFrameworkSeeder first.');

            return;
        }

        $phases = DesignPhaseDefinition::query()
            ->where('framework_version_id', $version->getKey())
            ->orderBy('position')
            ->get();

        foreach ($this->adoptions() as $adoption) {
            $game = $this->game($adoption['workspace'], $adoption['game']);
            $follower = $this->user($adoption['by']);

            $adopted = $this->adopt($game, $version, $follower, $adoption);

            $reached = $phases->take($adoption['reached']);

            foreach ($reached->values() as $index => $phase) {
                /*
                 * Progress is dated by working backwards from the adoption: the
                 * first phase was worked through when the game was young and the
                 * most recent one a few days ago, so the activity list reads as
                 * a project rather than as an import.
                 */
                $worked = $this->workedOn($adopted->started_at, $index, $adoption['reached']);

                $this->evaluate($adopted, $phase, $follower, $worked, $adoption['ratings']);
                $this->practise($adopted, $phase, $follower, $worked);
                $this->answer($adopted, $phase, $follower, $worked, $adoption['answers']);
                $this->tick($adopted, $phase, $follower, $worked);
            }
        }

        $this->command->info(sprintf('Seeded %d sample framework adoptions.', count($this->adoptions())));
    }

    /**
     * The published edition of the methodology, if it has been seeded.
     */
    private function publishedVersion(): ?FrameworkVersion
    {
        $framework = Framework::query()->where('slug', $this->frameworkSlug())->first();

        if ($framework === null) {
            return null;
        }

        return FrameworkVersion::query()
            ->where('framework_id', $framework->getKey())
            ->where('status', FrameworkStatus::Published)
            ->orderByDesc('version_number')
            ->first();
    }

    /**
     * Record that a game follows the methodology.
     *
     * @param  array<string, mixed>  $adoption
     */
    private function adopt(Game $game, FrameworkVersion $version, User $follower, array $adoption): GameFramework
    {
        $adopted = GameFramework::query()->firstOrNew(['game_id' => $game->getKey()]);

        $adopted->game_id = $game->getKey();
        $adopted->framework_version_id = $version->getKey();
        $adopted->status = $adoption['status'];
        $adopted->started_at = $this->daysAgo($adoption['started'], 9);
        $adopted->completed_at = $adoption['status'] === GameFrameworkStatus::Completed
            ? $this->daysAgo($adoption['finished'] ?? 30, 17)
            : null;
        $adopted->adopted_by = $follower->id;

        $this->stamp($adopted, $adopted->started_at);
        $adopted->save();

        return $adopted;
    }

    /**
     * The day a given phase's work was done.
     *
     * Spread evenly between the adoption and a fortnight ago. Precision is not
     * the point — an ordering that matches the order the work happened in is.
     */
    private function workedOn(CarbonImmutable $startedAt, int $index, int $phaseCount): CarbonImmutable
    {
        $span = max(1, $startedAt->diffInDays(CarbonImmutable::now()->subDays(14)));

        return $startedAt->addDays((int) round($span * ($index + 1) / max(1, $phaseCount)))->setTime(15, 0);
    }

    /**
     * Grade the phase's judgement criteria.
     *
     * @param  array<string, array{0: CriterionRating, 1: string}>  $ratings
     */
    private function evaluate(
        GameFramework $adopted,
        DesignPhaseDefinition $phase,
        User $evaluator,
        CarbonImmutable $on,
        array $ratings,
    ): void {
        $criteria = DesignCriterion::query()
            ->where('phase_id', $phase->getKey())
            ->orderBy('position')
            ->get();

        foreach ($criteria as $criterion) {
            /*
             * A criterion the design record answers is already answered. Grading
             * it here would be a second opinion nobody asked for.
             */
            if ($criterion->isAnsweredByTheDesignRecord()) {
                continue;
            }

            [$rating, $note] = $ratings[$criterion->slug] ?? [CriterionRating::NotEvaluated, null];

            if ($rating === CriterionRating::NotEvaluated) {
                continue;
            }

            $evaluation = CriterionEvaluation::query()->firstOrNew([
                'game_framework_id' => $adopted->getKey(),
                'criterion_id' => $criterion->getKey(),
            ]);

            $evaluation->game_framework_id = $adopted->getKey();
            $evaluation->criterion_id = $criterion->getKey();
            $evaluation->status = $rating;
            $evaluation->notes = $note;
            $evaluation->evaluated_by = $evaluator->id;
            $evaluation->evaluated_at = $on;

            $this->stamp($evaluation, $on);
            $evaluation->save();
        }
    }

    /**
     * Mark the phase's practices done.
     *
     * No notes: a practice is a thing you did, and most of the time the record
     * that you did it is the whole of what anybody wants from the row.
     */
    private function practise(GameFramework $adopted, DesignPhaseDefinition $phase, User $by, CarbonImmutable $on): void
    {
        $practices = DesignPractice::query()
            ->where('phase_id', $phase->getKey())
            ->orderBy('position')
            ->get();

        foreach ($practices as $offset => $practice) {
            $completion = PracticeCompletion::query()->firstOrNew([
                'game_framework_id' => $adopted->getKey(),
                'practice_id' => $practice->getKey(),
            ]);

            $completion->game_framework_id = $adopted->getKey();
            $completion->practice_id = $practice->getKey();
            $completion->completed_by = $by->id;
            $completion->completed_at = $on->addHours($offset);

            $this->stamp($completion, $completion->completed_at);
            $completion->save();
        }
    }

    /**
     * Answer the phase's prompts.
     *
     * Only the prompts this game has an answer written for. A prompt is a
     * question somebody has to sit down and think about, and an unanswered one
     * is the normal state of most of them.
     *
     * @param  array<string, string>  $answers
     */
    private function answer(
        GameFramework $adopted,
        DesignPhaseDefinition $phase,
        User $by,
        CarbonImmutable $on,
        array $answers,
    ): void {
        $prompts = DesignPrompt::query()
            ->where('phase_id', $phase->getKey())
            ->orderBy('position')
            ->get();

        foreach ($prompts as $offset => $prompt) {
            if (! isset($answers[$prompt->slug])) {
                continue;
            }

            $response = PromptResponse::query()->firstOrNew([
                'game_framework_id' => $adopted->getKey(),
                'prompt_id' => $prompt->getKey(),
            ]);

            $response->game_framework_id = $adopted->getKey();
            $response->prompt_id = $prompt->getKey();
            $response->response = $answers[$prompt->slug];
            $response->answered_by = $by->id;
            $response->answered_at = $on->addHours($offset + 1);

            $this->stamp($response, $response->answered_at);
            $response->save();
        }
    }

    /**
     * Tick the phase's readiness gates.
     *
     * Items met by a design fact are skipped for the same reason their criteria
     * are: the fact is the answer, and a tick beside it would be a second one.
     */
    private function tick(GameFramework $adopted, DesignPhaseDefinition $phase, User $by, CarbonImmutable $on): void
    {
        $checklists = Checklist::query()
            ->where('phase_id', $phase->getKey())
            ->orderBy('position')
            ->pluck('id');

        $items = ChecklistItem::query()
            ->whereIn('checklist_id', $checklists)
            ->orderBy('position')
            ->get();

        foreach ($items as $offset => $item) {
            if ($item->isAnsweredByTheDesignRecord()) {
                continue;
            }

            $completion = ChecklistItemCompletion::query()->firstOrNew([
                'game_framework_id' => $adopted->getKey(),
                'checklist_item_id' => $item->getKey(),
            ]);

            $completion->game_framework_id = $adopted->getKey();
            $completion->checklist_item_id = $item->getKey();
            $completion->completed_by = $by->id;
            $completion->completed_at = $on->addMinutes(15 * $offset);

            $this->stamp($completion, $completion->completed_at);
            $completion->save();
        }
    }

    /**
     * Who follows the methodology, how far, and what they have said about it.
     *
     * `reached` is a count of phases from the start, because the methodology is
     * worked through in order even though designers loop back. Ratings and
     * answers are keyed by the content's address, so content that is renamed
     * loses its answer rather than attaching it to the wrong question.
     *
     * @return list<array<string, mixed>>
     */
    protected function adoptions(): array
    {
        return [
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'harbourmaster',
                'by' => 'mara@lanternandanvil.test',
                'status' => GameFrameworkStatus::Active,
                'started' => 236,
                'reached' => 7,
                'ratings' => [
                    'is-there-a-reason-this-game-rather-than-a-similar-one' => [CriterionRating::Good, 'Worker placement games are usually about scarcity of workers. This one is about scarcity of time, and the tide is the only clock anybody has to read.'],
                    'is-the-core-decision-meaningful' => [CriterionRating::Strong, 'A careless player unloads whatever is nearest; a good one reads which berth the tide closes first. The two play the same turn differently and it shows in the score.'],
                    'is-the-loop-understandable' => [CriterionRating::Good, 'New players describe it back correctly after one round, though most of them say "before the tide goes out" rather than naming the cycle.'],
                    'does-the-loop-stay-interesting-on-the-twentieth-repetition' => [CriterionRating::NeedsWork, 'It holds for three tide cycles and sags in the fourth, when the contracts left are the ones nobody wanted.'],
                    'does-player-interaction-create-interesting-choices' => [CriterionRating::Good, 'Taking the berth somebody was queuing for is the whole of the interaction, and it is enough. Nobody has asked for a way to hurt each other more directly.'],
                    'is-downtime-acceptable' => [CriterionRating::NeedsWork, 'At four players the gap between turns runs to two and a half minutes late in a cycle. Everyone is watching the tide board, which helps, but it is still the longest wait in the game.'],
                    'can-a-losing-player-still-play-well' => [CriterionRating::Good, 'The fourth cycle bonuses are large enough that a player behind on crates can still place third or better. Two sessions have ended within four points.'],
                    'is-every-subsystem-load-bearing' => [CriterionRating::Weak, 'The dockhand market has been removed and re-added twice and nothing broke either time. That is an answer.'],
                    'can-the-prototype-be-played-start-to-finish' => [CriterionRating::Strong, 'Four full cycles, scored, in every session since v2.'],
                    'can-it-be-changed-in-under-a-minute' => [CriterionRating::Strong, 'Berths are index cards and the tide board is a printed strip. Changing a berth mid-session is a matter of writing on it.'],
                    'have-players-outside-the-design-taught-you-something' => [CriterionRating::Strong, 'The Tuesday group taught us that the tide reads as a threat, not a schedule. Nobody in the studio had noticed we were reading it as a schedule.'],
                    'do-new-players-understand-the-rules-from-the-reference-alone' => [CriterionRating::NeedsWork, 'Three of four groups asked what happens to a ship that is partly unloaded. The reference does not say, because we had not decided.'],
                    'does-the-game-end-when-you-intended' => [CriterionRating::NeedsWork, 'Two-player games land at fifty minutes. Four-player games have run to ninety-five twice, against a stated maximum of seventy-five.'],
                    'did-the-last-change-do-what-you-expected' => [CriterionRating::Good, 'Three contract piles were meant to make denial a real decision, and they did. They also slowed the fourth cycle down, which we did not expect.'],
                    'are-the-same-problems-still-being-reported' => [CriterionRating::NeedsWork, 'Length at four players has come back in five sessions running. It is not the group.'],
                    'is-the-game-getting-simpler' => [CriterionRating::Good, 'Two subsystems out, one in, and the rules reference is a third shorter than it was at v1.'],
                ],
                'answers' => [
                    'core-experience' => 'The feeling of a shift that is nearly under control. A player should finish '
                        .'the game able to name the one ship they let go and still think they were right to.',
                    'the-reason-to-play' => 'Because every other worker placement game on the shelf is about not '
                        .'having enough workers. This one gives you plenty and takes away the time.',
                    'the-intended-table' => 'Four people who have played a worker placement game before, on a '
                        .'weeknight, with an hour and a bit before somebody has to leave. That last part is why '
                        .'seventy-five minutes is a limit rather than an aspiration.',
                    'the-repeated-action' => 'Place a crew, take the space\'s action, watch the tide move one step. '
                        .'It stays interesting because the same berth is worth a different amount depending on how '
                        .'many steps are left.',
                    'the-most-interesting-decision' => 'Whether to unload the last hold of a ship you have almost '
                        .'finished, or start the one that will strand if you do not. It comes up two or three '
                        .'times a cycle, so eight to twelve times a game.',
                    'where-tension-comes-from' => 'From the tide board being visible and unstoppable. The '
                        .'discomfort is meant to be about your own planning, and at the moment about a third of it '
                        .'is about waiting for other players instead.',
                    'the-safest-option' => 'Everyone works the cranes and nobody berths anything risky. That game '
                        .'is dull and it also loses — the fourth-cycle bonuses only pay out to players who took a '
                        .'ship they were not sure about. That is the intended answer.',
                    'the-first-five-minutes' => 'They learn what a crew is, what the tide does and that a berth '
                        .'can close. They have to be told that a partly unloaded ship keeps its cargo, because the '
                        .'reference still does not say so.',
                    'what-surprised-you' => 'Players talk to the tide board. Two groups have physically leaned '
                        .'over to look at it before choosing, which is the clearest evidence we have that moving '
                        .'it off the rules card was right.',
                    'the-unresolved-problem' => 'The four-player length. We keep answering it with small trims and '
                        .'it keeps coming back, which probably means a cycle has to go — and losing a cycle would '
                        .'change what the fourth-cycle bonuses are for.',
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'kiln',
                'by' => 'devin@lanternandanvil.test',
                'status' => GameFrameworkStatus::Active,
                'started' => 72,
                'reached' => 3,
                'ratings' => [
                    'is-there-a-reason-this-game-rather-than-a-similar-one' => [CriterionRating::Good, 'Most push-your-luck games ask you to stop before a random event. This one asks you to stop before the other player is ready, which is a read rather than a gamble.'],
                    'is-the-core-decision-meaningful' => [CriterionRating::Good, 'Calling the firing early costs you a piece and costs them two. Both players want the other to call it, and that is the game.'],
                    'is-the-loop-understandable' => [CriterionRating::Strong, 'Load or fire. Nobody has needed it explained twice.'],
                    'does-the-loop-stay-interesting-on-the-twentieth-repetition' => [CriterionRating::NeedsWork, 'A game is about fourteen loads and it is still interesting at the end. Beyond that it would not be — which is an argument for keeping the game short rather than a problem to fix.'],
                ],
                'answers' => [
                    'core-experience' => 'Two people watching each other rather than the board, both hoping the '
                        .'other one blinks.',
                    'the-intended-table' => 'Two people at a kitchen table with half an hour, most likely between '
                        .'two longer games.',
                    'the-repeated-action' => 'Put a piece in the kiln, or open it. The pieces already in there are '
                        .'what make the next decision different.',
                    'the-most-interesting-decision' => 'Loading a fourth piece when you know your opponent can '
                        .'fire on their turn. Once or twice a game, and it decides most of them.',
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::LANTERN,
                'game' => 'tidewrack',
                'by' => 'mara@lanternandanvil.test',
                'status' => GameFrameworkStatus::Paused,
                'started' => 394,
                'reached' => 5,
                'ratings' => [
                    'is-there-a-reason-this-game-rather-than-a-similar-one' => [CriterionRating::Weak, 'It is a diving game with an air track. So are the two on the shelf behind me, and they are finished.'],
                    'is-the-core-decision-meaningful' => [CriterionRating::NeedsWork, 'Going one space deeper is almost always correct until it is catastrophically not, and players cannot tell which turn that is.'],
                    'is-the-loop-understandable' => [CriterionRating::Good, 'Down, grab, up. Nobody has ever needed it explained.'],
                    'does-the-loop-stay-interesting-on-the-twentieth-repetition' => [CriterionRating::Weak, 'By the third dive every player is doing the same thing in the same order and the only variable is the dice.'],
                    'does-player-interaction-create-interesting-choices' => [CriterionRating::Weak, 'There is essentially none. Five players play five solitaire games beside each other.'],
                    'is-downtime-acceptable' => [CriterionRating::Weak, 'At five players a diver waits four minutes to find out whether they drowned.'],
                    'can-a-losing-player-still-play-well' => [CriterionRating::NeedsWork, 'A player who loses a loaded diver in the first dive cannot catch up, and there are two more dives to sit through.'],
                    'is-every-subsystem-load-bearing' => [CriterionRating::NeedsWork, 'The salvage sets were added to give the second dive a reason to exist. They did not.'],
                    'can-the-prototype-be-played-start-to-finish' => [CriterionRating::Good, 'Three dives and a score, reliably.'],
                    'can-it-be-changed-in-under-a-minute' => [CriterionRating::Strong, 'The whole thing is a grid drawn on card. Changing it is a matter of a marker.'],
                ],
                'answers' => [
                    'core-experience' => 'The moment of deciding to go one space deeper with a full bag. That '
                        .'moment works. Everything built around it does not.',
                    'the-intended-table' => 'Five people who like being cruel to themselves. In practice five '
                        .'people who spent most of the evening waiting.',
                    'the-repeated-action' => 'Move a diver one space and spend air. It is the same decision every '
                        .'time, which is the problem.',
                    'the-first-five-minutes' => 'They learn the air track, and then they learn that it is the '
                        .'only thing in the game.',
                ],
            ],
            [
                'workspace' => SampleStudioSeeder::NIGHTSHIFT,
                'game' => 'ember-court',
                'by' => 'yusuf@nightshiftgames.test',
                'status' => GameFrameworkStatus::Active,
                'started' => 268,
                'reached' => 8,
                'ratings' => [
                    'is-there-a-reason-this-game-rather-than-a-similar-one' => [CriterionRating::Good, 'The bid is public and the penalty for missing it is being handed the ember, which is a job rather than a score. No other trick-taker on my shelf makes losing a role.'],
                    'is-the-core-decision-meaningful' => [CriterionRating::Strong, 'Bidding one under what you can make, to hand somebody else the ember, is a real and frequently correct play.'],
                    'is-the-loop-understandable' => [CriterionRating::Strong, 'Anyone who has played a trick-taking game is playing properly by the second hand.'],
                    'does-the-loop-stay-interesting-on-the-twentieth-repetition' => [CriterionRating::Good, 'A game is nine or ten hands and the ember moves in most of them, so the board state genuinely changes.'],
                    'does-player-interaction-create-interesting-choices' => [CriterionRating::Strong, 'Spending favour to change your bid after seeing somebody else\'s is the most talked-about moment in every session.'],
                    'is-downtime-acceptable' => [CriterionRating::Strong, 'It is a trick-taking game. Nobody is ever more than three cards from acting.'],
                    'can-a-losing-player-still-play-well' => [CriterionRating::Good, 'The ember costs a point and gives a favour, so the player behind has the most flexible bid. That was the whole point of the v2 rework.'],
                    'is-every-subsystem-load-bearing' => [CriterionRating::Good, 'Favour, bids and the ember. Removing any one of them ends the game as a design.'],
                    'can-the-prototype-be-played-start-to-finish' => [CriterionRating::Strong, 'Print and play deck, full game, since the first month.'],
                    'can-it-be-changed-in-under-a-minute' => [CriterionRating::NeedsWork, 'Not any more — the deck is printed and a card change is a reprint. That is the cost of being at development.'],
                    'have-players-outside-the-design-taught-you-something' => [CriterionRating::Strong, 'A group of four who had never met me played it twice and asked to keep the deck. The second play is the evidence.'],
                    'do-new-players-understand-the-rules-from-the-reference-alone' => [CriterionRating::Good, 'One question in the last blind test, about whether favour carries between hands. It is in the rulebook; it is in the wrong section.'],
                    'does-the-game-end-when-you-intended' => [CriterionRating::Strong, 'Twenty-eight to thirty-six minutes across the last nine sessions, against a stated twenty-five to forty.'],
                    'did-the-last-change-do-what-you-expected' => [CriterionRating::Good, 'Moving favour to the bid stopped the runaway leader exactly as intended.'],
                    'are-the-same-problems-still-being-reported' => [CriterionRating::Good, 'The only repeat is the favour-between-hands question, which is a rulebook problem rather than a design one.'],
                    'is-the-game-getting-simpler' => [CriterionRating::Strong, 'The v2 rework removed a whole scoring track.'],
                    'is-there-a-dominant-strategy' => [CriterionRating::Good, 'Two experienced groups tried to break it by always bidding zero. It loses, slowly and visibly.'],
                    'can-somebody-learn-the-game-from-the-rulebook-alone' => [CriterionRating::NeedsWork, 'Nearly. The favour section needs to move ahead of the bidding section, because that is the order players need it in.'],
                    'are-all-the-components-final-in-kind-if-not-in-art' => [CriterionRating::Strong, 'Sixty cards, five favour tokens and one ember. Nothing on that list wants to be a different kind of thing.'],
                ],
                'answers' => [
                    'core-experience' => 'The half-second after somebody bids high and you realise you can make '
                        .'them miss it.',
                    'the-reason-to-play' => 'It is a trick-taking game where the person losing has the most '
                        .'interesting turn, and it fits in a coat pocket.',
                    'the-intended-table' => 'Four people in a staff room with thirty minutes and one small table. '
                        .'That is where it was designed and it is what the footprint is for.',
                    'the-repeated-action' => 'Bid, then play cards to tricks. The bid is what makes the same '
                        .'fifty-two cards a different problem every hand.',
                    'the-most-interesting-decision' => 'Whether to spend favour to lower a bid you now know you '
                        .'cannot make. Once or twice a hand.',
                    'where-tension-comes-from' => 'From having said a number out loud that everybody heard.',
                    'the-safest-option' => 'Everyone bids one. That game is quiet, short and decided by the '
                        .'ember, which is a worse game but still a game — and it is not stable, because whoever '
                        .'breaks first wins.',
                    'the-first-five-minutes' => 'The bid, the trick and the ember. They have to be told that '
                        .'favour does not carry between hands, which is the rulebook\'s fault.',
                    'what-surprised-you' => 'Players bid to lose on purpose far more often than I designed for. '
                        .'It turns out that is the best part of the game.',
                    'the-unresolved-problem' => 'Whether five players is really supported or merely possible. '
                        .'Every five-player session has been fine and none of them has been the best game of the '
                        .'evening.',
                    'the-remaining-risk' => 'That the ember is too swingy at three players, where it returns to '
                        .'the same person too often. A dozen three-player sessions would settle it and I have run '
                        .'four.',
                ],
            ],
        ];
    }
}
