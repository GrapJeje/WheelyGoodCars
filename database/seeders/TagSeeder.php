<?php

namespace Database\Seeders;

use App\Models\Tags;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            ['name' => 'Airco', 'color' => 'blue'],
            ['name' => 'Winterbanden', 'color' => 'white'],
            ['name' => 'Navigatie', 'color' => 'purple'],
            ['name' => 'Bluetooth', 'color' => 'cyan'],
            ['name' => 'Cruise Control', 'color' => 'gray'],
            ['name' => 'Elektrische ramen', 'color' => 'blue'],
            ['name' => 'Panoramadak', 'color' => 'orange'],
            ['name' => 'Leder interieur', 'color' => 'brown'],
            ['name' => 'Parkeersensor', 'color' => 'yellow'],
            ['name' => 'Backup camera', 'color' => 'green'],
            ['name' => 'Apple CarPlay', 'color' => 'gray'],
            ['name' => 'Android Auto', 'color' => 'green'],
            ['name' => 'Elektrische spiegels', 'color' => 'silver'],
            ['name' => 'Verwarmd stuur', 'color' => 'red'],
            ['name' => 'Automatische verlichting', 'color' => 'yellow'],
            ['name' => 'Hill Assist', 'color' => 'orange'],
            ['name' => 'Sportuitlaat', 'color' => 'black'],
            ['name' => 'Getint glas', 'color' => 'gray'],
            ['name' => 'Doof systeem', 'color' => 'purple'],
            ['name' => 'ESC-systeem', 'color' => 'blue'],
        ];

        $this->command->info('Creating tags...');
        $bar = $this->command->getOutput()->createProgressBar(count($tags));

        foreach ($tags as $tag) {
            Tags::create($tag);
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Successfully created ' . count($tags) . ' tags!');
    }
}
