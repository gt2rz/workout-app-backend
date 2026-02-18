<?php

namespace App\Features\Home\Controllers;

use App\Features\Home\Resources\HomeResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        return (new HomeResource($user))
            ->additional([
                'status' => 'success',
            ]);
    }
}
