<?php

namespace App\Features\Periodization\Controllers;

use App\Features\Periodization\Requests\StoreMesocycleRequest;
use App\Features\Periodization\Requests\UpdateMesocycleRequest;
use App\Features\Periodization\Resources\MesocycleResource;
use App\Http\Controllers\Controller;
use App\Models\Macrocycle;
use App\Models\Mesocycle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MesocycleController extends Controller
{
    public function index(Request $request, Macrocycle $macrocycle): AnonymousResourceCollection
    {
        abort_if($request->user()->cannot('view', $macrocycle), 403);

        $mesocycles = $macrocycle->mesocycles()
            ->with(['mesocycleType', 'splitType'])
            ->withCount('microcycles')
            ->get();

        return MesocycleResource::collection($mesocycles)
            ->additional(['status' => 'success']);
    }

    public function store(StoreMesocycleRequest $request, Macrocycle $macrocycle): JsonResponse
    {
        abort_if($request->user()->cannot('update', $macrocycle), 403);

        $mesocycle = $macrocycle->mesocycles()->create($request->validated());
        $mesocycle->load(['mesocycleType', 'splitType'])->loadCount('microcycles');

        return (new MesocycleResource($mesocycle))
            ->additional(['status' => 'success'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Macrocycle $macrocycle, Mesocycle $mesocycle): MesocycleResource
    {
        abort_if($request->user()->cannot('view', $macrocycle), 403);

        $mesocycle->load(['mesocycleType', 'splitType'])->loadCount('microcycles');

        return (new MesocycleResource($mesocycle))
            ->additional(['status' => 'success']);
    }

    public function update(UpdateMesocycleRequest $request, Macrocycle $macrocycle, Mesocycle $mesocycle): MesocycleResource
    {
        abort_if($request->user()->cannot('update', $macrocycle), 403);

        $mesocycle->update($request->validated());
        $mesocycle->load(['mesocycleType', 'splitType'])->loadCount('microcycles');

        return (new MesocycleResource($mesocycle))
            ->additional(['status' => 'success']);
    }

    public function destroy(Request $request, Macrocycle $macrocycle, Mesocycle $mesocycle): JsonResponse
    {
        abort_if($request->user()->cannot('update', $macrocycle), 403);

        $mesocycle->delete();

        return response()->json(null, 204);
    }
}
