<?php

namespace Database\Seeders;

use App\Models\MesocycleType;
use Illuminate\Database\Seeder;

class MesocycleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Introductorio', 'rep_range_min' => 12, 'rep_range_max' => 15, 'typical_duration_weeks' => 3, 'description' => 'Adaptación técnica, enfoque en movimiento controlado y MEV.'],
            ['name' => 'Hipertrofia', 'rep_range_min' => 8, 'rep_range_max' => 12, 'typical_duration_weeks' => 6, 'description' => 'Construcción muscular con volumen medio-alto y progresión.'],
            ['name' => 'Fuerza', 'rep_range_min' => 1, 'rep_range_max' => 5, 'typical_duration_weeks' => 5, 'description' => 'Entrenamientos pesados para mejorar RM y sistema nervioso central.'],
            ['name' => 'Definición', 'rep_range_min' => 8, 'rep_range_max' => 15, 'typical_duration_weeks' => 5, 'description' => 'Mantenimiento muscular con déficit y enfoque en volumen manejable.'],
        ];

        foreach ($types as $type) {
            MesocycleType::updateOrCreate(
                ['name' => $type['name']],
                [
                    'rep_range_min' => $type['rep_range_min'],
                    'rep_range_max' => $type['rep_range_max'],
                    'typical_duration_weeks' => $type['typical_duration_weeks'],
                    'description' => $type['description'],
                ]
            );
        }
    }
}
