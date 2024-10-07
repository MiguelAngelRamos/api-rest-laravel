<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    // Método para registrar un nuevo usuario y devolver el código QR
    public function register(Request $request)
    {
        // Validar los datos
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Crear nuevo usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Generar clave secreta para Google2FA
        $google2fa = new Google2FA();
        $user->google2fa_secret = $google2fa->generateSecretKey();
        $user->save();

        // Generar código QR
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->google2fa_secret
        );

        // Devolver la información del usuario y el código QR en la respuesta
        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'qrCodeUrl' => $qrCodeUrl
        ], 201);
    }

    // Método para iniciar sesión
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();

        // Verificar si el usuario tiene MFA habilitado
        if ($user->google2fa_secret) {
            return response()->json([
                'message' => 'MFA required',
                'mfa_required' => true
            ]);
        }

        // Si no tiene MFA, se responde con el token JWT directamente
        return $this->respondWithToken($token);
    }

    // Método para verificar MFA
    public function verifyMFA(Request $request)
    {
        $request->validate(['otp' => 'required']);

        $google2fa = new Google2FA();
        $user = auth()->user();

        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->otp);

        if ($valid) {
            // Si MFA es correcto, generamos y devolvemos el token JWT
            $token = auth()->refresh(); // Refrescamos o generamos el token JWT
            return $this->respondWithToken($token);
        }

        return response()->json(['error' => 'Invalid MFA code'], 401);
    }

    // Método para responder con el token JWT
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
