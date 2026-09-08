<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Identity\Domain\Models\User;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /*
         * Both of these are content rather than test data, and both are keyed by
         * address so that running them again edits rather than duplicates.
         *
         * The vocabulary comes first because it is the more basic of the two: a
         * designer describing their game needs words to describe it with, and an
         * empty picker sends everybody back to free text — which is the thing the
         * vocabulary exists to replace.
         *
         * The design framework follows for the same reason it exists at all: a
         * designer cannot adopt a methodology nobody has written, so a fresh
         * install ships with Barkeep's own.
         */
        $this->call(MechanicSeeder::class);
        $this->call(DesignFrameworkSeeder::class);

        /*
         * The worked example is the opposite: a fictional studio, its games and
         * two years of its playtests. Useful to develop and demonstrate against
         * and wrong to ship, so it is called here only where the database is
         * somebody's own.
         *
         * On a shared or production install, run it deliberately:
         * `php artisan db:seed --class=SampleDataSeeder`.
         */
        if (app()->environment('local')) {
            /*
             * The throwaway login, alongside the sample data rather than above it.
             * `User::factory()` needs `fakerphp/faker`, which is a dev dependency and
             * absent from a production image — so a factory call in the unguarded part
             * of this seeder takes the content seeders down with it.
             */
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

            if (app()->isLocale('fa')) {
                $this->call(SampleFaDataSeeder::class);
            } else {
                $this->call(SampleDataSeeder::class);
            }
        }
    }
}
