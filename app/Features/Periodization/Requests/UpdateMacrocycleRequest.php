<?php

namespace App\Features\Periodization\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMacrocycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'goal' => ['sometimes', 'nullable', 'string'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after:start_date'],
            'duration_weeks' => ['sometimes', 'integer', 'min:1', 'max:52'],
            'is_active' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:planned,active,completed,paused'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'duration_weeks.min' => 'La duración mínima es de 1 semana.',
            'duration_weeks.max' => 'La duración máxima es de 52 semanas.',
            'status.in' => 'El estado debe ser uno de: planned, active, completed, paused.',
        ];
    }
}
