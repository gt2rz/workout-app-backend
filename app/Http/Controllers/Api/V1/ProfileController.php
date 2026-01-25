<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProfileResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function getProfile(Request $request) {
        $user = $request->user();

        return (new ProfileResource($user))
        ->additional([
            'status' => 'success',
        ]);
    }

}
