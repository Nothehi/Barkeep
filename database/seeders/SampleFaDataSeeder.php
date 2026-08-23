<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * The Persian worked example: a second methodology and the workshop that follows it.
 *
 * Designed to sit alongside `SampleDataSeeder` rather than to replace it. Two studios working in
 * two languages in one database is not a contrivance — it is what the application claims to
 * support, and it is the only way to see that the claim is true: `MechanicResource` translates a
 * shared vocabulary both studios draw on, while every game, playtest and observation either of
 * them wrote stays in the language it was written in.
 *
 * `DatabaseSeeder` nonetheless picks one of the two by `app()->isLocale('fa')`, so a plain
 * `migrate:fresh --seed` gives whoever runs it a workshop they can read. Seed the other one by
 * hand when you want both; nothing prevents it, and the two share only the design vocabulary.
 *
 * Run it on its own:
 *
 *     php artisan db:seed --class=SampleFaDataSeeder
 *
 * The order is the English seeder's order with the Persian methodology in front, because a
 * framework has to exist before a game can adopt one. Each seeder looks up what the previous one
 * wrote by address, so any of them can be re-run alone.
 */
class SampleFaDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the Persian worked example.
     */
    public function run(): void
    {
        $this->call([
            /*
             * «مسیر کارگاه» — a methodology somebody wrote, not a translation of the one Barkeep
             * ships. Content rather than sample data, which is why it is a Fa*Seeder rather than a
             * SampleFa*Seeder: a Persian install would want it whether or not it wanted a
             * fictional workshop.
             */
            FaDesignFrameworkSeeder::class,

            SampleFaStudioSeeder::class,
            SampleFaGameSeeder::class,
            SampleFaFrameworkProgressSeeder::class,
            SampleFaPlaytestSeeder::class,
            SampleFaPrototypeSeeder::class,
            SampleFaEconomySeeder::class,
            SampleFaRulesSeeder::class,
        ]);
    }
}
