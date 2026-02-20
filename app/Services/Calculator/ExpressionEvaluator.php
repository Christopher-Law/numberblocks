<?php

namespace App\Services\Calculator;

use App\Exceptions\InvalidCalculationExpressionException;
use InvalidArgumentException;

class ExpressionEvaluator
{
    public function __construct(
        protected OperatorRegistry $operatorRegistry,
        protected FunctionRegistry $functionRegistry,
        protected HighPrecisionMath $math,
    ) {}

    public function evaluate(string $expression): string
    {
        $tokens = $this->tokenize($expression);
        $rpn = $this->toRpn($tokens);

        return $this->evaluateRpn($rpn);
    }

    /**
     * @return array<int, array{type:string, value:string}>
     */
    protected function tokenize(string $expression): array
    {
        $tokens = [];
        $length = strlen($expression);
        $index = 0;

        while ($index < $length) {
            $character = $expression[$index];

            if (ctype_space($character)) {
                $index++;

                continue;
            }

            if (ctype_digit($character) || $character === '.') {
                [$number, $nextIndex] = $this->consumeNumber($expression, $index);
                $tokens[] = ['type' => 'number', 'value' => $number];
                $index = $nextIndex;

                continue;
            }

            if (ctype_alpha($character)) {
                [$function, $nextIndex] = $this->consumeWord($expression, $index);
                $tokens[] = ['type' => 'function', 'value' => strtolower($function)];
                $index = $nextIndex;

                continue;
            }

            if ($this->operatorRegistry->supports($character)) {
                if ($character === '-' && $this->isUnaryMinusContext($tokens)) {
                    $nextNonSpace = $this->peekNextNonSpace($expression, $index + 1);
                    if ($nextNonSpace !== null && (ctype_digit($nextNonSpace) || $nextNonSpace === '.')) {
                        [$number, $nextIndex] = $this->consumeNumber($expression, $index + 1);
                        $tokens[] = ['type' => 'number', 'value' => '-'.$number];
                        $index = $nextIndex;

                        continue;
                    }

                    $tokens[] = ['type' => 'number', 'value' => '0'];
                }

                $tokens[] = ['type' => 'operator', 'value' => $character];
                $index++;

                continue;
            }

            if ($character === '(') {
                $tokens[] = ['type' => 'lparen', 'value' => $character];
                $index++;

                continue;
            }

            if ($character === ')') {
                $tokens[] = ['type' => 'rparen', 'value' => $character];
                $index++;

                continue;
            }

            if ($character === ',') {
                $tokens[] = ['type' => 'comma', 'value' => $character];
                $index++;

                continue;
            }

            throw InvalidCalculationExpressionException::withMessage("Unsupported token [{$character}] in expression.");
        }

        if ($tokens === []) {
            throw InvalidCalculationExpressionException::withMessage('Expression cannot be empty.');
        }

        return $tokens;
    }

    /**
     * @param  array<int, array{type:string, value:string}>  $tokens
     * @return array<int, array{type:string, value:string}>
     */
    protected function toRpn(array $tokens): array
    {
        $output = [];
        $stack = [];

        foreach ($tokens as $token) {
            if ($token['type'] === 'number') {
                $output[] = $token;

                continue;
            }

            if ($token['type'] === 'function') {
                if (! $this->functionRegistry->supports($token['value'])) {
                    throw InvalidCalculationExpressionException::withMessage("Unsupported function [{$token['value']}].");
                }

                $stack[] = $token;

                continue;
            }

            if ($token['type'] === 'comma') {
                while ($stack !== [] && end($stack)['type'] !== 'lparen') {
                    $output[] = array_pop($stack);
                }

                if ($stack === []) {
                    throw InvalidCalculationExpressionException::withMessage('Misplaced comma in expression.');
                }

                continue;
            }

            if ($token['type'] === 'operator') {
                while ($stack !== []) {
                    $top = end($stack);

                    if (! is_array($top) || ! isset($top['type'], $top['value'])) {
                        break;
                    }

                    if ($top['type'] === 'function') {
                        $output[] = array_pop($stack);

                        continue;
                    }

                    if ($top['type'] !== 'operator') {
                        break;
                    }

                    $tokenPrecedence = $this->operatorRegistry->precedence($token['value']);
                    $topPrecedence = $this->operatorRegistry->precedence($top['value']);
                    $tokenAssociativity = $this->operatorRegistry->associativity($token['value']);

                    $shouldPop = ($tokenAssociativity === 'left' && $tokenPrecedence <= $topPrecedence)
                        || ($tokenAssociativity === 'right' && $tokenPrecedence < $topPrecedence);

                    if (! $shouldPop) {
                        break;
                    }

                    $output[] = array_pop($stack);
                }

                $stack[] = $token;

                continue;
            }

            if ($token['type'] === 'lparen') {
                $stack[] = $token;

                continue;
            }

            if ($token['type'] === 'rparen') {
                while ($stack !== [] && end($stack)['type'] !== 'lparen') {
                    $output[] = array_pop($stack);
                }

                if ($stack === []) {
                    throw InvalidCalculationExpressionException::withMessage('Expression has unbalanced parentheses.');
                }

                array_pop($stack);

                if ($stack !== [] && end($stack)['type'] === 'function') {
                    $output[] = array_pop($stack);
                }
            }
        }

        while ($stack !== []) {
            $item = array_pop($stack);
            if ($item['type'] === 'lparen' || $item['type'] === 'rparen') {
                throw InvalidCalculationExpressionException::withMessage('Expression has unbalanced parentheses.');
            }

            $output[] = $item;
        }

        return $output;
    }

