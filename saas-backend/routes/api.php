<?php

use App\Http\Controllers\Api\AppController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\NavigationController;
use App\Http\Controllers\Api\OptionController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\WilayahController;
use App\Http\Middleware\TenantAwareMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('app', [AppController::class, 'index']);

// Authentication (public)
Route::post('login', [AuthController::class, 'login']);
Route::post('auth/refresh', [AuthController::class, 'refresh']);
Route::post('forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);
Route::post('register', [RegisterController::class, 'register']);
Route::post('auth/google', [AuthController::class, 'loginWithGoogle']);

// Public organization lookup (for subdomain resolution)
Route::get('organizations/by-slug/{slug}', [OrganizationController::class, 'bySlug']);

Route::middleware(['jwt'])->group(function () {
    // Navigation API - returns menu items and layout type based on role
    Route::get('navigation', [NavigationController::class, 'index']);

    // Generic app/platform modules
    Route::resource('modules', ModuleController::class);

    // Profile and account management
    Route::get('me', [ProfileController::class, 'index'])->name('me');
    Route::get('profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');

    // Explicit neutral Branch Management API. Organization context is resolved from JWT/X-Organization-ID.
    Route::apiResource('branches', BranchController::class)->names('branches');

    // Organization Management (Multi-Tenant)
    Route::prefix('organizations')->group(function () {
        Route::get('/', [OrganizationController::class, 'index'])->name('organizations.index');
        Route::get('/current', [OrganizationController::class, 'current'])->name('organizations.current');
        Route::get('/{organization}/show', [OrganizationController::class, 'show'])->name('organizations.show');
        Route::post('/{organization}/switch', [OrganizationController::class, 'switch'])->name('organizations.switch');
        Route::post('/{organization}/set-default', [OrganizationController::class, 'setDefault'])->name('organizations.set-default');

        // Branch Management scoped to an organization URL.
        Route::apiResource('branches', BranchController::class)->names('organizations.branches');

        // Roles & Permissions Management
        Route::resource('roles', RoleController::class);
        Route::get('permissions', [RoleController::class, 'getAllPermissions']);
    });

    Route::get('current-tenant', [AuthController::class, 'currentTenant'])->middleware(TenantAwareMiddleware::class);

    // Users / team management
    Route::resource('staffs', StaffController::class);

    // Generic SaaS subscription/billing core
    Route::get('saas/plans', [SubscriptionController::class, 'plans']);
    Route::post('saas/subscribe', [SubscriptionController::class, 'subscribe']);
});

Route::prefix('options')->group(function () {
    Route::get('wilayah', [WilayahController::class, 'kelurahan']);
    Route::get('wilayah/{kelurahanId}/kode-pos', [WilayahController::class, 'kodePos']);
    Route::get('prefix', [OptionController::class, 'prefix']);
    Route::get('suffix', [OptionController::class, 'suffix']);
    Route::get('roles', [OptionController::class, 'roles']);
});

Route::post('/logout', function () {
    return true;
});
