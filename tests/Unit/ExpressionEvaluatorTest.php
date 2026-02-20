<?php

use App\Exceptions\InvalidCalculationExpressionException;
use App\Services\Calculator\ExpressionEvaluator;
use App\Services\Calculator\FunctionRegistry;
use App\Services\Calculator\HighPrecisionMath;
use App\Services\Calculator\OperatorRegistry;

function evaluator(): ExpressionEvaluator
{
    return new ExpressionEvaluator(
        new OperatorRegistry,
        new FunctionRegistry,
        new HighPrecisionMath,
    );
}

it('handles operator precedence and parentheses', function () {
    $result = evaluator()->evaluate('(2+3)*4');

    expect($result)->toBe('20');
});

it('supports right-associative exponent operations', function () {
    $result = evaluator()->evaluate('2^3^2');

    expect($result)->toBe('512');
});

it('supports sqrt and unary minus', function () {
    expect(evaluator()->evaluate('sqrt(81)'))->toBe('9');
    expect(evaluator()->evaluate('-3+5'))->toBe('2');
});

it('supports nested stretch-goal expressions', function () {
    $result = evaluator()->evaluate('sqrt((((9*9)/12)+(13-4))*2)^2');

    expect((float) $result)->toBeGreaterThan(31.49)
        ->toBeLessThan(31.51);
});

it('throws for malformed expressions', function () {
    expect(fn () => evaluator()->evaluate('2++3'))
        ->toThrow(InvalidCalculationExpressionException::class);
});
