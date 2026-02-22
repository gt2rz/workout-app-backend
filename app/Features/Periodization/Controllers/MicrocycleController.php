<?php

namespace App\Features\Periodization\Controllers;

use App\Features\Periodization\Requests\StoreMicrocycleRequest;
use App\Features\Periodization\Requests\UpdateMicrocycleRequest;
use App\Features\Periodization\Resources\MicrocycleResource;
use App\Http\Controllers\Controller;
use App\Models\Macrocycle;
use App\Models\Mesocycle;
use App\Models\Microcycle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MicrocycleController extends Controller
{
    public function index(Request $request, Macrocycle $macrocycle, Mesocycle $mesocycle): AnonymousResourceCollection
    {
        abort_if($request->user()->cannot('view', $macrocycle), 403);

        $microcycles = $mesocycle->microcycles()
            ->withCount('workoutSessions')
            ->get();

        return MicrocycleResource::collection($microcycles)
            ->additional(['status' => 'success']);
    }

    public function store(StoreMicrocycleRequest $request, Macrocycle $macrocycle, Mesocycle $mesocycle): JsonResponse
    {
        abort_if($request->user()->cannot('update', $macrocycle), 403);

        $microcycle = $mesocycle->microcycles()->create($request->validated());
        $microcycle->loadCount('workoutSessions');

        return (new MicrocycleResource($microcycle))
            ->additional(['status' => 'success'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Macrocycle $macrocycle, Mesocycle $mesocycle, Microcycle $microcycle): MicrocycleResource
    {
        abort_if($request->user()->cannot('view', $macrocycle), 403);

        $microcycle->loadCount('workoutSessions');

        return (new MicrocycleResource($microcycle))
            ->additional(['status' => 'success']);
    }

    public function update(UpdateMicrocycleRequest $request, Macrocycle $macrocycle, Mesocycle $mesocycle, Microcycle $microcycle): MicrocycleResource
    {
        abort_if($request->user()->cannot('update', $macrocycle), 403);

        $microcycle->update($request->validated());
        $microcycle->loadCount('workoutSessions');

        return (new MicrocycleResource($microcycle))
            ->additional(['status' => 'success']);
    }

    public function destroy(Request $request, Macrocycle $macrocycle, Mesocycle $mesocycle, Microcycle $microcycle): JsonResponse
    {
        abort_if($request->user()->cannot('update', $macrocycle), 403);

        $microcycle->delete();

        return response()->json(null, 204);
    }
}
