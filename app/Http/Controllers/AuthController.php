<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    // Método para registrar un nuevo usuario y devolver el token JWT
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

        // Autenticar el usuario y generar el token JWT
        $token = JWTAuth::fromUser($user);

        // Devolver el token JWT al usuario después del registro
        return $this->respondWithToken($token);
    }

    // Método para iniciar sesión
// Método para iniciar sesión
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth('api')->user();

        // Si MFA ya está habilitado para este usuario, requerimos MFA
        if ($user->google2fa_secret && $user->mfa_enabled) {
            return response()->json([
                'message' => 'MFA required',
                'mfa_required' => true,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user' => $user,
            ]);
        }

        // Si no tiene MFA habilitado, se responde con el token JWT directamente
        return $this->respondWithToken($token);
    }

    // Método para activar MFA desde el perfil del usuario
// Método para activar MFA desde el perfil del usuario
    public function enableMFA(Request $request)
    {
        $user = auth()->user();
        $google2fa = new Google2FA();

        // Generar clave secreta para Google2FA
        $user->google2fa_secret = $google2fa->generateSecretKey();
        $user->mfa_enabled = true; // Aquí se actualiza el estado MFA a "habilitado"
        $user->save();

        // Generar código QR
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->google2fa_secret
        );

        // Devolver la URL del QR al frontend para que pueda ser escaneado
        return response()->json([
            'message' => 'MFA enabled successfully',
            'qrCodeUrl' => $qrCodeUrl,
            'mfa_enabled' => true // Notificar al frontend que MFA está habilitado
        ]);
    }

    // Método para verificar MFA
    public function verifyMFA(Request $request)
    {
        $request->validate(['otp' => 'required']);

        $google2fa = new Google2FA();
        $user = auth('api')->user(); // Usuario autenticado

        // Verificar el código OTP con el secreto almacenado del usuario
        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->otp);

        if ($valid) {
            // Si el MFA es válido, generar un nuevo token JWT
            $token = auth('api')->refresh();
            return $this->respondWithToken($token);
        }

        return response()->json(['error' => 'Invalid MFA code'], 401);
    }

    // Método para mostrar el perfil del usuario autenticado
    public function profile()
    {
        $user = auth('api')->user();

        return response()->json([
            'user' => $user,
        ]);
    }

    // Método para responder con el token JWT
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }
}
