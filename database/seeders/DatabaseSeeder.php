<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Campaign;
use App\Models\NewsletterList;
use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\NewsletterSubscriber;

class DatabaseSeeder extends Seeder
{
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

        NewsletterList::factory()->count(3)->create();
        NewsletterSubscriber::factory()->count(10)->create([
            'newsletter_list_id' => 1,
        ]);
        NewsletterSubscriber::factory()->count(20)->create([
            'newsletter_list_id' => 2,
        ]);
        NewsletterSubscriber::factory()->count(30)->create([
            'newsletter_list_id' => 3,
        ]);

        Campaign::factory()->count(3)->create();

    }
}
