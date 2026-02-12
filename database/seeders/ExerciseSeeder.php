<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExerciseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseExercises = [
            [
                'name' => 'Press de banca',
                'type' => 'Compuesto',
                'description' => 'Empuje horizontal clásico para pecho y tríceps.',
                'muscles' => ['Pecho', 'Tríceps', 'Hombros'],
            ],
            [
                'name' => 'Press militar con barra',
                'type' => 'Compuesto',
                'description' => 'Empuje vertical para hombros y tríceps.',
                'muscles' => ['Hombros', 'Tríceps'],
            ],
            [
                'name' => 'Remo con barra',
                'type' => 'Compuesto',
                'description' => 'Tracción horizontal para espalda media y bíceps.',
                'muscles' => ['Espalda', 'Bíceps'],
            ],
            [
                'name' => 'Dominadas',
                'type' => 'Compuesto',
                'description' => 'Tracción vertical con propio peso.',
                'muscles' => ['Espalda', 'Bíceps', 'Core'],
            ],
            [
                'name' => 'Sentadilla trasera',
                'type' => 'Compuesto',
                'description' => 'Patrón básico de piernas que involucra cuádriceps y glúteos.',
                'muscles' => ['Cuádriceps', 'Glúteos', 'Isquiotibiales'],
            ],
            [
                'name' => 'Peso muerto convencional',
                'type' => 'Compuesto',
                'description' => 'Cadena posterior completa y fuerza de agarre.',
                'muscles' => ['Espalda', 'Glúteos', 'Isquiotibiales', 'Antebrazos'],
            ],
            [
                'name' => 'Hip thrust',
                'type' => 'Compuesto',
                'description' => 'Hiperextensión de cadera orientada a glúteos.',
                'muscles' => ['Glúteos', 'Isquiotibiales'],
            ],
            [
                'name' => 'Press de hombro con mancuernas sentado',
                'type' => 'Compuesto',
                'description' => 'Variación controlada del empuje vertical.',
                'muscles' => ['Hombros', 'Tríceps'],
            ],
            [
                'name' => 'Curl de bíceps con barra',
                'type' => 'Aislamiento',
                'description' => 'Flexión de codo para bíceps.',
                'muscles' => ['Bíceps'],
            ],
            [
                'name' => 'Fondos en paralelas',
                'type' => 'Compuesto',
                'description' => 'Empuje enfocado en pecho inferior y tríceps.',
                'muscles' => ['Pecho', 'Tríceps'],
            ],
            [
                'name' => 'Curl femoral',
                'type' => 'Aislamiento',
                'description' => 'Flexión de rodilla para isquiotibiales.',
                'muscles' => ['Isquiotibiales'],
            ],
            [
                'name' => 'Prensa de piernas',
                'type' => 'Compuesto',
                'description' => 'Extensión de pierna con énfasis en cuádriceps.',
                'muscles' => ['Cuádriceps', 'Glúteos'],
            ],
            [
                'name' => 'Remo en polea baja',
                'type' => 'Compuesto',
                'description' => 'Tracción controlada con foco en escapulares.',
                'muscles' => ['Espalda', 'Bíceps'],
            ],
            [
                'name' => 'Elevaciones laterales',
                'type' => 'Aislamiento',
                'description' => 'Deltoides lateral para anchura de hombros.',
                'muscles' => ['Hombros'],
            ],
            [
                'name' => 'Plancha frontal',
                'type' => 'Funcional',
                'description' => 'Estabilidad anti-extensión para core.',
                'muscles' => ['Core'],
            ],
            [
                'name' => 'Remo a una mano con mancuerna',
                'type' => 'Compuesto',
                'description' => 'Tracción unilateral para espalda y core.',
                'muscles' => ['Espalda', 'Core'],
            ],
            [
                'name' => 'Elevación de talones parado',
                'type' => 'Aislamiento',
                'description' => 'Pantorrillas con rango completo.',
                'muscles' => ['Pantorrillas'],
            ],
            [
                'name' => 'Press inclinado con mancuernas',
                'type' => 'Compuesto',
                'description' => 'Empuje superior de pecho.',
                'muscles' => ['Pecho', 'Hombros', 'Tríceps'],
            ],
            [
                'name' => 'Elevaciones de cadera con una pierna',
                'type' => 'Funcional',
                'description' => 'Glúteos y core con control unilateral.',
                'muscles' => ['Glúteos', 'Core'],
            ],
        ];

        foreach ($baseExercises as $exercise) {
            $exerciseTypeId = DB::table('exercise_types')->where('name', $exercise['type'])->value('id');

            if (! $exerciseTypeId) {
                continue;
            }

            DB::table('exercises')->updateOrInsert(
                ['name' => $exercise['name'], 'exercise_type_id' => $exerciseTypeId],
                [
                    'description' => $exercise['description'],
                    'instructions' => null,
                    'video_url' => null,
                    'is_custom' => false,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $exerciseId = DB::table('exercises')->where('name', $exercise['name'])->value('id');

            if (! $exerciseId) {
                continue;
            }

            DB::table('exercise_muscle_group')->where('exercise_id', $exerciseId)->delete();

            foreach ($exercise['muscles'] as $index => $groupName) {
                $muscleGroupId = DB::table('muscle_groups')->where('name', $groupName)->value('id');

                if (! $muscleGroupId) {
                    continue;
                }

                DB::table('exercise_muscle_group')->updateOrInsert(
                    ['exercise_id' => $exerciseId, 'muscle_group_id' => $muscleGroupId],
                    ['is_primary' => $index === 0]
                );
            }
        }
    }
}
