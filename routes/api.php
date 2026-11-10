<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\BukuApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ---------------------------------------------------------------
    // ENDPOINT PUBLIK (tanpa token)
    // ---------------------------------------------------------------
    Route::post('/auth/login', [AuthTokenController::class, 'login']);

    Route::get('/bukus', [BukuApiController::class, 'index']);
    Route::get('/bukus/{id}', [BukuApiController::class, 'show']);

    // ---------------------------------------------------------------
    // ENDPOINT TERPROTEKSI STATELESS BEARER TOKEN (wajib auth:sanctum)
    // ---------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthTokenController::class, 'me']);
        Route::post('/auth/logout', [AuthTokenController::class, 'logout']);

        // Mutasi katalog buku
        Route::post('/bukus', [BukuApiController::class, 'store']);
        Route::put('/bukus/{id}', [BukuApiController::class, 'update']);
        Route::patch('/bukus/{id}', [BukuApiController::class, 'update']);
        Route::delete('/bukus/{id}', [BukuApiController::class, 'destroy']);
    });
});