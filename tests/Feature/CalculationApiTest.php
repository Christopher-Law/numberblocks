<?php

use App\Models\Calculation;
use Carbon\Carbon;

it('creates a simple calculation and persists it', function () {
    $response = $this->postJson('/api/calculations', [
        'left' => '10.5',
        'operator' => '+',
        'right' => '2.25',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.mode', 'simple')
        ->assertJsonPath('data.result', '12.75');

    $this->assertDatabaseHas('calculations', [
        'mode' => 'simple',
        'left_operand' => '10.5',
        'operator' => '+',
        'right_operand' => '2.25',
        'result' => '12.75',
    ]);
});

it('creates an expression calculation', function () {
    $response = $this->postJson('/api/calculations', [
        'expression' => 'sqrt(81)^2',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.mode', 'expression')
        ->assertJsonPath('data.expression', 'sqrt(81)^2')
        ->assertJsonPath('data.result', '81');
});

it('lists ticker tape history with newest first', function () {
    Calculation::query()->create([
        'mode' => 'simple',
        'left_operand' => '1',
        'operator' => '+',
        'right_operand' => '1',
        'result' => '2',
        'metadata' => ['input_type' => 'simple'],
        'created_at' => Carbon::parse('2026-02-20 10:00:00'),
        'updated_at' => Carbon::parse('2026-02-20 10:00:00'),
    ]);

    Calculation::query()->create([
        'mode' => 'expression',
        'expression' => '2^3',
        'result' => '8',
        'metadata' => ['input_type' => 'expression'],
        'created_at' => Carbon::parse('2026-02-20 11:00:00'),
        'updated_at' => Carbon::parse('2026-02-20 11:00:00'),
    ]);

    $response = $this->getJson('/api/calculations');

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.0.expression', '2^3')
        ->assertJsonPath('data.1.operator', '+');
});

it('deletes a single calculation record', function () {
    $calculation = Calculation::query()->create([
        'mode' => 'simple',
        'left_operand' => '4',
        'operator' => '*',
        'right_operand' => '5',
        'result' => '20',
        'metadata' => ['input_type' => 'simple'],
    ]);

    $response = $this->deleteJson("/api/calculations/{$calculation->id}");

    $response
        ->assertSuccessful()
        ->assertJsonPath('message', 'Calculation deleted successfully.');

    $this->assertDatabaseMissing('calculations', [
        'id' => $calculation->id,
    ]);
});

it('clears all calculation history', function () {
    Calculation::query()->create([
        'mode' => 'simple',
        'left_operand' => '1',
        'operator' => '+',
        'right_operand' => '2',
        'result' => '3',
        'metadata' => ['input_type' => 'simple'],
    ]);

    Calculation::query()->create([
        'mode' => 'expression',
        'expression' => 'sqrt(9)',
        'result' => '3',
        'metadata' => ['input_type' => 'expression'],
    ]);

    $response = $this->deleteJson('/api/calculations');

    $response
        ->assertSuccessful()
        ->assertJsonPath('deleted_count', 2)
        ->assertJsonPath('message', 'Calculation history cleared successfully.');

    expect(Calculation::query()->count())->toBe(0);
});

it('rejects division by zero requests', function () {
    $response = $this->postJson('/api/calculations', [
        'left' => '1',
        'operator' => '/',
        'right' => '0',
    ]);

    $response->assertUnprocessable();

    expect(Calculation::query()->count())->toBe(0);
});
