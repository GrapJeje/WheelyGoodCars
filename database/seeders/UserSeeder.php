<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{

    public function run(): void
    {
        $count = 150;
        $bar = $this->command->getOutput()->createProgressBar($count);

        $this->command->info('Creating users...');
        User::factory($count)->create()->each(function () use ($bar) {
            $bar->advance();
        });

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Successfully created ' . $count . ' users!');
    }
}
