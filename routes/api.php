<?php

use App\Http\Controllers\ItemController;
use Illuminate\Support\Facades\Route;

Route::get('/items', [ItemController::class, 'index']);
Route::get('/items/generate', [ItemController::class, 'generate']);
Route::post('/items', [ItemController::class, 'store']);
Route::patch('/items/{id}/review', [ItemController::class, 'review']);
Route::post('/items/{id}/rejection-draft', [ItemController::class, 'rejectionDraft']);
