<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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
        $expression = Arr::get($this->data, 'expression');
        $hasExpression = filled(Str::of((string) $expression)->trim()->toString());

        $simpleFields = collect(['left', 'operator', 'right'])
            ->map(fn (string $field): string => Str::of((string) Arr::get($this->data, $field))->trim()->toString());
        $hasSimpleFields = $simpleFields->every(fn (string $field): bool => filled($field));

        if ($hasExpression && $hasSimpleFields) {
            $fail('Provide either expression mode or simple operand mode, not both.');

            return;
        }

        if (! $hasExpression && ! $hasSimpleFields) {
            $fail('Provide either expression or all simple operands (left, operator, right).');
        }
    }
}
