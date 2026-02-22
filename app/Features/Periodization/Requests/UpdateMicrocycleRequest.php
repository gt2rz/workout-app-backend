<?php

namespace App\Features\Periodization\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMicrocycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'week_number' => ['sometimes', 'integer', 'min:1'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'is_deload' => ['sometimes', 'boolean'],
            'target_volume_percentage' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:200'],
            'actual_volume_completed' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:planned,active,completed'],
        ];
    }

    public function messages(): array
    {
        return [
            'week_number.min' => 'El número de semana debe ser al menos 1.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'target_volume_percentage.min' => 'El porcentaje mínimo de volumen es 0.',
            'target_volume_percentage.max' => 'El porcentaje máximo de volumen es 200.',
            'status.in' => 'El estado debe ser uno de: planned, active, completed.',
        ];
    }
}
