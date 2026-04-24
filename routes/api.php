<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GroupController;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/groups', [GroupController::class, 'index']);
    Route::post('/groups', [GroupController::class, 'store']);
    Route::get('/groups/{id}', [GroupController::class, 'show']);
    Route::put('/groups/{id}', [GroupController::class, 'update']);
    Route::delete('/groups/{id}', [GroupController::class, 'destroy']);
    Route::post('/groups/{id}/join', [GroupController::class, 'join']);


    Route::prefix('expenses')->group(function () {
        Route::controller(ExpenseController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{id}', 'show');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'destroy');

            Route::get('/byGroup/{group_id}', 'getByGroup');
            Route::post('/{id}/addMember', 'addMember');
            Route::post('/{id}/markMemberDebtAsPaid', 'markMemberDebtAsPaid');
        });
    });
});

//PfyLo3o5XbzDO9h9