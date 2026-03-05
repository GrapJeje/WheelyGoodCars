<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Make my user for testing purposes
        User::factory()->create(
            [
                'name' => 'Jason',
                'email' => 'jasonvanl2019@gmail.com',
            ]
        );

        // Create 150 users
        $this->call(UserSeeder::class);
        // Create 20 tags
        $this->call(TagSeeder::class);
        // Create 250+ cars from RDW API with tags
        $this->call(CarSeeder::class);
    }
}
