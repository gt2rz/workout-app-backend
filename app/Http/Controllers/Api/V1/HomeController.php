<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\HomeResource;
use App\Http\Resources\Api\V1\ProfileResource;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request) {
        try {
            $user = $request->user();

            return (new HomeResource($user))
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