    /**
     * @param  array<int, array{type:string, value:string}>  $rpn
     */
    protected function evaluateRpn(array $rpn): string
    {
        $stack = [];

        foreach ($rpn as $token) {
            if ($token['type'] === 'number') {
                $stack[] = $token['value'];

                continue;
            }

            if ($token['type'] === 'operator') {
                if (count($stack) < 2) {
                    throw InvalidCalculationExpressionException::withMessage('Malformed expression near operator token.');
                }

                $right = (string) array_pop($stack);
                $left = (string) array_pop($stack);

                try {
                    $stack[] = $this->operatorRegistry->apply($token['value'], $left, $right, $this->math);
                } catch (InvalidArgumentException $exception) {
                    throw InvalidCalculationExpressionException::withMessage($exception->getMessage());
                }

                continue;
            }

            if ($token['type'] === 'function') {
                $arity = $this->functionRegistry->arity($token['value']);
                if ($arity !== 1 || count($stack) < 1) {
                    throw InvalidCalculationExpressionException::withMessage('Malformed expression near function token.');
                }

                $argument = (string) array_pop($stack);
                try {
                    $stack[] = $this->functionRegistry->apply($token['value'], $argument, $this->math);
                } catch (InvalidArgumentException $exception) {
                    throw InvalidCalculationExpressionException::withMessage($exception->getMessage());
                }
            }
        }

        if (count($stack) !== 1) {
            throw InvalidCalculationExpressionException::withMessage('Malformed expression.');
        }

        return $this->math->normalize((string) $stack[0]);
    }

    /**
     * @return array{0:string,1:int}
     */
    protected function consumeNumber(string $expression, int $start): array
    {
        $number = '';
        $index = $start;
        $length = strlen($expression);
        $dotCount = 0;

        while ($index < $length) {
            $character = $expression[$index];
            if (! ctype_digit($character) && $character !== '.') {
                break;
            }

            if ($character === '.') {
                $dotCount++;
                if ($dotCount > 1) {
                    throw InvalidCalculationExpressionException::withMessage('Invalid numeric literal in expression.');
                }
            }

            $number .= $character;
            $index++;
        }

        if ($number === '' || $number === '.') {
            throw InvalidCalculationExpressionException::withMessage('Invalid numeric literal in expression.');
        }

        return [$number, $index];
    }

    /**
     * @return array{0:string,1:int}
     */
    protected function consumeWord(string $expression, int $start): array
    {
        $word = '';
        $index = $start;
        $length = strlen($expression);

        while ($index < $length && ctype_alpha($expression[$index])) {
            $word .= $expression[$index];
            $index++;
        }

        return [$word, $index];
    }

    /**
     * @param  array<int, array{type:string, value:string}>  $tokens
     */
    protected function isUnaryMinusContext(array $tokens): bool
    {
        if ($tokens === []) {
            return true;
        }

        $previousType = $tokens[count($tokens) - 1]['type'];

        return in_array($previousType, ['operator', 'lparen', 'comma'], true);
    }

    protected function peekNextNonSpace(string $expression, int $start): ?string
    {
        $length = strlen($expression);
        $index = $start;

        while ($index < $length) {
            if (! ctype_space($expression[$index])) {
                return $expression[$index];
            }

            $index++;
        }

        return null;
    }
}
