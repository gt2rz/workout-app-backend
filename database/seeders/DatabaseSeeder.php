<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\ExerciseSeeder;
use Database\Seeders\ExerciseTypeSeeder;
use Database\Seeders\MesocycleTypeSeeder;
use Database\Seeders\MuscleGroupSeeder;
use Database\Seeders\SplitTypeSeeder;
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
        $this->call([
            MuscleGroupSeeder::class,
            ExerciseTypeSeeder::class,
            SplitTypeSeeder::class,
            MesocycleTypeSeeder::class,
            ExerciseSeeder::class,
        ]);

        // User::factory(10)->create();

        User::query()->updateOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
        ]);
    }
}
