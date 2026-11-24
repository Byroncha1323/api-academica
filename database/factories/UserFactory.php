<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Genera cuentas aisladas para los tests sin asignar roles académicos.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Hash reutilizado para evitar calcular la contraseña de prueba en cada cuenta.
     */
    protected static ?string $password;

    /**
     * Datos predeterminados con correo único y contraseña de prueba con hash.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Estado alternativo de una cuenta cuyo correo todavía no fue verificado.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
