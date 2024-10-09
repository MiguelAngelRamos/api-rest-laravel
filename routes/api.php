<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;

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

Route::middleware('jwt')->group(function () {
    // CRUD de libros
    Route::post('books', [BookController::class, 'store']);      // Crear libro
    Route::get('books', [BookController::class, 'index']);       // Listar todos los libros (problema de seguridad)
    Route::get('books/{id}', [BookController::class, 'show']);   // Mostrar un libro específico por su identificador
});
