<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index()
    {
        try {
            $users = User::with('role')->get();
            return response()->json($users, 200);
        } catch (\Exception $e) {
            Log::error('User index error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve users'], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = User::with('role')->find($id);

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            return response()->json($user, 200);
        } catch (\Exception $e) {
            Log::error('User show error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve user'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $fields = $request->validate([
                'role_id' => 'exists:roles,id',
            ]);

            $user->update($fields);

            return response()->json([
                'message' => 'User updated successfully',
                'user' => $user->load('role')
            ], 200);
        } catch (\Exception $e) {
            Log::error('User update error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update user'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $user->delete();

            return response()->json(['message' => 'User deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('User delete error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete user'], 500);
        }
    }
}
