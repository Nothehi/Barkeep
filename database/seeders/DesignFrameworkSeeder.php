<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\DesignFramework\Domain\Enums\FrameworkContentStatus;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\DesignPractice;
use Modules\DesignFramework\Domain\Models\DesignPrinciple;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\ValueObjects\FrameworkVersionNumber;
use Modules\Identity\Domain\Models\User;

/**
 * Barkeep's own board-game design methodology, as version 1.
 *
 * The framework tables are useless empty — a designer cannot adopt a methodology nobody has written —
 * so this seeder is the methodology, not a fixture. The phases are the ten stages of section 7, and the
 * content under them is the shortest honest version of each: enough that a designer working through it
 * gets real guidance, and not so much that whoever maintains it has to read a novel to change one
 * criterion.
 *
 * ## Why this bypasses the commands
 *
 * Every row is written directly rather than through `CreatePhase`, `CreateCriterion` and friends, and
 * the version is published by setting its status rather than through `PublishFrameworkVersion`. The
 * commands require an acting account and dispatch domain events; a seeder has neither a user whose
 * action this is nor any business announcing to a notification system that a framework was published
 * during `migrate --seed`.
 *
 * The two rules the commands would enforce are honoured here instead: positions are allocated in
 * order, and addresses are derived from titles. That is what makes this seeder idempotent in the way
 * that matters — running it twice produces the same slugs, so `firstOrCreate` finds what it wrote.
 *
 * ## Idempotency
 *
 * Everything is keyed by address, so re-running updates rather than duplicating. That is deliberate:
 * this file is where the methodology is *edited* until there is an interface somebody would rather
 * use, and an author who fixes a typo should be able to run the seeder again.
 *
 * Note what re-running does *not* do: it does not touch published content in any way a game following
 * it would notice, because the phases and content it rewrites are matched by slug and keep their ids.
 * A real change of substance to a published methodology is a new version, and that is
 * `CreateFrameworkVersion`'s job rather than this file's.
 */
class DesignFrameworkSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The framework's address, and the handle everything else is keyed by.
     */
    private const SLUG = 'board-game-design';

    /**
     * Seed the methodology.
     */
    public function run(): void
    {
        $author = $this->author();

        $framework = Framework::query()->firstOrNew(['slug' => self::SLUG]);

        $framework->fill([
            'name' => 'Board Game Design Framework',
            'description' => 'A structured path from an idea to a game somebody else can play, in ten stages. '
                .'Every stage names what to work on, what to ask of the design, and what has to be true '
                .'before moving on.',
        ]);

        $framework->slug ??= self::SLUG;
        $framework->status = FrameworkStatus::Published;
        $framework->created_by ??= $author->id;
        $framework->save();

        $version = $this->version($framework, $author);

        $position = 0;

        foreach ($this->phases() as $definition) {
            $position++;

            $phase = $this->phase($version, $definition, $position);

            $this->fill($version, $phase, DesignPrinciple::class, $definition['principles'] ?? [], 'description');
            $this->fill($version, $phase, DesignCriterion::class, $definition['criteria'] ?? [], 'description');
            $this->fill($version, $phase, DesignPractice::class, $definition['practices'] ?? [], 'instructions');
            $this->fill($version, $phase, DesignPrompt::class, $definition['prompts'] ?? [], 'prompt');

            foreach ($definition['checklists'] ?? [] as $index => $checklist) {
                $this->checklist($version, $phase, $checklist, $index + 1);
            }
        }

        $this->command->info("Seeded [{$framework->name}] {$version->label()} with {$position} phases.");
    }

    /**
     * The account the methodology is attributed to.
     *
     * Whoever exists — a framework belongs to the platform rather than to a person, and the column is
     * only there so the row has a creator. A fresh database with no accounts gets one, because the
     * seeder should work on an empty install.
     */
    private function author(): User
    {
        return User::query()->oldest()->first()
            ?? User::factory()->create([
                'name' => 'Barkeep',
                'email' => 'framework@barkeep.test',
            ]);
    }

    /**
     * Version 1 of the framework, published.
     *
     * Published directly rather than through the command, because there is no acting designer here and
     * nothing should be announced during a migration. What matters is that games adopting it get a
     * frozen edition — which is exactly what a published status means.
     */
    private function version(Framework $framework, User $author): FrameworkVersion
    {
        $version = FrameworkVersion::query()->firstOrNew([
            'framework_id' => $framework->id,
            'version_number' => FrameworkVersionNumber::FIRST,
        ]);

        $version->fill([
            'name' => 'First edition',
            'description' => 'The methodology as Barkeep ships it.',
        ]);

        $version->framework_id = $framework->id;
        $version->version_number = FrameworkVersionNumber::FIRST;
        $version->status = FrameworkStatus::Published;
        $version->published_at ??= now();
        $version->created_by ??= $author->id;
        $version->save();

        return $version->setRelation('framework', $framework);
    }

    /**
     * Write one stage of the arc.
     *
     * @param  array<string, mixed>  $definition
     */
    private function phase(FrameworkVersion $version, array $definition, int $position): DesignPhaseDefinition
    {
        $slug = Str::slug((string) $definition['name']);

        $phase = DesignPhaseDefinition::query()->firstOrNew([
            'framework_version_id' => $version->id,
            'slug' => $slug,
        ]);

        $phase->fill([
            'name' => (string) $definition['name'],
            'description' => (string) $definition['description'],
        ]);

        $phase->framework_version_id = $version->id;
        $phase->slug = $slug;
        $phase->position = $position;
        $phase->status = FrameworkContentStatus::Published;
        $phase->save();

        return $phase->setRelation('version', $version);
    }

    /**
     * Write a phase's content of one kind.
     *
     * The body column differs by type — a description, a set of instructions, a question — so the caller
     * names it. Everything else about writing content is the same for all four, which is why they share
     * this and why they share an Eloquent base class.
     *
     * @param  class-string<DesignPrinciple|DesignCriterion|DesignPractice|DesignPrompt>  $type
     * @param  array<int, array{0: string, 1: string}>  $rows  title and body, in order
     */
    private function fill(
        FrameworkVersion $version,
        DesignPhaseDefinition $phase,
        string $type,
        array $rows,
        string $bodyColumn,
    ): void {
        $position = 0;

        foreach ($rows as [$title, $body]) {
            $position++;

            $slug = Str::slug($title);

            $content = $type::query()->firstOrNew([
                'framework_version_id' => $version->id,
                'slug' => $slug,
            ]);

            $content->framework_version_id = $version->id;
            $content->phase_id = $phase->id;
            $content->title = $title;
            $content->slug = $slug;
            $content->position = $position;
            $content->status = FrameworkContentStatus::Published;
            $content->setAttribute($bodyColumn, $body);
            $content->save();
        }
    }

    /**
     * Write one readiness gate and its requirements.
     *
     * @param  array{title: string, description: string, items: array<int, string>}  $definition
     */
    private function checklist(
        FrameworkVersion $version,
        DesignPhaseDefinition $phase,
        array $definition,
        int $position,
    ): void {
        $slug = Str::slug($definition['title']);

        $checklist = Checklist::query()->firstOrNew([
            'framework_version_id' => $version->id,
            'slug' => $slug,
        ]);

        $checklist->fill([
            'title' => $definition['title'],
            'description' => $definition['description'],
        ]);

        $checklist->framework_version_id = $version->id;
        $checklist->phase_id = $phase->id;
        $checklist->slug = $slug;
        $checklist->position = $position;
        $checklist->status = FrameworkContentStatus::Published;
        $checklist->save();

        $itemPosition = 0;

        foreach ($definition['items'] as $title) {
            $itemPosition++;

            $itemSlug = Str::slug($title);

            $item = ChecklistItem::query()->firstOrNew([
                'checklist_id' => $checklist->id,
                'slug' => $itemSlug,
            ]);

            $item->fill(['title' => $title, 'required' => true]);

            $item->checklist_id = $checklist->id;
            $item->slug = $itemSlug;
            $item->position = $itemPosition;
            $item->save();
        }
    }

    /**
     * The methodology itself.
     *
     * Ten stages, in the order a design actually moves through them — with the caveat every designer
     * already knows: the arc is a spiral rather than a line, and iteration sends you back to
     * prototyping more often than it sends you forward.
     *
     * The content is chosen to be *answerable*. A criterion a designer cannot honestly grade, and a
     * checklist item nobody can tell whether they have met, are worse than nothing: they turn a
     * methodology into paperwork.
     *
     * @return array<int, array<string, mixed>>
     */
    private function phases(): array
    {
        return [
            [
                'name' => 'Ideation',
                'description' => 'Find something worth building. At this stage the game does not exist and '
                    .'nothing is wasted, which makes it the cheapest place to be wrong.',
                'principles' => [
                    ['An idea is a promise to a player', 'Every idea implies an experience somebody is hoping for. Name that experience and the design has a target; leave it unnamed and every later decision is a guess.'],
                    ['Theme and mechanism have to want the same thing', 'A game about smuggling whose mechanisms reward openness will always feel wrong, and no amount of art fixes it.'],
                ],
                'criteria' => [
                    ['Can you say what the game is in one sentence?', 'Not the theme and not the mechanisms — the experience. If it takes a paragraph, the idea is still several ideas.'],
                    ['Is there a reason this game rather than a similar one?', 'Every idea competes with games that already exist and are already finished.'],
                ],
                'practices' => [
                    ['Write the one-sentence pitch', 'Fill in: "A game about ___ where players ___ in order to ___." Rewrite it until somebody who has not heard the idea repeats it back correctly.'],
                    ['Name three games this sits between', 'For each, write one line on what you are taking and one on what you are refusing. This is faster and more honest than claiming originality.'],
                ],
                'prompts' => [
                    ['Core experience', 'What should a player feel at the moment this game is working exactly as intended?'],
                    ['The reason to play', 'Why would somebody choose this over the closest game they already own?'],
                ],
            ],
            [
                'name' => 'Concept',
                'description' => 'Turn the idea into constraints. Player count, length, weight and audience are '
                    .'not details to settle later — they decide which mechanisms are even available.',
                'principles' => [
                    ['Constraints are design, not paperwork', 'A forty-minute game for two and a two-hour game for five are different designs even with identical mechanisms.'],
                    ['Choose an audience you can actually watch play', 'A design aimed at players you never test with is a design aimed at a guess.'],
                ],
                'criteria' => [
                    ['Are the player count and playing time decided?', 'Ranges are fine; "we will see" is not, because it defers every pacing decision.'],
                    ['Does the intended weight match the intended audience?', 'Complexity a table will not carry is complexity that never gets played.'],
                ],
                'practices' => [
                    ['Write the constraints down', 'Player count, playing time, age, weight, and roughly what a box would cost to make. Keep them visible while designing.'],
                    ['Describe the table you want', 'Who is playing, where, and what else is going on around them. A family game and a convention game survive different amounts of downtime.'],
                ],
                'prompts' => [
                    ['The intended table', 'Who is playing this, and what were they doing an hour before they sat down?'],
                ],
                'checklists' => [
                    [
                        'title' => 'Concept readiness',
                        'description' => 'What has to be settled before designing systems on top of it.',
                        'items' => [
                            'One-sentence pitch written',
                            'Player count decided',
                            'Target playing time decided',
                            'Intended audience named',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Core loop',
                'description' => 'Find the repeating action, consequence and reward that the whole game is made '
                    .'of. If this is not interesting on its own, nothing built on top of it will save it.',
                'principles' => [
                    ['The core loop should be understandable in one turn', 'A player who cannot say what they are doing and why after their first turn will spend the game following instructions.'],
                    ['Every decision should have meaningful consequences', 'A choice whose outcomes are indistinguishable is not a choice; it is an interface.'],
                    ['Luck should support uncertainty rather than replace decision-making', 'Randomness that makes a decision hard is good; randomness that makes it irrelevant is not.'],
                ],
                'criteria' => [
                    ['Is the core decision meaningful?', 'Would a good player and a careless player choose differently, and would it show?'],
                    ['Is the loop understandable?', 'Can a new player describe the action → consequence → reward cycle after seeing one round?'],
                    ['Does the loop stay interesting on the twentieth repetition?', 'The loop repeats for the whole game. Interesting once is not the test.'],
                ],
                'practices' => [
                    ['Write the core loop in one sentence', 'Describe the repeating cycle: what the player does, what it costs, and what it gives back. If you need two sentences, you may have two loops.'],
                    ['Play the loop alone, on paper', 'Twenty repetitions, by hand. You are looking for the point where it stops being interesting, and how many turns in that is.'],
                    ['Identify the dominant strategy', 'Try to win by doing one thing repeatedly. If it works, that is not a bug in play — it is the design telling you the loop has one answer.'],
                ],
                'prompts' => [
                    ['The repeated action', 'What does the player do over and over, and why does it stay interesting?'],
                    ['The most interesting decision', 'What is the single most interesting decision in the game, and how often does a player face it?'],
                ],
                'checklists' => [
                    [
                        'title' => 'Core loop readiness',
                        'description' => 'The loop is the foundation. These have to be true before building systems on it.',
                        'items' => [
                            'Core action identified',
                            'Cost of the core action identified',
                            'Reward identified',
                            'Failure condition identified',
                            'Win condition identified',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'System design',
                'description' => 'Build the systems the loop needs and no more: economy, interaction, catch-up, '
                    .'and the shape of the arc from first turn to last.',
                'principles' => [
                    ['Complexity must produce meaningful depth', 'A rule that adds work without adding decisions is a rule to cut.'],
                    ['Players should understand why they won or lost', 'A game whose outcome feels arbitrary teaches nobody anything and invites nobody back.'],
                    ['Interaction should create choices, not just damage', 'Being able to affect another player matters far less than having an interesting decision about whether to.'],
                ],
                'criteria' => [
                    ['Does player interaction create interesting choices?', 'Interaction that is always correct, or always rude, is not a decision.'],
                    ['Is downtime acceptable?', 'Measure the gap between a player\'s turns at the highest player count, and ask what they are doing in it.'],
                    ['Can a losing player still play well?', 'A game decided in the first third is a game most of whose length is a formality.'],
                    ['Is every subsystem load-bearing?', 'Remove each one in turn and ask what breaks. Nothing breaking is an answer.'],
                ],
                'practices' => [
                    ['Map the economy on one page', 'Every source, every sink, every conversion. Loops with no sink are runaway leaders waiting to happen.'],
                    ['Cut one subsystem', 'Choose the one you are least sure about and remove it entirely. Play without it. Keeping it afterwards is a decision rather than an accident.'],
                    ['Write the arc', 'What is different about turn one, the middle game and the last round? A game with no arc is a spreadsheet with a timer.'],
                ],
                'prompts' => [
                    ['Where tension comes from', 'What makes a player uncomfortable during a turn, and is it the discomfort you intended?'],
                    ['The safest option', 'What happens if every player always chooses the safest option? Is that game still worth playing?'],
                ],
            ],
            [
                'name' => 'Prototyping',
                'description' => 'Make the ugliest thing that can be played. The purpose of a prototype is to be '
                    .'wrong quickly, which is why it should cost nothing to change.',
                'principles' => [
                    ['A prototype is an experiment, not a product', 'Time spent making it look finished is time spent making it harder to change.'],
                    ['If it cannot be changed mid-session, it is too polished', 'The best prototypes are edited at the table.'],
                ],
                'criteria' => [
                    ['Can the prototype be played start to finish?', 'A prototype missing an ending cannot answer questions about pacing.'],
                    ['Can it be changed in under a minute?', 'Card sleeves and a marker beat a print run.'],
                ],
                'practices' => [
                    ['Create a paper prototype', 'Index cards, a pen, and whatever tokens are in the house. Do not make art.'],
                    ['Write the rules as a one-page reference', 'Not a rulebook — a page you can hand somebody and correct by hand.'],
                    ['Play it solo, all seats', 'You are checking that the game functions, not that it is fun. Fun is the next stage\'s question.'],
                ],
                'prompts' => [
                    ['The first five minutes', 'What does a player learn during the first five minutes, and what do they have to be told?'],
                ],
                'checklists' => [
                    [
                        'title' => 'Prototype readiness',
                        'description' => 'What has to exist before putting the game in front of somebody else.',
                        'items' => [
                            'Core loop playable',
                            'Basic components available',
                            'Win condition implemented',
                            'Loss or ending condition implemented',
                            'One-page rules reference written',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Playtesting',
                'description' => 'Put the design in front of people and find out what is actually true. Every '
                    .'playtest should be answering a question you wrote down first.',
                'principles' => [
                    ['A playtest without a question is an evening of gaming', 'Both are worthwhile. Only one of them is design work.'],
                    ['Watch what players do, not what they say', 'Players report what they remember; you can see what they reached for.'],
                    ['The designer is the worst judge of their own rules', 'You know what the rule means. Nobody at the table does.'],
                ],
                'criteria' => [
                    ['Have players outside the design taught you something?', 'Testing only with people who helped design it tests the explanation, not the game.'],
                    ['Do new players understand the rules from the reference alone?', 'Every time you explain something out loud, note it: that is a rules bug.'],
                    ['Does the game end when you intended?', 'Compare real playing times against the constraint you set in Concept.'],
                ],
                'practices' => [
                    ['Run a two-player playtest', 'Two players expose the economy and the interaction most sharply, and it is the easiest session to arrange.'],
                    ['Run a session at the highest player count', 'Downtime, table talk and turn order effects only appear at the top of the range.'],
                    ['Teach it without speaking', 'Hand over the rules reference and say nothing. Write down every question.'],
                ],
                'prompts' => [
                    ['What surprised you', 'What did players do that you did not expect, and what does it tell you about what the rules actually say?'],
                ],
                'checklists' => [
                    [
                        'title' => 'Playtest readiness',
                        'description' => 'What has to be true before a session is worth somebody else\'s evening.',
                        'items' => [
                            'A question the session is meant to answer is written down',
                            'Rules reference is current',
                            'All components for the intended player count exist',
                            'A way to record observations is ready',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Iteration',
                'description' => 'Change one thing at a time and find out what it did. This is where most of a '
                    .'design happens, and where the arc bends back to prototyping.',
                'principles' => [
                    ['Change one thing at a time', 'Two changes in one session produce one result and no information.'],
                    ['Cut before you add', 'Most problems in a design are caused by something that is already there.'],
                    ['Keep the version you broke', 'Iteration is only reversible if you can say what the game used to be.'],
                ],
                'criteria' => [
                    ['Did the last change do what you expected?', 'If you cannot say, the change was not isolated enough to learn from.'],
                    ['Are the same problems still being reported?', 'A complaint that survives three sessions is a design problem, not a group.'],
                    ['Is the game getting simpler?', 'Late-stage designs usually improve by losing rules rather than gaining them.'],
                ],
                'practices' => [
                    ['Record a new version', 'Cut a numbered version before changing anything, so the next playtest is evidence about something specific.'],
                    ['Write down what you expect the change to do', 'Before the session. Comparing it to what happened afterwards is the whole point.'],
                    ['Read back through the observations', 'All of them, in order. Patterns are visible across sessions that are invisible within one.'],
                ],
                'prompts' => [
                    ['The unresolved problem', 'What is the problem you keep deferring, and what would it cost to face it now?'],
                ],
            ],
            [
                'name' => 'Development',
                'description' => 'Settle the numbers and the words. The design is decided; now it has to be '
                    .'balanced, and it has to be teachable by somebody who has never met you.',
                'principles' => [
                    ['A rulebook is part of the design', 'A rule nobody can find is a rule that does not exist.'],
                    ['Balance is a claim about play, not about spreadsheets', 'A number is balanced when a table cannot find the exploit, not when the maths looks even.'],
                ],
                'criteria' => [
                    ['Is there a dominant strategy?', 'Ask experienced players to break it deliberately. Their failure is the only evidence that counts.'],
                    ['Can somebody learn the game from the rulebook alone?', 'Hand it to a group you are not in the room with.'],
                    ['Are all the components final in kind, if not in art?', 'Counts, sizes and types. Art can come later; a card that needs to be a tile cannot.'],
                ],
                'practices' => [
                    ['Write the full rulebook', 'Including setup, turn structure, edge cases and an index of terms.'],
                    ['Run a blind playtest', 'A group teaches itself and plays without you present. Their questions afterwards are the rulebook\'s bug list.'],
                    ['Balance pass on every asymmetric element', 'Every faction, role, card or starting position. Note which ones you are least confident in.'],
                ],
                'prompts' => [
                    ['The remaining risk', 'What is most likely to be wrong with this game, and how would you find out?'],
                ],
            ],
            [
                'name' => 'Production',
                'description' => 'Make it manufacturable. Component choices, costs and specifications become '
                    .'real and stop being cheap to change.',
                'principles' => [
                    ['Every component costs somebody something', 'A beautiful insert is a price rise. Decide whether it is worth one.'],
                    ['Specify what you mean', 'A manufacturer builds what the file says, not what you meant.'],
                ],
                'criteria' => [
                    ['Is the component list final and costed?', 'Counts, materials, sizes and finishes, with a number beside them.'],
                    ['Does the box work on a shelf and on a table?', 'It has to be recognisable at a distance and unpackable in a living room.'],
                ],
                'practices' => [
                    ['Write the component specification', 'Every part, with dimensions, material and quantity. This is the document a manufacturer quotes against.'],
                    ['Cost the box', 'Per-unit cost at two or three print runs. It changes which components are possible.'],
                ],
                'prompts' => [
                    ['The compromise', 'Which component would you cut first if the cost came back too high, and what would that do to the game?'],
                ],
                'checklists' => [
                    [
                        'title' => 'Production readiness',
                        'description' => 'What has to be settled before committing to manufacture.',
                        'items' => [
                            'Component list finalised',
                            'Component specification written',
                            'Per-unit cost estimated',
                            'Rulebook proofread by somebody outside the project',
                            'Box dimensions decided',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Launch',
                'description' => 'Get it in front of players who did not help make it. The design work is done; '
                    .'what remains is making sure the game is findable and teachable without you.',
                'principles' => [
                    ['A game nobody can explain will not spread', 'The pitch a player gives their friends is the one that matters, and it is not yours.'],
                    ['First impressions are part of the design', 'What a player sees before they play decides whether they play.'],
                ],
                'criteria' => [
                    ['Can a stranger explain the game to another stranger?', 'Test it. This is the sentence the game travels on.'],
                    ['Is there a way for players to reach you about rules questions?', 'Every game has errata. The question is whether players can tell you.'],
                ],
                'practices' => [
                    ['Write the back-of-box pitch', 'Sixty words. It has to say what the player does and why it is interesting, in that order.'],
                    ['Prepare a rules FAQ', 'From the blind playtests. The questions have already been asked; write the answers down before launch.'],
                ],
                'prompts' => [
                    ['What you learned', 'What do you know now about designing games that you did not know when this one was an idea?'],
                ],
            ],
        ];
    }
}
