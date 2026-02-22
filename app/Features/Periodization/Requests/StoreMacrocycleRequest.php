<?php

namespace App\Features\Periodization\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMacrocycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'goal' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'duration_weeks' => ['required', 'integer', 'min:1', 'max:52'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del macrociclo es requerido.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'start_date.required' => 'La fecha de inicio es requerida.',
            'start_date.date' => 'La fecha de inicio debe ser una fecha válida.',
            'end_date.required' => 'La fecha de fin es requerida.',
            'end_date.date' => 'La fecha de fin debe ser una fecha válida.',
            'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'duration_weeks.required' => 'La duración en semanas es requerida.',
            'duration_weeks.integer' => 'La duración debe ser un número entero.',
            'duration_weeks.min' => 'La duración mínima es de 1 semana.',
            'duration_weeks.max' => 'La duración máxima es de 52 semanas.',
        ];
    }
}
