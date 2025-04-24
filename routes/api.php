<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/user', [UserController::class, 'listUsersWithPosts']);
Route::post('/user', [UserController::class, 'createUser']);
Route::post('/user-block/{id}', [UserController::class, 'blockUser']);
Route::post('/user/{id}', [UserController::class, 'deleteUser']);
