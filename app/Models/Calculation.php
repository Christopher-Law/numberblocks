<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;

class Calculation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'mode',
        'expression',
        'left_operand',
        'operator',
        'right_operand',
        'result',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => AsArrayObject::class,
        ];
    }
}
