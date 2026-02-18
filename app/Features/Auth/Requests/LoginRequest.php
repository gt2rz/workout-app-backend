<?php

namespace App\Features\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required'],
            'device' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'Debe proporcionar un correo electrónico válido.',
            'password.required' => 'La contraseña es requerida.',
            'device.required' => 'El identificador del dispositivo es requerido.',
        ];
    }
}
