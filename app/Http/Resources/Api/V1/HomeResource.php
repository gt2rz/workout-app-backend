<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Workout\WorkoutTodayService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $todayWorkout = app(WorkoutTodayService::class)->getWorkoutForToday($this->resource);
        $hasWorkout = $todayWorkout !== null;

        return [
            'greeting' => [
                'enabled' => true,
                'today' => now()->locale('es')->isoFormat('dddd, D [de] MMMM'),
                'greeting' => [
                    'message' => $this->getGreetingMessage(),
                    'user_name' => $this->firstName(),
                ],
            ],
            'weekly_overview' => [
                'enabled' => true,
                'title' => 'Tu Semana',
                'subtitle' => 'Planifica tus entrenamientos y actividades.',
                'week_days' => $this->getWeekDays(6),
                'all_label' => 'Ver todo',
                'show_all' => false,
            ],
            'workout_today' => [
                'enabled' => true,
                'has_workout' => $hasWorkout,
                'workout' => $this->when(
                    $hasWorkout,
                    fn () => new WorkoutTodayResource($todayWorkout)
                ),
                'no_workout' => $this->when(
                    ! $hasWorkout,
                    fn () => [
                        'title' => '¡Descanso hoy!',
                        'subtitle' => 'Recarga energías para tu próxima sesión.',
                        'image_url' => null,
                    ]
                ),
            ],
            'progress_overview' => [
                'enabled' => true,
                'title' => 'Tu Progreso',
                'subtitle' => 'Sigue avanzando hacia tus metas.',
                'metrics' => [
                    'weight' => [
                        'label' => 'Peso actual',
                        'icono_name' => 'weight_scale',
                        'value' => 70,
                        'unit' => 'kg',
                        'trend' => [
                            'direction' => 'down',
                            'percentage' => '-0.5kg',
                        ],
                        'last_measures' => [
                            [
                                'date' => '2024-01-25',
                                'value' => '70.5 kg',
                            ],
                            [
                                'date' => '2024-01-18',
                                'value' => '71 kg',
                            ],
                            [
                                'date' => '2024-01-11',
                                'value' => '71.5 kg',
                            ],
                            [
                                'date' => '2024-01-04',
                                'value' => '72 kg',
                            ],
                            [
                                'date' => '2023-12-28',
                                'value' => '72.5 kg',
                            ],
                        ],
                    ],
                    'streak' => [
                        'id' => 2,
                        'label' => 'Racha Semanal',
                        'icono_name' => 'fire',
                        'value' => 3,
                        'unit' => 'días',
                        'trend' => [
                            'message' => '¡Vas genial! Mantén el ritmo.',
                        ],
                    ],
                ],
            ],
            'quick_access' => [
                'enabled' => true,
                'title' => 'Accesos Rápidos',
                'options' => [
                    [
                        'id' => 1,
                        'label' => 'Registrar peso',
                        'icon_name' => 'weight_scale',
                        'icon_color' => '#4CAF50',
                    ],
                    [
                        'id' => 2,
                        'label' => 'Crear rutina',
                        'icon_name' => 'playlist_add',
                        'icon_color' => '#2196F3',
                    ],
                    [
                        'id' => 3,
                        'label' => 'Historial de entrenos',
                        'icon_name' => 'history',
                        'icon_color' => '#FF9800',
                    ],
                ],
            ],

        ];
    }

    private function getGreetingMessage(): string
    {
        $hour = now()->hour;

        if ($hour >= 5 && $hour < 12) {
            return '¡Buenos días, {{user_name}}!';
        } elseif ($hour >= 12 && $hour < 18) {
            return '¡Buenas tardes, {{user_name}}!';
        } else {
            return '¡Buenas noches, {{user_name}}!';
        }
    }

    private function firstName(): string
    {
        $names = explode(' ', trim($this->resource->name));

        return $names[0] ?? $this->resource->name;
    }

    private function getWeekDays(int $days = 6): array
    {
        $start = Carbon::now();
        $end = Carbon::now()->addDays($days);

        return collect(CarbonPeriod::create($start, $end))->map(function (Carbon $date) {
            return [
                'day' => $date->locale('es')->minDayName, // 'lun', 'mar', etc.
                'label' => $date->format('d'),           // '01', '02', etc.
                'date' => $date->toDateString(),      // '2024-02-01', etc.
                'is_today' => $date->isToday(),             // true o false
            ];
        })->toArray();
    }
}
