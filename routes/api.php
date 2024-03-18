<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\CompanyController;

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

// Route::post('register', [UserController::class, 'register']);
Route::post('login',[UserController::class, 'login']);
Route::post('index',  [UserController::class, 'index']);

Route::middleware('auth:api')->group(function () {
    Route::post('register', [UserController::class, 'register']);
    Route::apiResource('company', CompanyController::class);
    Route::post('company/{company}', [CompanyController::class, 'update']);
});
