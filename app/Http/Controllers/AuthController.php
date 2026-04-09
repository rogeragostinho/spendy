<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|min:3',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create($validated);

        $token = $user->createToken('auth_token');

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials  = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Credenciais inválidas'
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request) {
        $request->user()->tokens()->delete(); // ou currentAcessToken()->delete()

        return response()->json([
            'message' => 'Logout feito com sucesso'
        ]);
    }

    public function me(Request $request) {
        return response()->json([
            'user' => $request->user()
        ]);
    }
}
