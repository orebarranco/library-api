<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResendEmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\VerifyEmailController;
use App\Http\Controllers\Api\V1\Catalog\AuthorController;
use App\Http\Controllers\Api\V1\Catalog\BookController;
use App\Http\Controllers\Api\V1\Catalog\CategoryController;
use App\Http\Controllers\Api\V1\User\AssignRoleController;
use App\Http\Controllers\Api\V1\User\SuspendUserController;
use App\Http\Controllers\Api\V1\User\UnsuspendUserController;
use App\Http\Controllers\Api\V1\User\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified', 'role:admin', 'throttle:api'])->prefix('users')->name('users.')->group(function (): void {
    Route::apiResource('/', UserController::class)->parameters(['' => 'user']);
    Route::post('/{user}/suspend', SuspendUserController::class)->name('suspend');
    Route::post('/{user}/unsuspend', UnsuspendUserController::class)->name('unsuspend');
    Route::put('/{user}/role', AssignRoleController::class)->name('assign-role');
});

Route::prefix('authors')->name('authors.')->group(function (): void {
    Route::get('/', [AuthorController::class, 'index'])->name('index');
    Route::middleware(['auth:sanctum', 'role:librarian,admin', 'throttle:api'])->group(function (): void {
        Route::post('/', [AuthorController::class, 'store'])->name('store');
        Route::put('/{author}', [AuthorController::class, 'update'])->name('update');
    });
});

Route::prefix('books')->name('books.')->group(function (): void {
    Route::get('/', [BookController::class, 'index'])->name('index');
    Route::get('/{book}', [BookController::class, 'show'])->name('show');
    Route::middleware(['auth:sanctum', 'role:librarian,admin', 'throttle:api'])->group(function (): void {
        Route::post('/', [BookController::class, 'store'])->name('store');
        Route::put('/{book}', [BookController::class, 'update'])->name('update');
        Route::delete('/{book}', [BookController::class, 'destroy'])->name('destroy');
    });
});

Route::prefix('categories')->name('categories.')->group(function (): void {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::middleware(['auth:sanctum', 'role:librarian,admin', 'throttle:api'])->group(function (): void {
        Route::post('/', [CategoryController::class, 'store'])->name('store');
    });
});

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::middleware('throttle:auth')->group(function (): void {
        Route::post('register', RegisterController::class)->name('register');
        Route::post('login', LoginController::class)->name('login');
        Route::post('forgot-password', ForgotPasswordController::class)->name('password.forgot');
        Route::post('reset-password', ResetPasswordController::class)->name('password.reset');
    });

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
        Route::get('me', MeController::class)->name('me');
        Route::post('logout', LogoutController::class)->name('logout');
        Route::post('email/resend', ResendEmailVerificationController::class)->name('verification.send');
    });

    Route::get('email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:auth'])
        ->name('verification.verify');
});
