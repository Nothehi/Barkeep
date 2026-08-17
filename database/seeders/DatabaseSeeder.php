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
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        /*
         * The design framework is content rather than test data: a designer cannot
         * adopt a methodology nobody has written, so a fresh install ships with
         * Barkeep's own. The seeder is idempotent and keyed by address, so running
         * it again edits rather than duplicates.
         */
        $this->call(DesignFrameworkSeeder::class);
    }
}
