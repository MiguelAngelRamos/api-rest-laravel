<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    // Método para registrar un nuevo usuario
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
            'password' => Hash::make($request->password), // Encriptar la contraseña
        ]);

        // Generar clave secreta para Google2FA
        $google2fa = new Google2FA();
        $user->google2fa_secret = $google2fa->generateSecretKey();
        $user->save();

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user
        ], 201);
    }

    // Método para generar el código QR para Google Authenticator
    public function getQRCode(Request $request)
    {
        $user = auth()->user(); // Obtener el usuario autenticado

        // Generar código QR para Google Authenticator
        $google2fa = new Google2FA();
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->google2fa_secret
        );

        return response()->json([
            'qrCodeUrl' => $qrCodeUrl,
        ]);
    }

    // Método para iniciar sesión
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

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
            return response()->json(['message' => 'MFA verified successfully'], 200);
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
