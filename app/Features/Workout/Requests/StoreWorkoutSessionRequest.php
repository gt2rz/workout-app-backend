<?php

namespace App\Features\Workout\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'microcycle_id' => ['nullable', 'integer', 'exists:microcycles,id'],
            'workout_template_id' => ['nullable', 'integer', 'exists:workout_templates,id'],
            'scheduled_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:scheduled,in_progress,completed,skipped'],
        ];
    }

    public function messages(): array
    {
        return [
            'microcycle_id.exists' => 'El microciclo seleccionado no existe.',
            'workout_template_id.exists' => 'La plantilla de entrenamiento seleccionada no existe.',
            'scheduled_date.required' => 'La fecha programada es requerida.',
            'scheduled_date.date' => 'La fecha programada debe ser una fecha válida.',
            'status.in' => 'El estado debe ser uno de: scheduled, in_progress, completed, skipped.',
        ];
    }
}
