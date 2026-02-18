<?php

namespace App\Features\Profile\Controllers;

use App\Exceptions\Profile\ProfileNotFoundException;
use App\Features\Profile\Resources\ProfileResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile()->with(['user.membership', 'userPreferences'])->first();

        if (! $profile) {
            throw new ProfileNotFoundException;
        }

        return (new ProfileResource($profile))
            ->additional([
                'status' => 'success',
            ]);
    }
}
