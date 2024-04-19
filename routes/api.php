<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/fetch', [\App\Http\Controllers\ScrapperController::class, 'fetch']);
Route::post('/fetch-recursive', [\App\Http\Controllers\ScrapperController::class, 'fetchRecursive']);
