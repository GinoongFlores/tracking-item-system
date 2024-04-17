<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AuthController;
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


Route::post('login',[AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::get('/company/view', [CompanyController::class, 'showRegisteredCompanies']);

Route::middleware('auth:api')->group(function () {

    // prefix for company
    Route::prefix('company')->group(function() {
        Route::get('/list', [CompanyController::class, 'index']);
        Route::get('/{id}/show', [CompanyController::class, 'show']);
        Route::post('/add', [CompanyController::class, 'store']);
        Route::post('/{id}/update', [CompanyController::class, 'update']);
        Route::delete('/{id}/delete', [CompanyController::class, 'destroy']);
        Route::post('/{id}/restore', [CompanyController::class, 'restore']);
    });

    // prefix for user
    Route::prefix('user')->group(function() {
        Route::get('/list', [UserController::class, 'index']);
        Route::get('/{id}/show', [UserController::class, 'show']);
        Route::get('/current', [UserController::class, 'currentUser']);
        Route::post('/add', [UserController::class, 'store']);
        Route::post('/{id}/update', [UserController::class, 'update']);
        Route::delete('/{id}/delete', [UserController::class, 'destroy']);
        Route::post('/{id}/restore', [UserController::class, 'restore']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // super_admin
        Route::post('/{id}/assign-role', [UserController::class, 'assignRole']);
        Route::post('/{id}/activation', [UserController::class, 'toggleActivation']);
    });

    // prefix for item
    Route::prefix('item')->group(function() {
        Route::get('/list', [ItemController::class, 'index']);
        Route::get('/{id}/show', [ItemController::class, 'show']);
        Route::get('/{user_id}/user-items', [ItemController::class, 'userItemIndex']);
        Route::get('/user/trashed', [ItemController::class, 'currentUserTrashedItems']);
        Route::get('/user', [ItemController::class, 'CurrentUserItem']);
        Route::post('/{id}/user/update', [ItemController::class, 'updateCurrentUserItem']);
        Route::post('/add', [ItemController::class, 'store']);
        Route::post('/{id}/restore', [ItemController::class, 'restore']);
        Route::post('/{id}/user/restore', [ItemController::class, 'restoreCurrentUserTrashedItems']);
        Route::post('/{id}/update', [ItemController::class, 'update']);
        Route::delete('/{id}/delete', [ItemController::class, 'destroy']);
    });

});
