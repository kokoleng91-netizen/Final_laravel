<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            "username" => "required",
            "email"    => "required|email|unique:users,email",
            "password" => "required|min:6",
        ]);

        $role = Role::where('role_name', 'customer')->first();

        $user = User::create([
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role_id'  => $role->id
        ]);

        return response()->json([
            "user" => [
                "id" => $user->id,
                "username" => $user->username,
                "email" => $user->email,
                "role" => $user->role->role_name
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            "email" => "required|email",
            "password" => "required",
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(["message" => "Invalid credentials"], 401);
        }

        // ✅ ADD THIS LINE
        // dd(method_exists(Auth::user(), 'createToken'));

        $user = Auth::user();

        /** @var User $user */
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            "token" => $token,
            "user"  => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(["message" => "Logged out"]);
    }
}
