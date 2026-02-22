<?php

namespace App\Features\Periodization\Controllers;

use App\Features\Periodization\Requests\StoreMacrocycleRequest;
use App\Features\Periodization\Requests\UpdateMacrocycleRequest;
use App\Features\Periodization\Resources\MacrocycleResource;
use App\Http\Controllers\Controller;
use App\Models\Macrocycle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MacrocycleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $macrocycles = $request->user()
            ->macrocycles()
            ->withCount('mesocycles')
            ->paginate(15);

        return MacrocycleResource::collection($macrocycles)
            ->additional(['status' => 'success']);
    }

    public function store(StoreMacrocycleRequest $request): JsonResponse
    {
        $macrocycle = $request->user()->macrocycles()->create($request->validated());

        return (new MacrocycleResource($macrocycle->loadCount('mesocycles')))
            ->additional(['status' => 'success'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Macrocycle $macrocycle): MacrocycleResource
    {
        abort_if($request->user()->cannot('view', $macrocycle), 403);

        return (new MacrocycleResource($macrocycle->loadCount('mesocycles')))
            ->additional(['status' => 'success']);
    }

    public function update(UpdateMacrocycleRequest $request, Macrocycle $macrocycle): MacrocycleResource
    {
        abort_if($request->user()->cannot('update', $macrocycle), 403);

        $macrocycle->update($request->validated());

        return (new MacrocycleResource($macrocycle->loadCount('mesocycles')))
            ->additional(['status' => 'success']);
    }

    public function destroy(Request $request, Macrocycle $macrocycle): JsonResponse
    {
        abort_if($request->user()->cannot('delete', $macrocycle), 403);

        $macrocycle->delete();

        return response()->json(null, 204);
    }
}
