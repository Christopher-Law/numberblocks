<?php

namespace App\Services\Calculator;

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
        return array_key_exists(strtolower($function), $this->all());
    }

    public function arity(string $function): int
    {
        return $this->all()[strtolower($function)] ?? 0;
    }

    public function apply(string $function, string $argument, HighPrecisionMath $math): string
    {
        return match (strtolower($function)) {
            'sqrt' => $math->sqrt($argument),
            default => throw new \InvalidArgumentException("Unsupported function [{$function}]."),
        };
    }
}
