<?php

namespace App\Features\Periodization\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMicrocycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'week_number' => ['required', 'integer', 'min:1'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_deload' => ['nullable', 'boolean'],
            'target_volume_percentage' => ['nullable', 'integer', 'min:0', 'max:200'],
            'status' => ['nullable', 'in:planned,active,completed'],
        ];
    }

    public function messages(): array
    {
        return [
            'week_number.required' => 'El número de semana es requerido.',
            'week_number.min' => 'El número de semana debe ser al menos 1.',
            'start_date.required' => 'La fecha de inicio es requerida.',
            'start_date.date' => 'La fecha de inicio debe ser una fecha válida.',
            'end_date.required' => 'La fecha de fin es requerida.',
            'end_date.date' => 'La fecha de fin debe ser una fecha válida.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'target_volume_percentage.min' => 'El porcentaje mínimo de volumen es 0.',
            'target_volume_percentage.max' => 'El porcentaje máximo de volumen es 200.',
            'status.in' => 'El estado debe ser uno de: planned, active, completed.',
        ];
    }
}
