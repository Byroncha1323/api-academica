<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Autenticación de clientes externos mediante tokens personales de Sanctum.
 * Las rutas protegidas autentican antes de aplicar las reglas de autorización.
 */
class AuthController extends Controller
{
    /**
     * Crea una cuenta sin roles ni permisos y entrega su primer token (HTTP 201).
     * Solo persiste nombre, correo y contraseña; los privilegios enviados por el
     * cliente se ignoran para impedir que el registro público eleve permisos.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Sanctum almacena el hash; el cliente recibe aquí el token utilizable.
        $token = $user->createToken('api-academica')->plainTextToken;

        return response()->json([
            'message' => 'Usuario registrado correctamente',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Valida las credenciales y entrega un nuevo token para esa cuenta.
     * Las credenciales incorrectas generan ValidationException (HTTP 422),
     * distinto del 401 que devuelve una ruta protegida sin autenticación válida.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        $token = $user->createToken('api-academica')->plainTextToken;

        return response()->json([
            'message' => 'Autenticación correcta',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Devuelve al usuario autenticado con carrera, sede, roles y permisos directos.
     * Los permisos heredados del rol se consultan con getAllPermissions();
     * no se debe interpretar la relación permissions vacía como ausencia de acceso.
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('carrera', 'sede', 'roles', 'permissions'),
        ]);
    }

    /**
     * Revoca únicamente el token de la solicitud actual y confirma con HTTP 200.
     * Otros tokens de la misma cuenta permanecen válidos; la cuenta no se elimina.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente',
        ]);
    }
}
