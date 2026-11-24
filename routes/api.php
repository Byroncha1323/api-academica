<?php

/**
 * Contrato HTTP de la API académica. register/login son públicos; el resto
 * requiere identidad Sanctum y después aplica el Gate, middleware o Policy correspondiente.
 */

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EstudianteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/admin/dashboard', function (Request $request) {
        Gate::authorize('access-admin-dashboard');

        return response()->json([
            'message' => 'Bienvenido al dashboard administrativo',
            'user' => $request->user()->name,
        ]);
    });

    Route::get('/profesor/dashboard', function (Request $request) {
        Gate::authorize('access-professor-dashboard');

        return response()->json([
            'message' => 'Bienvenido al dashboard de profesores',
            'user' => $request->user()->name,
        ]);
    });

    Route::get('/estudiantes', [EstudianteController::class, 'index']);
    // RBAC por endpoint; la Policy también autoriza dentro del controlador.
    Route::post('/estudiantes', [EstudianteController::class, 'store'])
        ->middleware('permission:estudiantes.crear');
    // El parámetro activa el enlace de modelos de Laravel y el 404 si no existe.
    Route::get('/estudiantes/{estudiante}', [EstudianteController::class, 'show']);
    Route::put('/estudiantes/{estudiante}', [EstudianteController::class, 'update']);
    Route::patch('/estudiantes/{estudiante}', [EstudianteController::class, 'update']);
    Route::delete('/estudiantes/{estudiante}', [EstudianteController::class, 'destroy']);
});
