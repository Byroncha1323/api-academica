<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Registra capacidades generales de la aplicación que no requieren un estudiante.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Punto de registro de servicios; el taller no necesita bindings adicionales.
     */
    public function register(): void
    {
        //
    }

    /**
     * Define los Gates ejecutados en las rutas de dashboards tras autenticar.
     * No hay una excepción global para administrador en el dashboard del profesor.
     */
    public function boot(): void
    {
        Gate::define('access-admin-dashboard', function (User $user): bool {
            return $user->hasRole('administrador');
        });

        Gate::define('access-professor-dashboard', function (User $user): bool {
            return $user->hasRole('profesor');
        });
    }
}
