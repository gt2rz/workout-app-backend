<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Envía el link de restablecimiento de contraseña
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'status' => 'success',
                'message' => 'Se ha enviado un enlace de restablecimiento a tu correo electrónico.',
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'No se pudo enviar el enlace de restablecimiento.',
        ], 500);
    }

    /**
     * Restablece la contraseña del usuario
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'status' => 'success',
                'message' => 'Tu contraseña ha sido restablecida exitosamente.',
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => $this->getErrorMessage($status),
        ], 400);
    }

    /**
     * Obtiene mensaje de error apropiado según el status
     */
    private function getErrorMessage(string $status): string
    {
        return match ($status) {
            Password::INVALID_TOKEN => 'El token de restablecimiento es inválido o ha expirado.',
            Password::INVALID_USER => 'No se encontró un usuario con este correo electrónico.',
            default => 'No se pudo restablecer la contraseña. Por favor, intenta nuevamente.',
        };
    }
}
