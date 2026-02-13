<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $this->authService->register($validated);

        $this->authService->login($request->only('email', 'password'));

        return response()->json([
            'data'    => $user,
            'message' => 'ok',
        ], 201);
    }

    /**
     * Log the user in.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! $this->authService->login($validated)) {
            return response()->json([
                'data'    => null,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'data'    => $request->user(),
            'message' => 'ok',
        ]);
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout();

        return response()->json([
            'data'    => null,
            'message' => 'ok',
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data'    => $request->user(),
            'message' => 'ok',
        ]);
    }
}
