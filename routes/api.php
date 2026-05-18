<?php

use App\Http\Controllers\ItemController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/items', [ItemController::class, 'index']);
Route::get('/items/generate', [ItemController::class, 'generate']);
Route::post('/items', [ItemController::class, 'store']);
Route::patch('/items/{id}/review', [ItemController::class, 'review']);
Route::post('/items/{id}/rejection-draft', [ItemController::class, 'rejectionDraft']);

Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{email}/history', [UserController::class, 'history']);
Route::post('/users/ban', [UserController::class, 'toggleBan']);
