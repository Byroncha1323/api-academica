<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Ejemplo unitario del esqueleto; no inicia ni comprueba el flujo HTTP de la API.
 */
class ExampleTest extends TestCase
{
    /**
     * Aserción básica que valida la configuración de PHPUnit.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
