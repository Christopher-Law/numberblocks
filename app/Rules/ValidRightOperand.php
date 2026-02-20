<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ValidRightOperand implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $operator = Str::of((string) Arr::get($this->data, 'operator'))->trim()->toString();

        if ($operator !== '/') {
            return;
        }

        $operand = Str::of((string) $value)->trim()->toString();

        if (blank($operand) || preg_match('/^[+-]?0*(?:\.0*)?$/', $operand) === 1) {
            $fail('Division by zero is not allowed.');
        }
    }
}
