<?php

namespace Database\Seeders;

use App\Models\Cars;
use App\Models\User;
use App\Models\Tags;
use App\Services\RDWService;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class CarSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('nl_NL');
        $users = User::pluck('id')->toArray();
        $tags = Tags::pluck('id')->toArray();

        if (empty($users)) {
            $this->command->warn('No users found. Run UserSeeder first.');
            return;
        }

        $this->command->info('Fetching vehicle data from RDW API...');
        $vehicleData = RDWService::getVehicles(250);

        if (empty($vehicleData)) {
            $this->command->error('RDW API unavailable, cannot seed cars.');
            return;
        }

        $this->command->info('Creating cars...');
        $bar = $this->command->getOutput()->createProgressBar(count($vehicleData));

        foreach ($vehicleData as $data) {
            $car = Cars::create([
                'user_id'         => $users[array_rand($users)],
                'license_plate'   => $data['license_plate'] ?? $faker->regexify('[A-Z]{2}-[0-9]{4}-[A-Z]{2}'),
                'make'            => $data['make'],
                'model'           => $data['model'],
                'price'           => $faker->numberBetween(5000, 50000),
                'mileage'         => $faker->numberBetween(5000, 300000),
                'seats'           => $data['seats']           ?? $faker->numberBetween(4, 7),
                'doors'           => $data['doors']           ?? $faker->numberBetween(2, 5),
                'production_year' => $data['production_year'] ?? $faker->year(),
                'weight'          => $data['weight']          ?? $faker->numberBetween(1000, 2500),
                'color'           => $data['color']           ?? $faker->colorName(),
                'views'           => $faker->numberBetween(0, 500),
            ]);

            // Attach 3-5 random tags
            $shuffled = collect($tags)->shuffle()->take(rand(3, 5))->toArray();
            $car->tags()->attach($shuffled);

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Successfully created ' . count($vehicleData) . ' cars with tags!');
    }
}
