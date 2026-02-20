<?php

namespace App\Data;

use App\Http\Requests\StoreCalculationRequest;

class CalculationInputData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $mode,
        public ?string $left,
        public ?string $operator,
        public ?string $right,
        public ?string $expression,
        public array $metadata = [],
    ) {}

    public static function fromRequest(StoreCalculationRequest $request): self
    {
        $validated = $request->validated();
        $expression = isset($validated['expression']) ? trim((string) $validated['expression']) : null;

        if ($expression !== null && $expression !== '') {
            return new self(
                mode: 'expression',
                left: null,
                operator: null,
                right: null,
                expression: $expression,
                metadata: ['input_type' => 'expression'],
            );
        }

        return new self(
            mode: 'simple',
            left: isset($validated['left']) ? trim((string) $validated['left']) : null,
            operator: isset($validated['operator']) ? trim((string) $validated['operator']) : null,
            right: isset($validated['right']) ? trim((string) $validated['right']) : null,
            expression: null,
            metadata: ['input_type' => 'simple'],
        );
    }
}
