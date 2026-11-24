<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prueba de disponibilidad de la bienvenida de Laravel, sin probar permisos.
 */
class ExampleTest extends TestCase
{
    /**
     * Confirma que la ruta web principal responde HTTP 200.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
