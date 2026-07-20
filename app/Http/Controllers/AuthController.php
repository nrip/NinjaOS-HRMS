<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login endpoint - returns API token
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This user account is inactive.'],
            ]);
        }

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        return response()->json([
            'token' => $user->createToken('api-token')->plainTextToken,
            'user' => $user->only(['id', 'name', 'email', 'location_id']),
        ]);
    }

    /**
     * Logout endpoint - revokes current token
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get current user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $user->load('location');
        $user->roles; // Load roles

        return response()->json([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}
