<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Rutas de autenticación
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Ruta para habilitar MFA desde el perfil del usuario
Route::middleware('auth:api')->post('enable-mfa', [AuthController::class, 'enableMFA']);

// Ruta para verificar el código MFA
Route::post('verify-mfa', [AuthController::class, 'verifyMFA']);

// Rutas protegidas por autenticación JWT y MFA
Route::middleware('jwt')->group(function () {
    Route::get('user-profile', [AuthController::class, 'profile']);
});
