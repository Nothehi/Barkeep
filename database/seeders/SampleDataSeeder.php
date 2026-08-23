<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * A worked example of the whole application, as one studio's two years of work.
 *
 * Sample data rather than test data: every game, playtest, iteration and
 * economy here is one somebody could have written, and they refer to each
 * other. Harbourmaster's contract rework is an iteration, the iteration cites
 * the playtest that produced its evidence, the playtest was run against a
 * design version, and the balance profile hanging off that version is the one
 * the numbers in the observations belong to. Random data cannot do that, and a
 * screen full of random data cannot show what the screen is for.
 *
 * Run it on its own:
 *
 *     php artisan db:seed --class=SampleDataSeeder
 *
 * The order below is the order the studio produced the work in, and it is also
 * the dependency order — each seeder looks up what the one before it wrote, by
 * address rather than by identifier, so any of them can be re-run alone to
 * correct its own part.
 *
 * Not called from `DatabaseSeeder` outside local and testing environments. The
 * vocabulary and the design framework are content that belongs on a fresh
 * install; a fictional studio is not.
 */
class SampleDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the worked example.
     */
    public function run(): void
    {
        $this->call([
            /*
             * Accounts and the two workspaces they work in. Everything below is
             * addressed to a workspace, so nothing else can run first.
             */
            SampleStudioSeeder::class,

            /*
             * The games, their design versions and what each game actually is.
             * The versions matter more than they look: a playtest, a prototype
             * and a balance profile all point at one, and that is what makes a
             * two-year-old observation still readable.
             */
            SampleGameSeeder::class,

            /*
             * How far each game has worked through Barkeep's own methodology.
             * Needs the framework itself, which `DesignFrameworkSeeder` writes;
             * it says so and stops if it is missing.
             */
            SampleFrameworkProgressSeeder::class,

            /*
             * The sessions the designs were put in front of people at. Before
             * the prototypes, because the iterations cite these playtests as
             * their evidence and a citation to nothing is not evidence.
             */
            SamplePlaytestSeeder::class,

            /*
             * What was on the table, and what changed between one sitting and
             * the next.
             */
            SamplePrototypeSeeder::class,

            /*
             * The numbers behind three of the games, hung off design versions
             * rather than games so that the old ones stay interpretable.
             */
            SampleEconomySeeder::class,

            /*
             * The rules behind three of them, hung off the same versions and for
             * the same reason. Last, because a rule action points at an economy
             * action by handle: the economy has to exist for the handle to
             * resolve, and Harbourmaster's live rules are the one place in the
             * sample data where the two modules meet.
             */
            SampleRulesSeeder::class,
        ]);
    }
}
