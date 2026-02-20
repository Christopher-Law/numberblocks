<?php

use App\Http\Controllers\Api\CalculationController;
use Illuminate\Support\Facades\Route;

Route::get('/calculations', [CalculationController::class, 'index'])->name('api.calculations.index');
Route::post('/calculations', [CalculationController::class, 'store'])->name('api.calculations.store');
Route::delete('/calculations/{calculation}', [CalculationController::class, 'destroy'])->name('api.calculations.destroy');
Route::delete('/calculations', [CalculationController::class, 'clear'])->name('api.calculations.clear');
