<?php

namespace App\Http\Requests;

use App\Data\CalculationInputData;
use App\Rules\ValidCalculationPayload;
use App\Rules\ValidExpression;
use App\Rules\ValidRightOperand;
use Illuminate\Foundation\Http\FormRequest;

class StoreCalculationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'calculation_payload' => [new ValidCalculationPayload],
            'left' => ['nullable', 'numeric', 'required_without:expression'],
            'operator' => ['nullable', 'in:+,-,*,/,^', 'required_without:expression'],
            'right' => ['nullable', 'numeric', 'required_without:expression', new ValidRightOperand],
            'expression' => ['nullable', 'string', 'max:500', 'required_without_all:left,operator,right', new ValidExpression],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'left.required_without' => 'Left operand is required when expression is not provided.',
            'operator.required_without' => 'Operator is required when expression is not provided.',
            'right.required_without' => 'Right operand is required when expression is not provided.',
            'operator.in' => 'Operator must be one of +, -, *, /, ^.',
            'expression.required_without_all' => 'Expression is required when simple operands are not provided.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'calculation_payload' => true,
        ]);
    }

    public function toData(): CalculationInputData
    {
        return CalculationInputData::fromRequest($this);
    }
}
