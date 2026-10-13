<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Company\CompanyController;
use App\Http\Controllers\Api\Permission\PermissionController;
use App\Http\Controllers\Api\Role\RoleController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Middleware\SetCurrentCompany;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('api.auth.login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    Route::get('/auth/me', [AuthController::class, 'me'])->name('api.auth.me');

    Route::get('/companies', [CompanyController::class, 'index'])->name('api.companies.index');
    Route::post('/companies', [CompanyController::class, 'store'])->name('api.companies.store');

    Route::middleware(SetCurrentCompany::class)->group(function (): void {
        Route::patch('/companies/{company}', [CompanyController::class, 'update'])
            ->name('api.companies.update');

        // User & access management. Roles are administered alongside users,
        // so both resources share the `users.*` permission family.
        Route::middleware('permission:users.view')->group(function (): void {
            Route::get('/users', [UserController::class, 'index'])->name('api.users.index');
            Route::get('/roles', [RoleController::class, 'index'])->name('api.roles.index');
            Route::get('/permissions', [PermissionController::class, 'index'])->name('api.permissions.index');
        });

        Route::middleware('permission:users.create')->group(function (): void {
            Route::post('/users', [UserController::class, 'store'])->name('api.users.store');
            Route::post('/roles', [RoleController::class, 'store'])->name('api.roles.store');
        });

        Route::middleware('permission:users.update')->group(function (): void {
            Route::patch('/users/{user}', [UserController::class, 'update'])->name('api.users.update');
            Route::patch('/roles/{role}', [RoleController::class, 'update'])->name('api.roles.update');
        });

        Route::middleware('permission:users.delete')->group(function (): void {
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('api.users.destroy');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('api.roles.destroy');
        });
    });
});
