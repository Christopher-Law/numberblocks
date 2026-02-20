<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidCalculationExpressionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCalculationRequest;
use App\Http\Resources\CalculationResource;
use App\Models\Calculation;
use App\Services\Calculator\CalculationEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

class CalculationController extends Controller
{
    public function index(): JsonResponse
    {
        $calculations = Calculation::query()->latest()->get();

        return $this->successResponse(CalculationResource::collection($calculations));
    }

    public function store(StoreCalculationRequest $request, CalculationEngine $engine): JsonResponse
    {
        try {
            $payload = $engine->evaluate($request->toData());
        } catch (InvalidCalculationExpressionException $exception) {
            return $this->errorResponse($exception->getMessage(), 422);
        }

        $calculation = Calculation::query()->create($payload);

        return $this->successResponse(new CalculationResource($calculation), 201, 'Calculation created successfully.');
    }

    public function destroy(Calculation $calculation): JsonResponse
    {
        $calculation->delete();

        return $this->successResponse(null, 200, 'Calculation deleted successfully.');
    }

    public function clear(): JsonResponse
    {
        $deletedCount = Calculation::query()->delete();

        return $this->successResponse(
            ['deleted_count' => $deletedCount],
            200,
            'Calculation history cleared successfully.',
        );
    }

    /**
     * @param  CalculationResource|AnonymousResourceCollection|array<string, mixed>|null  $data
     */
    protected function successResponse(
        CalculationResource|AnonymousResourceCollection|array|null $data,
        int $status = 200,
        ?string $message = null
    ): JsonResponse {
        if ($data instanceof AnonymousResourceCollection || $data instanceof CalculationResource) {
            $resolved = $data->resolve(request());
            $data = is_array($resolved) && Arr::exists($resolved, 'data') ? $resolved['data'] : $resolved;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
