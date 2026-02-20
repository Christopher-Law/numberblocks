<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCalculationRequest;
use App\Http\Resources\CalculationResource;
use App\Models\Calculation;
use App\Services\Calculator\CalculationEngine;
use Illuminate\Http\JsonResponse;

class CalculationController extends Controller
{
    public function index(): JsonResponse
    {
        $calculations = Calculation::query()->latest()->get();

        return CalculationResource::collection($calculations)->response();
    }

    public function store(StoreCalculationRequest $request, CalculationEngine $engine): JsonResponse
    {
        $payload = $engine->evaluate($request->toData());

        $calculation = Calculation::query()->create($payload);

        return (new CalculationResource($calculation))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Calculation $calculation): JsonResponse
    {
        $calculation->delete();

        return response()->json([
            'message' => 'Calculation deleted successfully.',
        ]);
    }

    public function clear(): JsonResponse
    {
        $deletedCount = Calculation::query()->delete();

        return response()->json([
            'deleted_count' => $deletedCount,
            'message' => 'Calculation history cleared successfully.',
        ]);
    }
}
