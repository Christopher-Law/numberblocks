<?php

namespace App\Services\Calculator;

use Illuminate\Support\Str;

class HighPrecisionMath
{
    public function __construct(
        protected int $scale = 12,
    ) {}

    public function add(string $left, string $right): string
    {
        return $this->normalize(bcadd($left, $right, $this->scale));
    }

    public function subtract(string $left, string $right): string
    {
        return $this->normalize(bcsub($left, $right, $this->scale));
    }

    public function multiply(string $left, string $right): string
    {
        return $this->normalize(bcmul($left, $right, $this->scale));
    }

    public function divide(string $left, string $right): string
    {
        if ($this->isZero($right)) {
            throw new \InvalidArgumentException('Division by zero is not allowed.');
        }

        return $this->normalize(bcdiv($left, $right, $this->scale));
    }

    public function power(string $base, string $exponent): string
    {
        if (! preg_match('/^-?\d+$/', $exponent)) {
            throw new \InvalidArgumentException('Only integer exponents are supported.');
        }

        $exp = (int) $exponent;
        if ($exp === 0) {
            return '1';
        }

        if ($exp > 0) {
            return $this->normalize(bcpow($base, (string) $exp, $this->scale));
        }

        $positivePower = bcpow($base, (string) abs($exp), $this->scale);

        return $this->divide('1', $positivePower);
    }

    public function sqrt(string $value): string
    {
        if ($this->compare($value, '0') < 0) {
            throw new \InvalidArgumentException('Square root of a negative number is not supported.');
        }

        if (function_exists('bcsqrt')) {
            /** @var string $sqrt */
            $sqrt = bcsqrt($value, $this->scale);

            return $this->normalize($sqrt);
        }

        return $this->normalize((string) sqrt((float) $value));
    }

    public function compare(string $left, string $right): int
    {
        return bccomp($left, $right, $this->scale);
    }

    public function isZero(string $value): bool
    {
        return $this->compare($value, '0') === 0;
    }

    public function normalize(string $value): string
    {
        $normalized = Str::of($value)->trim()->toString();

        if (Str::contains($normalized, '.')) {
            $normalized = rtrim($normalized, '0');
            $normalized = rtrim($normalized, '.');
        }

        if ($normalized === '' || $normalized === '-0') {
            return '0';
        }

        return $normalized;
    }
}
