<?php

namespace App\Services\Calculator;

use Illuminate\Support\Arr;

class OperatorRegistry
{
    /**
     * @return array<string, array{precedence:int,associativity:string}>
     */
    public function all(): array
    {
        return [
            '+' => ['precedence' => 2, 'associativity' => 'left'],
            '-' => ['precedence' => 2, 'associativity' => 'left'],
            '*' => ['precedence' => 3, 'associativity' => 'left'],
            '/' => ['precedence' => 3, 'associativity' => 'left'],
            '^' => ['precedence' => 4, 'associativity' => 'right'],
        ];
    }

    public function supports(string $operator): bool
    {
        return Arr::exists($this->all(), $operator);
    }

    public function precedence(string $operator): int
    {
        return $this->all()[$operator]['precedence'] ?? 0;
    }

    public function associativity(string $operator): string
    {
        return $this->all()[$operator]['associativity'] ?? 'left';
    }

    public function apply(string $operator, string $left, string $right, HighPrecisionMath $math): string
    {
        return match ($operator) {
            '+' => $math->add($left, $right),
            '-' => $math->subtract($left, $right),
            '*' => $math->multiply($left, $right),
            '/' => $math->divide($left, $right),
            '^' => $math->power($left, $right),
            default => throw new \InvalidArgumentException("Unsupported operator [{$operator}]."),
        };
    }
}
