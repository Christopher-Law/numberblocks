<?php

namespace App\Services\Calculator;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class FunctionRegistry
{
    /**
     * @return array<string, int>
     */
    public function all(): array
    {
        return [
            'sqrt' => 1,
        ];
    }

    public function supports(string $function): bool
    {
        return Arr::exists($this->all(), Str::lower($function));
    }

    public function arity(string $function): int
    {
        return $this->all()[Str::lower($function)] ?? 0;
    }

    public function apply(string $function, string $argument, HighPrecisionMath $math): string
    {
        return match (Str::lower($function)) {
            'sqrt' => $math->sqrt($argument),
            default => throw new \InvalidArgumentException("Unsupported function [{$function}]."),
        };
    }
}
