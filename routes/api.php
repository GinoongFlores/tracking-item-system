<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CompanyController;
use App\Http\Controllers\API\ItemController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\AdminController;

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

        // admin
        Route::get('/admin/list', [AdminController::class, 'indexByCompany']);

        // super_admin
        Route::post('/{id}/assign-role', [UserController::class, 'assignRole']);
        Route::post('/{id}/activation', [UserController::class, 'toggleActivation']);

        // transfer user
        Route::get('/search', [UserController:: class, 'searchUser']);
    });

    // prefix for item
    Route::prefix('item')->group(function() {
        Route::get('/list', [ItemController::class, 'index']);
        Route::get('/{id}/show', [ItemController::class, 'show']);
        // Route::get('/items', [ItemController::class, 'userItemIndex']);
        Route::get('/user/trashed', [ItemController::class, 'currentUserTrashedItems']);
        // Route::get('/user', [ItemController::class, 'CurrentUserItem']);
        Route::post('/{id}/user/update', [ItemController::class, 'update']);
        Route::post('/{id}/update', [ItemController::class, 'update']);
        Route::post('/add', [ItemController::class, 'store']);
        Route::post('/{id}/restore', [ItemController::class, 'restore']);
        Route::post('/{id}/user/restore', [ItemController::class, 'restoreCurrentUserTrashedItems']);
        Route::delete('/{id}/delete', [ItemController::class, 'destroy']);

        // transfer item
        Route::post('/transfer', [TransactionController::class, 'transferItem']);
        Route::post('/transfer/{id}/status', [TransactionController::class, 'transactionStatus']);
        Route::post('/transaction/{transactionId}/received-item/{itemId}', [TransactionController::class, 'userAcceptItemStatus']);
        Route::get('/view-transactions', [TransactionController::class, 'viewTransactionSuperAdmin']);
        Route::get('/admin/view-transactions', [TransactionController::class, 'viewTransactionsPerAdmin']);
        Route::get('/user/view-transactions', [TransactionController::class, 'viewTransactionsPerUser']);
        Route::get('/received/view-transactions', [TransactionController::class, 'viewTransactionsForReceiver']);
        Route::get('/search', [ItemController::class, 'searchItem']);
    });

});
