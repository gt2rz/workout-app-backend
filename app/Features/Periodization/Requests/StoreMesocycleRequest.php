<?php

namespace App\Features\Periodization\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMesocycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mesocycle_type_id' => ['required', 'integer', 'exists:mesocycle_types,id'],
            'split_type_id' => ['required', 'integer', 'exists:split_types,id'],
            'order' => ['required', 'integer', 'min:1'],
            'start_week' => ['required', 'integer', 'min:1'],
            'duration_weeks' => ['required', 'integer', 'min:1', 'max:16'],
            'deload_weeks' => ['nullable', 'array'],
            'deload_weeks.*' => ['integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'mesocycle_type_id.required' => 'El tipo de mesociclo es requerido.',
            'mesocycle_type_id.exists' => 'El tipo de mesociclo seleccionado no existe.',
            'split_type_id.required' => 'El tipo de split es requerido.',
            'split_type_id.exists' => 'El tipo de split seleccionado no existe.',
            'order.required' => 'El orden del mesociclo es requerido.',
            'order.min' => 'El orden debe ser al menos 1.',
            'start_week.required' => 'La semana de inicio es requerida.',
            'start_week.min' => 'La semana de inicio debe ser al menos 1.',
            'duration_weeks.required' => 'La duración en semanas es requerida.',
            'duration_weeks.min' => 'La duración mínima es de 1 semana.',
            'duration_weeks.max' => 'La duración máxima es de 16 semanas.',
            'deload_weeks.array' => 'Las semanas de deload deben ser un array.',
        ];
    }
}
