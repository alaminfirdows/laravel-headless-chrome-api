<?php

declare(strict_types=1);

use App\Http\Controllers\ScrapperController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('fetch', [ScrapperController::class, 'fetch']);
Route::get('fetch-recursive', [ScrapperController::class, 'fetchRecursive']);
