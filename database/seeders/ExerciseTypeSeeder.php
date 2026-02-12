<?php

namespace Database\Seeders;

use App\Models\ExerciseType;
use Illuminate\Database\Seeder;

class ExerciseTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Compuesto', 'description' => 'Movimientos multiarticulares con alta transferencia.'],
            ['name' => 'Aislamiento', 'description' => 'Enfocados en un solo músculo o articulación.'],
            ['name' => 'Funcional', 'description' => 'Movimientos que mejoran la capacidad diaria.'],
            ['name' => 'Cardiovascular', 'description' => 'Elevación de la frecuencia cardiaca.'],
            ['name' => 'Movilidad', 'description' => 'Patrones de rango de movimiento y control.'],
        ];

        foreach ($types as $type) {
            ExerciseType::updateOrCreate(['name' => $type['name']], ['description' => $type['description']]);
        }
    }
}
