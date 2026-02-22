<?php

namespace App\Features\Workout\Controllers;

use App\Features\Workout\Requests\StoreWorkoutSessionRequest;
use App\Features\Workout\Requests\UpdateWorkoutSessionRequest;
use App\Features\Workout\Resources\WorkoutSessionResource;
use App\Http\Controllers\Controller;
use App\Models\WorkoutSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkoutSessionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $request->user()->workoutSessions()->latest('scheduled_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('scheduled_date', $request->date);
        }

        if ($request->filled('microcycle_id')) {
            $query->where('microcycle_id', $request->microcycle_id);
        }

        $sessions = $query->paginate(15);

        return WorkoutSessionResource::collection($sessions)
            ->additional(['status' => 'success']);
    }

    public function store(StoreWorkoutSessionRequest $request): JsonResponse
    {
        $session = $request->user()->workoutSessions()->create($request->validated());

        return (new WorkoutSessionResource($session))
            ->additional(['status' => 'success'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, WorkoutSession $session): WorkoutSessionResource
    {
        abort_if($request->user()->cannot('view', $session), 403);

        return (new WorkoutSessionResource($session))
            ->additional(['status' => 'success']);
    }

    public function update(UpdateWorkoutSessionRequest $request, WorkoutSession $session): WorkoutSessionResource
    {
        abort_if($request->user()->cannot('update', $session), 403);

        $session->update($request->validated());

        return (new WorkoutSessionResource($session))
            ->additional(['status' => 'success']);
    }

    public function destroy(Request $request, WorkoutSession $session): JsonResponse
    {
        abort_if($request->user()->cannot('delete', $session), 403);

        $session->delete();

        return response()->json(null, 204);
    }
}
