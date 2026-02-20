<?php

namespace App\Services\Calculator;

use App\Data\CalculationInputData;
use App\Exceptions\InvalidCalculationExpressionException;
use InvalidArgumentException;

class CalculationEngine
{
    public function __construct(
        protected OperatorRegistry $operatorRegistry,
        protected FunctionRegistry $functionRegistry,
        protected ExpressionEvaluator $expressionEvaluator,
        protected HighPrecisionMath $math,
    ) {}

    /**
     * @return array{
     *     mode:string,
     *     expression:?string,
     *     left_operand:?string,
     *     operator:?string,
     *     right_operand:?string,
     *     result:string,
     *     metadata:array<string, mixed>
     * }
     */
    public function evaluate(CalculationInputData $input): array
    {
        if ($input->mode === 'expression' && $input->expression !== null) {
            $result = $this->expressionEvaluator->evaluate($input->expression);

            return [
                'mode' => 'expression',
                'expression' => $input->expression,
                'left_operand' => null,
                'operator' => null,
                'right_operand' => null,
                'result' => $result,
                'metadata' => array_merge($input->metadata, $this->supportedCapabilities()),
            ];
        }

        if ($input->left === null || $input->operator === null || $input->right === null) {
            throw InvalidCalculationExpressionException::withMessage('Incomplete simple calculation payload.');
        }

        if (! $this->operatorRegistry->supports($input->operator)) {
            throw InvalidCalculationExpressionException::withMessage("Unsupported operator [{$input->operator}].");
        }

        try {
            $result = $this->operatorRegistry->apply($input->operator, $input->left, $input->right, $this->math);
        } catch (InvalidArgumentException $exception) {
            throw InvalidCalculationExpressionException::withMessage($exception->getMessage());
        }

        return [
            'mode' => 'simple',
            'expression' => null,
            'left_operand' => $input->left,
            'operator' => $input->operator,
            'right_operand' => $input->right,
            'result' => $result,
            'metadata' => array_merge($input->metadata, $this->supportedCapabilities()),
        ];
    }

    /**
     * @return array{supported_functions:array<int, string>,supported_operators:array<int, string>}
     */
    protected function supportedCapabilities(): array
    {
        return [
            'supported_functions' => collect($this->functionRegistry->all())->keys()->values()->all(),
            'supported_operators' => collect($this->operatorRegistry->all())->keys()->values()->all(),
        ];
    }
}
