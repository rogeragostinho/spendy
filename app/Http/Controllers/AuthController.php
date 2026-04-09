<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

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

        return response()->json(['token' => $token->plainTextToken], 201);
    }
}
