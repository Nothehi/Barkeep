<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\GameDesign\Domain\Enums\MechanicCategory;
use Modules\GameDesign\Domain\Enums\MechanicStatus;
use Modules\GameDesign\Domain\Models\Mechanic;

/**
 * The starting design vocabulary.
 *
 * Content rather than test data: a designer describing their game needs words
 * to describe it with on the first day, and an empty picker would push everyone
 * straight back to free text — which is the thing the vocabulary exists to
 * replace.
 *
 * Deliberately not exhaustive. A published taxonomy runs to a couple of hundred
 * terms, most of which describe a handful of games each, and a picker that long
 * is one nobody reads to the end of. This is the set a board game designer
 * reaches for most, and curators add the rest as real games need them — which
 * is the whole reason the vocabulary lives in the database rather than in an
 * enum.
 *
 * Idempotent: seeding twice updates the definitions in place rather than
 * producing a second copy of the vocabulary. A curator's own terms are left
 * alone, and so is anything they have retired.
 */
class MechanicSeeder extends Seeder
{
    /**
     * Seed the vocabulary.
     */
    public function run(): void
    {
        foreach ($this->mechanics() as [$category, $name, $description]) {
            $slug = Str::slug($name);

            $mechanic = Mechanic::query()->firstOrNew(['slug' => $slug]);

            /*
             * A term the curator has retired stays retired. Re-seeding is how
             * definitions improve, not a way for the platform to overrule
             * somebody's decision to withdraw a word.
             */
            if ($mechanic->exists && $mechanic->status === MechanicStatus::Archived) {
                continue;
            }

            $mechanic->fill(['name' => $name, 'description' => $description]);
            $mechanic->slug = $slug;
            $mechanic->category = $category;
            $mechanic->status = MechanicStatus::Published;
            $mechanic->save();
        }

        $this->command?->info(sprintf('Seeded %d design mechanics.', count($this->mechanics())));
    }

