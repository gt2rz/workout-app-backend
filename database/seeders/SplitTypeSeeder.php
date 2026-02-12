<?php

namespace Database\Seeders;

use App\Models\SplitType;
use Illuminate\Database\Seeder;

class SplitTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $splits = [
            ['name' => 'Full Body', 'days_per_week' => 3, 'description' => 'Entrena todo el cuerpo en cada sesión, ideal para principiantes o poco tiempo.'],
            ['name' => 'Torso/Pierna', 'days_per_week' => 4, 'description' => 'Dos bloques diferenciados para torso y pierna con frecuencia 2.'],
            ['name' => 'Push/Pull/Legs', 'days_per_week' => 3, 'description' => 'Divide los empujes, tracciones y piernas para manejar volumen.'],
            ['name' => 'Weider', 'days_per_week' => 5, 'description' => 'Rutina músculo por día para especialización y altas series.'],
        ];

        foreach ($splits as $split) {
            SplitType::updateOrCreate(
                ['name' => $split['name']],
                ['days_per_week' => $split['days_per_week'], 'description' => $split['description']]
            );
        }
    }
}
