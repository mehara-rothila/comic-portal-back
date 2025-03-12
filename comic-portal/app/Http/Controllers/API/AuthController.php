<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        try {
            // Validate incoming request
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // Create a new user
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'is_admin' => false, // Default to regular user
            ]);

            // Generate an access token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
                'is_admin' => false,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Registration failed due to a server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Log in an existing user.
     */
    public function login(Request $request)
    {
        try {
            // Validate incoming request
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            // Find user by email
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                Log::warning('Failed login attempt', ['email' => $request->email]);
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            // Add debug statement
            Log::info('User login successful', [
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'id' => $user->id
            ]);

            // Revoke previous tokens and generate a new one
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Successfully logged in',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
                'is_admin' => (bool)$user->is_admin, // Cast to boolean to ensure proper type
            ]);
        } catch (ValidationException $e) {
            Log::error('Login validation failed', [
                'errors' => $e->errors(),
                'email' => $request->email
            ]);
            return response()->json([
                'message' => 'Login failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage(), [
                'email' => $request->input('email')
            ]);
            return response()->json([
                'message' => 'Login failed due to a server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Log out the current user.
     */
    public function logout(Request $request)
    {
        try {
            // Ensure the user is authenticated
            if ($request->user()) {
                $request->user()->currentAccessToken()->delete();
                Log::info('User logged out successfully', [
                    'user_id' => $request->user()->id,
                    'is_admin' => $request->user()->is_admin
                ]);
                return response()->json(['message' => 'Successfully logged out']);
            }

            return response()->json(['message' => 'No active session found'], 401);
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Logout failed due to a server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch the authenticated user's details.
     */
    public function user(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            return response()->json([
                'user' => $user,
                'is_admin' => (bool)$user->is_admin // Cast to boolean to ensure proper type
            ]);
        } catch (\Exception $e) {
            Log::error('User fetch error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching user data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if user is an admin.
     */
    public function checkAdmin(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            return response()->json([
                'is_admin' => (bool)$user->is_admin // Cast to boolean to ensure proper type
            ]);
        } catch (\Exception $e) {
            Log::error('Admin check error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error checking admin status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}