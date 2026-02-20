<?php

namespace App\Data;

use App\Http\Requests\StoreCalculationRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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
        $expression = self::nullableTrimmed(Arr::get($validated, 'expression'));

        if (filled($expression)) {
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
            left: self::nullableTrimmed(Arr::get($validated, 'left')),
            operator: self::nullableTrimmed(Arr::get($validated, 'operator')),
            right: self::nullableTrimmed(Arr::get($validated, 'right')),
            expression: null,
            metadata: ['input_type' => 'simple'],
        );
    }

    protected static function nullableTrimmed(mixed $value): ?string
    {
        $trimmed = Str::of((string) $value)->trim()->toString();

        return filled($trimmed) ? $trimmed : null;
    }
}