    /**
     * The vocabulary, as [category, name, definition].
     *
     * Definitions are written to settle arguments rather than to be complete.
     * A designer picking between "worker placement" and "action drafting"
     * wants to know which one their game is, and a dictionary entry that
     * restates the name helps nobody.
     *
     * @return list<array{0: MechanicCategory, 1: string, 2: string}>
     */
    private function mechanics(): array
    {
        return [
            [MechanicCategory::TurnStructure, 'Worker placement', 'Players take turns claiming action spaces, and a space taken is a space nobody else can use this round.'],
            [MechanicCategory::TurnStructure, 'Action points', 'A turn is a budget. Players spend a pool of points across whatever actions they choose.'],
            [MechanicCategory::TurnStructure, 'Action selection', 'Players choose from a shared list of actions, usually with a cost or a consequence attached to popular ones.'],
            [MechanicCategory::TurnStructure, 'Simultaneous action', 'Everybody decides at once and reveals together, so nobody is waiting and nobody is reacting.'],
            [MechanicCategory::TurnStructure, 'Turn order variation', 'The order of play is itself a resource, bid for or earned rather than fixed.'],
            [MechanicCategory::TurnStructure, 'Programming', 'Players commit to a sequence of actions in advance and then watch it play out, correctly or otherwise.'],
            [MechanicCategory::TurnStructure, 'Real time', 'Play is bounded by a clock rather than by turns, so hesitation costs something directly.'],

            [MechanicCategory::Economy, 'Engine building', 'Players assemble a machine of interacting pieces that makes later turns more productive than earlier ones.'],
            [MechanicCategory::Economy, 'Resource management', 'Play is about gathering, converting and spending a limited set of goods.'],
            [MechanicCategory::Economy, 'Tableau building', 'Each player grows a personal array of cards or tiles whose combinations define what they can do.'],
            [MechanicCategory::Economy, 'Income and upkeep', 'Something arrives each round and something is owed each round, so standing still is not free.'],
            [MechanicCategory::Economy, 'Market and pricing', 'The cost of goods moves with what players buy and sell, so an economy has other people in it.'],
            [MechanicCategory::Economy, 'Contracts', 'Players take on stated objectives with a cost and a reward, choosing what to commit to.'],

            [MechanicCategory::Space, 'Area control', 'Players compete for presence in regions, and the majority in a region is worth something.'],
            [MechanicCategory::Space, 'Tile placement', 'The board is built during play by adding pieces, and where a piece goes matters as much as which piece it is.'],
            [MechanicCategory::Space, 'Network building', 'Players lay routes between points, and a connected network is worth more than its parts.'],
            [MechanicCategory::Space, 'Point to point movement', 'Pieces move along defined links rather than freely, so geography constrains plans.'],
            [MechanicCategory::Space, 'Grid movement', 'Pieces move across a grid, where distance, adjacency and line of sight are the rules of the space.'],
            [MechanicCategory::Space, 'Pattern building', 'Players arrange pieces to form shapes or arrangements that score.'],

            [MechanicCategory::Cards, 'Deck building', 'Players buy cards into a personal deck that they will later draw from, so today\'s purchase is tomorrow\'s hand.'],
            [MechanicCategory::Cards, 'Hand management', 'The interest is in when to play a card rather than which cards you hold.'],
            [MechanicCategory::Cards, 'Card drafting', 'Players pick from a shared offer and pass or replace it, so choosing is also denying.'],
            [MechanicCategory::Cards, 'Set collection', 'Value comes from gathering matching or complementary things rather than from any one of them.'],
            [MechanicCategory::Cards, 'Trick taking', 'Players each contribute a card to a round and one of them wins it, under rules about what may be played.'],
            [MechanicCategory::Cards, 'Bag building', 'Players add tokens to a pool they draw blindly from, shaping their odds rather than their choices.'],

            [MechanicCategory::Interaction, 'Auction', 'Players bid for what is on offer, so the price of a thing is set by how much everybody else wants it.'],
            [MechanicCategory::Interaction, 'Trading', 'Players exchange goods by agreement, which makes talking part of the game.'],
            [MechanicCategory::Interaction, 'Take that', 'Players can directly harm one another\'s position, and choosing a target is part of the decision.'],
            [MechanicCategory::Interaction, 'Negotiation', 'Deals are made and may be broken, and the game is largely about who trusts whom.'],
            [MechanicCategory::Interaction, 'Cooperative play', 'Players win or lose together against the game rather than each other.'],
            [MechanicCategory::Interaction, 'Hidden roles', 'Somebody at the table is not who they say they are, and finding out is the point.'],
            [MechanicCategory::Interaction, 'Asymmetric powers', 'Players start with different abilities, so the same board is a different problem for each of them.'],

            [MechanicCategory::Uncertainty, 'Dice rolling', 'Outcomes are resolved by dice, whether as raw results or as a resource to be assigned.'],
            [MechanicCategory::Uncertainty, 'Push your luck', 'Players may keep going for more and lose what they have gathered, and stopping is a real decision.'],
            [MechanicCategory::Uncertainty, 'Hidden information', 'Players know things the others do not, so reading people is part of reading the board.'],
            [MechanicCategory::Uncertainty, 'Bluffing', 'Players may misrepresent what they hold or intend, and being caught costs something.'],
            [MechanicCategory::Uncertainty, 'Variable setup', 'The starting position changes between plays, so opening knowledge does not carry over intact.'],

            [MechanicCategory::Scoring, 'Victory points', 'Progress is measured in an explicit score, and the highest at the end wins.'],
            [MechanicCategory::Scoring, 'Race', 'The first to reach a stated position wins, so the finish line rather than a total decides it.'],
            [MechanicCategory::Scoring, 'Hidden objectives', 'Players score against goals the others cannot see, so nobody is quite sure who is winning.'],
            [MechanicCategory::Scoring, 'End game bonuses', 'A significant part of the score is settled after play stops, from what players built rather than what they did.'],
            [MechanicCategory::Scoring, 'Elimination', 'Players can be removed from play before the game ends.'],
            [MechanicCategory::Scoring, 'Catch-up mechanism', 'The rules give ground to players who are behind, so a lead is not a formality.'],
        ];
    }
}
