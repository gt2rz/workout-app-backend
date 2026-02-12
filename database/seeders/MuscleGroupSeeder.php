<?php

namespace Database\Seeders;

use App\Models\MuscleGroup;
use Illuminate\Database\Seeder;

class MuscleGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            ['name' => 'Pecho', 'description' => 'Empuja y estabiliza cargas horizontales.'],
            ['name' => 'Espalda', 'description' => 'Tracción y postura de la cadena posterior.'],
            ['name' => 'Hombros', 'description' => 'Estabilidad y transferencia de fuerza vertical.'],
            ['name' => 'Bíceps', 'description' => 'Flexión de codo y stylización de brazos.'],
            ['name' => 'Tríceps', 'description' => 'Extensión de codo y bloqueo de press.'],
            ['name' => 'Cuádriceps', 'description' => 'Extensión de rodilla y control de sentadillas.'],
            ['name' => 'Isquiotibiales', 'description' => 'Flexión de rodilla y posterior de pierna.'],
            ['name' => 'Glúteos', 'description' => 'Potencia de cadera y control de postura.'],
            ['name' => 'Pantorrillas', 'description' => 'Elevación de talón y soporte dinámico.'],
            ['name' => 'Core', 'description' => 'Estabilidad del tronco y transferencia de fuerza.'],
            ['name' => 'Antebrazos', 'description' => 'Agarre y control del antebrazo.'],
        ];

        foreach ($groups as $group) {
            MuscleGroup::updateOrCreate(
                ['name' => $group['name']],
                ['description' => $group['description']]
            );
        }
    }
}
