<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\CompanyController;
use App\Http\Controllers\API\ItemController;

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
Route::post('register', [UserController::class, 'register']);
// Route::get('index',  [UserController::class, 'index'])->middleware('auth:api');
Route::post('/users/{id}/assign_role', [UserController::class, 'assignRole'])->middleware('auth:api');

Route::middleware('auth:api')->group(function () {
    // api resource
    Route::apiResource('company', CompanyController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('item', ItemController::class);

    // company post method
    Route::post('company/{id}/update', [CompanyController::class, 'update']);
    Route::post('company/{id}/restore', [CompanyController::class, 'restore']);

    // users post method
    Route::post('/users/{id}/activation', [UserController::class, 'toggleActivation']);
    Route::post('/users/{id}/update', [UserController::class, 'update']);

    // item post method
    Route::post('item/{id}/restore', [ItemController::class, 'restore']);
});
