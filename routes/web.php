<?php

/**
 * Página de bienvenida del esqueleto Laravel; el taller se consume por las rutas /api.
 */

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
