<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\AuthResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): AuthResource
    {
        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'phone' => $request->input('phone'),
            'city' => $request->input('city'),
            'lat' => $request->input('lat'),
            'lng' => $request->input('lng'),
        ]);

        $token = $user->createToken($request->userAgent() ?: 'api')->plainTextToken;

        return (new AuthResource(['user' => $user, 'token' => $token]))
            ->additional(['meta' => (object) []]);
    }

    /**
     * Login using email and password.
     */
    public function login(LoginRequest $request): AuthResource|JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if ($user->is_blocked) {
            return response()->json(['message' => 'Account is blocked.'], 403);
        }

        $token = $user->createToken($request->userAgent() ?: 'api')->plainTextToken;

        return (new AuthResource(['user' => $user, 'token' => $token]))
            ->additional(['meta' => (object) []]);
    }

    /**
     * Logout current token.
     */
    public function logout(): JsonResponse
    {
        $user = request()->user();

        if ($user?->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json(['data' => ['message' => 'Logged out.'], 'meta' => (object) []]);
    }
}
