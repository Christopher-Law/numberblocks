<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class ValidExpression implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Expression must be a string.');

            return;
        }

        $expression = Str::of($value)->trim()->toString();

        if (blank($expression)) {
            $fail('Expression cannot be empty.');

            return;
        }

        if (! preg_match('/^[0-9+\-*\/^().,\sA-Za-z]+$/', $expression)) {
            $fail('Expression contains unsupported characters.');

            return;
        }

        $balance = 0;
        foreach (str_split($expression) as $character) {
            if ($character === '(') {
                $balance++;
            }

            if ($character === ')') {
                $balance--;
            }

            if ($balance < 0) {
                $fail('Expression has unbalanced parentheses.');

                return;
            }
        }

        if ($balance !== 0) {
            $fail('Expression has unbalanced parentheses.');

            return;
        }

        if (preg_match('/sqrt\s*\(\s*-\s*[0-9.]+\s*\)/i', $expression)) {
            $fail('Square root of a negative number is not supported.');
        }
    }
}
