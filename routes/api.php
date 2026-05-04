<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware('api.version:v1')
    ->group(function (): void {
        require base_path('routes/api/v1.php');
    });
