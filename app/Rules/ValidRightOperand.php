<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

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
        $operator = isset($this->data['operator']) ? trim((string) $this->data['operator']) : null;

        if ($operator !== '/') {
            return;
        }

        $operand = trim((string) $value);

        if ($operand === '' || preg_match('/^[+-]?0*(?:\.0*)?$/', $operand) === 1) {
            $fail('Division by zero is not allowed.');
        }
    }
}
