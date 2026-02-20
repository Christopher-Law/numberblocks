<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCalculationPayload implements DataAwareRule, ValidationRule
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
        $hasExpression = isset($this->data['expression']) && trim((string) $this->data['expression']) !== '';
        $hasSimpleFields = isset($this->data['left'], $this->data['operator'], $this->data['right'])
            && trim((string) $this->data['left']) !== ''
            && trim((string) $this->data['operator']) !== ''
            && trim((string) $this->data['right']) !== '';

        if ($hasExpression && $hasSimpleFields) {
            $fail('Provide either expression mode or simple operand mode, not both.');

            return;
        }

        if (! $hasExpression && ! $hasSimpleFields) {
            $fail('Provide either expression or all simple operands (left, operator, right).');
        }
    }
}
