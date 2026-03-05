<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login',    [AuthController::class, 'login'])->name('auth.login');
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', fn(Request $request) => $request->user());

    // Roles
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/',           [RoleController::class, 'index'])->name('index');
        Route::post('/',          [RoleController::class, 'store'])->name('store');
        Route::get('/{id}',       [RoleController::class, 'show'])->name('show');
        Route::patch('/{id}',     [RoleController::class, 'update'])->name('update');
        Route::delete('/{id}',    [RoleController::class, 'destroy'])->name('destroy');

        Route::post('/{id}/permissions',            [RoleController::class, 'assignPermission'])->name('permissions.assign');
        Route::delete('/{id}/permissions',          [RoleController::class, 'revokePermission'])->name('permissions.revoke');
    });

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/',           [UserController::class, 'index'])->name('index');
        Route::post('/',          [UserController::class, 'store'])->name('store');
        Route::get('/{id}',       [UserController::class, 'show'])->name('show');
        Route::patch('/{id}',     [UserController::class, 'update'])->name('update');
        Route::delete('/{id}',    [UserController::class, 'destroy'])->name('destroy');

        Route::post('/{id}/roles',                  [UserController::class, 'assignRole'])->name('roles.assign');
        Route::delete('/{id}/roles',                [UserController::class, 'revokeRole'])->name('roles.revoke');
    });
});
