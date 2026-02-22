<?php

namespace App\Features\Periodization\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMesocycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mesocycle_type_id' => ['sometimes', 'integer', 'exists:mesocycle_types,id'],
            'split_type_id' => ['sometimes', 'integer', 'exists:split_types,id'],
            'order' => ['sometimes', 'integer', 'min:1'],
            'start_week' => ['sometimes', 'integer', 'min:1'],
            'duration_weeks' => ['sometimes', 'integer', 'min:1', 'max:16'],
            'deload_weeks' => ['sometimes', 'nullable', 'array'],
            'deload_weeks.*' => ['integer', 'min:1'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'mesocycle_type_id.exists' => 'El tipo de mesociclo seleccionado no existe.',
            'split_type_id.exists' => 'El tipo de split seleccionado no existe.',
            'order.min' => 'El orden debe ser al menos 1.',
            'start_week.min' => 'La semana de inicio debe ser al menos 1.',
            'duration_weeks.min' => 'La duración mínima es de 1 semana.',
            'duration_weeks.max' => 'La duración máxima es de 16 semanas.',
        ];
    }
}
