<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProfileResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function getProfile(Request $request) {
        try {
            $user = $request->user();
            $profile = $user->profile()->with(['user.membership', 'userPreferences'])->first();
            if (!$profile) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Perfil no encontrado.'
                ], 404);
            }
            return (new ProfileResource($profile))
                ->additional([
                    'status' => 'success',
                ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

}
