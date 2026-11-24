<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Pendiente: Definir Gates access-admin-dashboard y access-professor-dashboard (Persona 3)
    }
}
