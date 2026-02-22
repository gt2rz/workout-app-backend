<?php

namespace App\Features\Workout\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkoutSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'microcycle_id' => ['sometimes', 'nullable', 'integer', 'exists:microcycles,id'],
            'workout_template_id' => ['sometimes', 'nullable', 'integer', 'exists:workout_templates,id'],
            'scheduled_date' => ['sometimes', 'date'],
            'completed_at' => ['sometimes', 'nullable', 'date'],
            'duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'overall_rpe' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'in:scheduled,in_progress,completed,skipped'],
        ];
    }

    public function messages(): array
    {
        return [
            'microcycle_id.exists' => 'El microciclo seleccionado no existe.',
            'workout_template_id.exists' => 'La plantilla de entrenamiento seleccionada no existe.',
            'scheduled_date.date' => 'La fecha programada debe ser una fecha válida.',
            'duration_minutes.min' => 'La duración mínima es de 1 minuto.',
            'overall_rpe.min' => 'El RPE mínimo es 1.',
            'overall_rpe.max' => 'El RPE máximo es 10.',
            'status.in' => 'El estado debe ser uno de: scheduled, in_progress, completed, skipped.',
        ];
    }
}
