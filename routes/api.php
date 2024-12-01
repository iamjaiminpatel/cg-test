<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AlbumController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ResetPasswordController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register',[UserController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/password/email', [ResetPasswordController::class, 'sendResetLink']);
Route::post('/password/reset', [ResetPasswordController::class, 'resetPassword']);
Route::middleware('auth:api')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [UserController::class, 'index']);
    Route::post('/user/{id}', [UserController::class, 'update']);
    Route::get('/refresh', [AuthController::class, 'refresh']);
    Route::post('/change-password', [UserController::class, 'changePassword']);

    Route::post('/album', [AlbumController::class, 'create']);
    Route::get('/album', [AlbumController::class, 'index']);
    Route::post('/album/{id}', [AlbumController::class, 'update']);
    Route::delete('/album/{id}', [AlbumController::class, 'delete']);
});