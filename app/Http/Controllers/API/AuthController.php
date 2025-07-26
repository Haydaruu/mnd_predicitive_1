<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\Agent;
use App\Enums\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Register a new user via API
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|lowercase|email|max:255|unique:users',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'role' => 'required|in:SuperAdmin,Admin,Agent',
                'extension' => 'required_if:role,Agent|string|unique:agents,extension',
            ]);

            $user = DB::transaction(function () use ($request) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => $request->role ?? UserRole::Agent->value,
                ]);

                // Create agent if role is Agent
                if ($request->role === 'Agent') {
                    $agent = Agent::create([
                        'user_id' => $user->id,
                        'name' => $request->name,
                        'extension' => $request->extension,
                        'status' => 'idle',
                    ]);

                    $user->update(['agent_id' => $agent->id]);
                }

                return $user;
            });

            // Create Sanctum token
            $token = $user->createToken('api-token', ['*'], now()->addDays(30))->plainTextToken;

            Log::info('✅ User registered via API', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user->load('agent'),
                    'token' => $token,
                ],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ Registration failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user via API
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt($credentials, $request->boolean('remember'))) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $user = $request->user();
            
            // Revoke all existing tokens
            $user->tokens()->delete();
            
            // Create new token
            $token = $user->createToken('api-token', ['*'], now()->addDays(30))->plainTextToken;

            Log::info('✅ User logged in via API', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user->load('agent'),
                    'token' => $token,
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ Login failed', [
                'message' => $e->getMessage(),
                'email' => $request->email ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout user via API
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Revoke current token
            $request->user()->currentAccessToken()->delete();
            
            Log::info('✅ User logged out via API', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Logout failed', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Logout failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get authenticated user info via API
     */
    public function user(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load('agent');

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Get user failed', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get user info: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh token via API
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Revoke current token
            $request->user()->currentAccessToken()->delete();
            
            // Create new token
            $token = $user->createToken('api-token', ['*'], now()->addDays(30))->plainTextToken;

            Log::info('✅ Token refreshed via API', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'user' => $user->load('agent'),
                    'token' => $token,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Token refresh failed', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}